<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\EventListener;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\EventListener\ResolveCategoryTypeGroupMarker;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

/**
 * The marker a FlexForm category field names its group with. The category trees of
 * the plugins that use it are covered end to end in their own extensions, this pins
 * what the marker resolves to.
 */
final class ResolveCategoryTypeGroupMarkerTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-group');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/categories.csv');
    }

    /**
     * The categories of the group, hidden ones included, and every ancestor they need to
     * be shown in a tree, however deep and whatever its type. A category of another
     * group below the same parent is not offered, and neither is a deleted category or
     * a translation. A parent loop ends the walk instead of running forever.
     */
    #[Test]
    public function theMarkerResolvesToTheCategoriesOfTheGroupAndTheirAncestors(): void
    {
        $this->assertSame(
            [1, 2, 3, 4, 5, 7, 8],
            $this->selectCategoryUids($this->resolve(
                'AND {#sys_category}.{#uid} IN (###CATEGORY_TYPE_GROUP:testing###)'
                . ' AND {#sys_category}.{#sys_language_uid} IN (-1, 0)'
            )),
        );
    }

    /**
     * The marker is resolved without a database query: TYPO3 v13 parses every data
     * structure while it builds the TCA schema during bootstrap, before any table
     * exists, and caches what it parsed. The statement it resolves to is evaluated by
     * the query that builds the tree instead.
     */
    #[Test]
    public function theMarkerResolvesToAStatementRatherThanToAListOfUids(): void
    {
        $resolved = $this->resolve('AND {#sys_category}.{#uid} IN (###CATEGORY_TYPE_GROUP:testing###)');

        $this->assertStringContainsString("{#type} IN ('testing_first','testing_second')", $resolved);
        $this->assertStringStartsWith('AND {#sys_category}.{#uid} IN (WITH RECURSIVE ', $resolved);
    }

    /**
     * Type identifiers are written into the statement as literals, so one that is not
     * made of letters, digits, `_` and `-` is left out instead of being quoted, which
     * would need a connection. A group left without a valid one resolves to `NULL`.
     */
    #[Test]
    public function anIdentifierWithOtherCharactersIsLeftOut(): void
    {
        $registry = new CategoryTypeRegistry();
        $registry->attach(
            new CategoryType('testing_first', 'tests', 'First', 'odd', '', 0),
            new CategoryType("x') OR ('1'='1", 'tests', 'Odd', 'odd', '', 0),
        );
        $listener = new ResolveCategoryTypeGroupMarker($registry);

        $resolved = $this->resolve('AND {#sys_category}.{#uid} IN (###CATEGORY_TYPE_GROUP:odd###)', $listener);
        $this->assertStringContainsString("{#type} IN ('testing_first')", $resolved);
        $this->assertStringNotContainsString("'1'='1", $resolved);

        $onlyOdd = new CategoryTypeRegistry();
        $onlyOdd->attach(new CategoryType('a b', 'tests', 'Odd', 'odd', '', 0));
        $this->assertSame(
            'AND {#sys_category}.{#uid} IN (NULL)',
            $this->resolve('AND {#sys_category}.{#uid} IN (###CATEGORY_TYPE_GROUP:odd###)', new ResolveCategoryTypeGroupMarker($onlyOdd)),
        );
    }

    #[Test]
    public function aGroupNoExtensionDeclaresResolvesToNull(): void
    {
        $this->assertSame(
            'AND {#sys_category}.{#uid} IN (NULL)',
            $this->resolve('AND {#sys_category}.{#uid} IN (###CATEGORY_TYPE_GROUP:unknown###)'),
        );
    }

    #[Test]
    public function aFieldWithoutTheMarkerIsLeftAlone(): void
    {
        $where = 'AND {#sys_category}.{#sys_language_uid} IN (-1, 0)';

        $this->assertSame($where, $this->resolve($where));
    }

    /**
     * Runs the condition the way the category tree does: the foreign table query with
     * the condition as its WHERE, deleted records excluded.
     *
     * @return list<int>
     */
    private function selectCategoryUids(string $where): array
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_category');
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $uids = $queryBuilder
            ->select('uid')
            ->from('sys_category')
            ->where(QueryHelper::quoteDatabaseIdentifiers($connection, QueryHelper::stripLogicalOperatorPrefix($where)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();

        return array_map(intval(...), $uids);
    }

    private function resolve(string $where, ?ResolveCategoryTypeGroupMarker $listener = null): string
    {
        $event = new AfterFlexFormDataStructureParsedEvent(
            [
                'sheets' => [
                    'sDEF' => [
                        'ROOT' => [
                            'type' => 'array',
                            'el' => [
                                'settings.categories' => [
                                    'config' => [
                                        'type' => 'category',
                                        'foreign_table_where' => $where,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            ['type' => 'tca', 'tableName' => 'tt_content', 'fieldName' => 'pi_flexform', 'dataStructureKey' => 'default'],
        );

        ($listener ?? $this->get(ResolveCategoryTypeGroupMarker::class))($event);

        return $event->getDataStructure()['sheets']['sDEF']['ROOT']['el']['settings.categories']['config']['foreign_table_where'];
    }
}
