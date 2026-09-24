<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Filter;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Filter\CategoryFilterNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The category filter of a demand form reaches a controller action as a plain request
 * array - `listAction(?array $demand = null)` validates nothing - so this class is what
 * stands between an arbitrary request and the category query.
 *
 * The three `Factory\DemandFactory` implementations of `EXT:academic_partners`,
 * `EXT:academic_programs` and `EXT:academic_projects` are its callers.
 */
final class CategoryFilterNormalizerTest extends UnitTestCase
{
    /**
     * What a single select of `ViewHelpers\Form\FilterSelectViewHelper` submits: one uid
     * per category type.
     */
    #[Test]
    public function oneValuePerCategoryTypeIsCollected(): void
    {
        $this->assertSame(
            [5, 8],
            $this->subject()->toUidList(['research_field' => '5', 'degree' => '8']),
        );
    }

    /**
     * What a select with `multiple` submits.
     */
    #[Test]
    public function aListOfValuesIsCollected(): void
    {
        $this->assertSame(
            [5, 8, 13],
            $this->subject()->toUidList(['research_field' => ['5', '8'], 'degree' => ['13']]),
        );
    }

    /**
     * No select can submit this, but a template assembling the value itself can.
     */
    #[Test]
    public function aCommaSeparatedValueIsSplit(): void
    {
        $this->assertSame([5, 8], $this->subject()->toUidList(['research_field' => '5,8']));
    }

    /**
     * The prepended "all options" entry carries an empty value, so an unselected filter
     * used to contribute uid 0 - one per category type. An empty list is what the
     * repository is handed now, which it takes since ACE-349.
     */
    #[Test]
    public function unselectedFiltersContributeNothing(): void
    {
        $this->assertSame([], $this->subject()->toUidList(['research_field' => '', 'degree' => '']));
    }

    #[Test]
    public function theSameUidIsCollectedOnce(): void
    {
        $this->assertSame([5], $this->subject()->toUidList(['research_field' => '5', 'degree' => ['5']]));
    }

    /**
     * The category type the values were submitted under is not part of the result: the
     * repository resolves the types of the whole group anyway.
     */
    #[Test]
    public function theCategoryTypeKeyIsNotEvaluated(): void
    {
        $this->assertSame([5], $this->subject()->toUidList(['anything at all' => '5']));
    }

    /**
     * @param array<int, int> $expected
     */
    #[Test]
    #[DataProvider('unreadableFilters')]
    public function anUnreadableFilterIsNoFilter(mixed $filter, array $expected): void
    {
        $this->assertSame($expected, $this->subject()->toUidList($filter));
    }

    /**
     * A crafted request may put anything in there. None of it may raise, because that
     * would take the whole list plugin down instead of dropping the filter.
     *
     * @return array<string, array{0: mixed, 1: array<int, int>}>
     */
    public static function unreadableFilters(): array
    {
        return [
            'not an array' => ['nonsense', []],
            'null' => [null, []],
            'an integer' => [42, []],
            'an empty array' => [[], []],
            'nested deeper than one level' => [['research_field' => [['5']]], []],
            'an object' => [['research_field' => new \stdClass()], []],
            'a boolean' => [['research_field' => true], []],
            'readable next to unreadable' => [['research_field' => ['5', ['8']], 'degree' => '13'], [5, 13]],
        ];
    }

    /**
     * Not a uid any category can have, but the repository is the place that decides that -
     * quoting the list is what makes an arbitrary value safe there.
     */
    #[Test]
    public function aNegativeValueIsKept(): void
    {
        $this->assertSame([-1], $this->subject()->toUidList(['research_field' => '-1']));
    }

    #[Test]
    public function aNonNumericValueBecomesZero(): void
    {
        $this->assertSame([0], $this->subject()->toUidList(['research_field' => 'drop table']));
    }

    #[Test]
    public function noFilterCollectionIsAnEmptyFilterArgument(): void
    {
        $this->assertSame('', $this->subject()->toFilterArgument(null));
        $this->assertSame('', $this->subject()->toFilterArgument(new FilterCollection()));
    }

    #[Test]
    public function oneCategoryIsItsUid(): void
    {
        $this->assertSame('12', $this->subject()->toFilterArgument($this->filterCollection(12)));
    }

    /**
     * The categories end up in one list, in ascending order however they were attached -
     * so one selection has one URL. Their type is not read, so the categories here carry
     * none; the partner list's functional test submits categories of several types.
     */
    #[Test]
    public function categoriesAreOneAscendingList(): void
    {
        $this->assertSame('5,12,31', $this->subject()->toFilterArgument($this->filterCollection(31, 5, 12)));
    }

    /**
     * What a list plugin redirects to is read by the same plugin again: the argument has
     * to come back as the uids it was made of.
     */
    #[Test]
    public function theFilterArgumentReadsBackAsTheSameUids(): void
    {
        $filterArgument = $this->subject()->toFilterArgument($this->filterCollection(31, 5, 12));

        $this->assertSame([5, 12, 31], $this->subject()->toUidList(['categories' => $filterArgument]));
    }

    private function filterCollection(int ...$uids): FilterCollection
    {
        $categoryCollection = new CategoryCollection();
        foreach ($uids as $uid) {
            $categoryCollection->attach(new Category(uid: $uid, parentId: 0, title: 'Category ' . $uid));
        }

        return new FilterCollection($categoryCollection);
    }

    private function subject(): CategoryFilterNormalizer
    {
        return new CategoryFilterNormalizer();
    }
}
