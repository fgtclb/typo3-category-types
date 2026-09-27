<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Registry;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Exception\CategoryTypeExistException;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CategoryTypeRegistryTest extends UnitTestCase
{
    private function categoryType(
        string $identifier,
        string $group = 'testing',
        string $extensionKey = 'test_extension',
        int $priority = 0,
    ): CategoryType {
        return new CategoryType(
            identifier: $identifier,
            extensionKey: $extensionKey,
            title: ucfirst($identifier),
            group: $group,
            icon: 'EXT:' . $extensionKey . '/Resources/Public/Icons/' . $identifier . '.svg',
            priority: $priority,
        );
    }

    /**
     * A registry nobody attached anything to is a real state: only three extensions of
     * this repository ship a `Configuration/CategoryTypes.yaml`, so an installation
     * using `EXT:category_types` alone never fills it. `attach()` also returns early for
     * an empty argument list, which is what the loader passes in that case.
     */
    #[Test]
    public function freshRegistryReportsNoTypes(): void
    {
        $subject = new CategoryTypeRegistry();

        $this->assertSame([], $subject->getCategoryTypes());
        $this->assertSame([], $subject->getGroupedCategoryTypes());
        $this->assertSame([], $subject->toArray());
        $this->assertSame(['registry' => []], $subject->jsonSerialize());
    }

    #[Test]
    public function attachWithoutArgumentsLeavesTheRegistryUsable(): void
    {
        $subject = new CategoryTypeRegistry();
        $subject->attach();

        $this->assertSame([], $subject->getGroupedCategoryTypes());
    }

    /**
     * The documented failure mode of an unknown group. On a registry that never received
     * a type this used to be a fatal `Error` about the uninitialised property instead.
     */
    #[Test]
    public function unknownGroupIsReportedAsInvalidArgumentOnAFreshRegistry(): void
    {
        $subject = new CategoryTypeRegistry();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633304209);

        $subject->getCategoryTypesByGroup('testing');
    }

    #[Test]
    public function attachedTypesAreReturnedInAttachmentOrder(): void
    {
        $first = $this->categoryType('first');
        $second = $this->categoryType('second');

        $subject = new CategoryTypeRegistry();
        $subject->attach($first, $second);

        $this->assertSame([$first, $second], $subject->getCategoryTypes());
    }

    /**
     * The order every consumer of a group follows: the facts of a program, the filter
     * selects of a list, the page module summary and the type select of a category.
     */
    #[Test]
    public function typesOfAGroupAreOrderedByPriorityHighestFirst(): void
    {
        $low = $this->categoryType('low', group: 'programs');
        $high = $this->categoryType('high', group: 'programs', priority: 10);
        $middle = $this->categoryType('middle', group: 'programs', priority: 5);

        $subject = new CategoryTypeRegistry();
        $subject->attach($low, $high, $middle);

        $this->assertSame(['high' => $high, 'middle' => $middle, 'low' => $low], $subject->getCategoryTypesByGroup('programs'));
        $this->assertSame(['high', 'middle', 'low'], $subject->getCategoryTypeIdentifierByGroup('programs'));
        $this->assertSame(['programs' => ['high' => $high, 'middle' => $middle, 'low' => $low]], $subject->getGroupedCategoryTypes());
        $this->assertSame([$high, $middle, $low], $subject->getCategoryTypes());
        $this->assertSame(['registry' => [$high, $middle, $low]], $subject->jsonSerialize());
        $this->assertSame(
            ['high', 'middle', 'low'],
            array_column($subject->toArray()['programs'], 'identifier'),
        );
    }

    #[Test]
    public function typesOfEqualPriorityKeepTheirAttachmentOrder(): void
    {
        $unprioritised = $this->categoryType('unprioritised', group: 'programs');
        $first = $this->categoryType('first', group: 'programs', priority: 10);
        $second = $this->categoryType('second', group: 'programs', priority: 10);

        $subject = new CategoryTypeRegistry();
        $subject->attach($unprioritised, $first, $second);

        $this->assertSame(['first', 'second', 'unprioritised'], $subject->getCategoryTypeIdentifierByGroup('programs'));
    }

    #[Test]
    public function negativePriorityMovesATypeBehindTheOthers(): void
    {
        $subject = new CategoryTypeRegistry();
        $subject->attach(
            $this->categoryType('first', group: 'programs'),
            $this->categoryType('last', group: 'programs', priority: -10),
            $this->categoryType('second', group: 'programs'),
        );

        $this->assertSame(['first', 'second', 'last'], $subject->getCategoryTypeIdentifierByGroup('programs'));
    }

    /**
     * `attach()` may be called more than once, so a type attached later still takes the
     * place its priority gives it.
     */
    #[Test]
    public function typeAttachedLaterIsSortedIntoItsGroup(): void
    {
        $subject = new CategoryTypeRegistry();
        $subject->attach($this->categoryType('early', group: 'programs'));
        $subject->attach($this->categoryType('late', group: 'programs', priority: 10));

        $this->assertSame(['late', 'early'], $subject->getCategoryTypeIdentifierByGroup('programs'));
    }

    /**
     * The flat list is the groups one after the other, in the order each group was first
     * attached, and each group in its own order. The priority of `country` is higher
     * than any of `programs`, which a sort across all types would put first.
     */
    #[Test]
    public function flatListFollowsTheOrderOfTheGroups(): void
    {
        $degree = $this->categoryType('degree', group: 'programs');
        $country = $this->categoryType('country', group: 'partners', priority: 5);
        $location = $this->categoryType('location', group: 'programs');

        $subject = new CategoryTypeRegistry();
        $subject->attach($degree, $country, $location);

        $this->assertSame([$degree, $location, $country], $subject->getCategoryTypes());
    }

    #[Test]
    public function typesAreGroupedByTheirGroupAndKeyedByIdentifier(): void
    {
        $programs = $this->categoryType('field_of_study', group: 'programs');
        $partners = $this->categoryType('country', group: 'partners');

        $subject = new CategoryTypeRegistry();
        $subject->attach($programs, $partners);

        $this->assertSame(
            [
                'programs' => ['field_of_study' => $programs],
                'partners' => ['country' => $partners],
            ],
            $subject->getGroupedCategoryTypes(),
        );
    }

    /**
     * `CategoryType::$group` is a plain string with no default, so an empty one is what a
     * YAML file without a group produces. The registry files those under `default`.
     */
    #[Test]
    public function typeWithoutAGroupIsFiledUnderDefault(): void
    {
        $groupless = $this->categoryType('loose', group: '');

        $subject = new CategoryTypeRegistry();
        $subject->attach($groupless);

        $this->assertSame(['default' => ['loose' => $groupless]], $subject->getGroupedCategoryTypes());
    }

    #[Test]
    public function attachingTheSameIdentifierTwiceInOneGroupIsRejected(): void
    {
        $subject = new CategoryTypeRegistry();
        $subject->attach($this->categoryType('duplicate'));

        $this->expectException(CategoryTypeExistException::class);
        $this->expectExceptionCode(1678979375329);

        $subject->attach($this->categoryType('duplicate'));
    }

    /**
     * The types before the duplicate stay attached, in the flat list as much as in their
     * group, so the two never disagree.
     */
    #[Test]
    public function typesBeforeARejectedDuplicateStayAttached(): void
    {
        $existing = $this->categoryType('duplicate');
        $before = $this->categoryType('before', priority: 10);

        $subject = new CategoryTypeRegistry();
        $subject->attach($existing);
        try {
            $subject->attach($before, $this->categoryType('duplicate'));
            $this->fail('The duplicate was not rejected.');
        } catch (CategoryTypeExistException) {
        }

        $this->assertSame([$before, $existing], $subject->getCategoryTypes());
        $this->assertSame(['before', 'duplicate'], $subject->getCategoryTypeIdentifierByGroup('testing'));
        $this->assertTrue($subject->exists($before));
    }

    #[Test]
    public function theSameIdentifierIsAllowedInDifferentGroups(): void
    {
        $programs = $this->categoryType('topic', group: 'programs');
        $projects = $this->categoryType('topic', group: 'projects');

        $subject = new CategoryTypeRegistry();
        $subject->attach($programs, $projects);

        $this->assertSame($programs, $subject->getCategoryType('programs', 'topic'));
        $this->assertSame($projects, $subject->getCategoryType('projects', 'topic'));
    }

    #[Test]
    public function unknownTypeResolvesToNull(): void
    {
        $subject = new CategoryTypeRegistry();
        $subject->attach($this->categoryType('known'));

        $this->assertNull($subject->getCategoryType('testing', 'unknown'));
        $this->assertNull($subject->getCategoryType('unknown', 'known'));
        $this->assertNull((new CategoryTypeRegistry())->getCategoryType('testing', 'known'));
    }

    #[Test]
    public function typesOfAGroupAreReturnedKeyedByIdentifier(): void
    {
        $first = $this->categoryType('first', group: 'programs');
        $second = $this->categoryType('second', group: 'programs');

        $subject = new CategoryTypeRegistry();
        $subject->attach($first, $second, $this->categoryType('other', group: 'partners'));

        $this->assertSame(['first' => $first, 'second' => $second], $subject->getCategoryTypesByGroup('programs'));
        $this->assertSame(['first', 'second'], $subject->getCategoryTypeIdentifierByGroup('programs'));
    }

    #[Test]
    public function unknownGroupIsRejectedWhenAskingForIdentifiers(): void
    {
        $subject = new CategoryTypeRegistry();
        $subject->attach($this->categoryType('known', group: 'programs'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633304209);

        $subject->getCategoryTypeIdentifierByGroup('partners');
    }

    #[Test]
    public function attachedTypeIsReportedAsExisting(): void
    {
        $attached = $this->categoryType('attached');

        $subject = new CategoryTypeRegistry();
        $subject->attach($attached);

        $this->assertTrue($subject->exists($attached));
        $this->assertFalse($subject->exists($this->categoryType('different')));
    }

    #[Test]
    public function toArraySerialisesEveryTypeGroupedByItsGroup(): void
    {
        $subject = new CategoryTypeRegistry();
        $subject->attach($this->categoryType('field_of_study', group: 'programs', priority: 10));

        $this->assertSame(
            [
                'programs' => [
                    [
                        'identifier' => 'field_of_study',
                        'extensionKey' => 'test_extension',
                        'title' => 'Field_of_study',
                        'group' => 'programs',
                        'icon' => 'EXT:test_extension/Resources/Public/Icons/field_of_study.svg',
                        'priority' => 10,
                    ],
                ],
            ],
            $subject->toArray(),
        );
    }

    #[Test]
    public function jsonSerializeReturnsTheFlatRegistry(): void
    {
        $first = $this->categoryType('first', group: 'programs');
        $second = $this->categoryType('second', group: 'partners');

        $subject = new CategoryTypeRegistry();
        $subject->attach($first, $second);

        $this->assertSame(['registry' => [$first, $second]], $subject->jsonSerialize());
    }
}
