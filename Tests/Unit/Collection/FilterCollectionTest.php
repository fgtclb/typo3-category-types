<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Collection;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The filter collection wraps a `CategoryCollection` and is what the demand objects of
 * the consuming plugins carry — `{demand.filterCollection.filterCategories.<type>}` in
 * the shipped `DemandCategories.html` partials.
 *
 * Unlike the wrapped collection it answers `offsetExists()` through the same lookup as
 * `offsetGet()`, so both agree on what exists.
 */
final class FilterCollectionTest extends UnitTestCase
{
    private function categoryCollectionWithTypes(string ...$typeIdentifiers): CategoryCollection
    {
        $categoryCollection = new CategoryCollection();
        $categoryCollection->setTypeIdentifiers($typeIdentifiers);

        return $categoryCollection;
    }

    #[Test]
    public function collectionIsCreatedEmptyWhenNoneIsGiven(): void
    {
        $subject = new FilterCollection();

        $this->assertCount(0, $subject->getFilterCategories());
    }

    #[Test]
    public function givenCollectionIsKept(): void
    {
        $categoryCollection = $this->categoryCollectionWithTypes('research_field');

        $this->assertSame($categoryCollection, (new FilterCollection($categoryCollection))->getFilterCategories());
    }

    #[Test]
    public function knownTypeIsReportedAsExisting(): void
    {
        $subject = new FilterCollection($this->categoryCollectionWithTypes('research_field'));

        $this->assertTrue($subject->offsetExists('research_field'));
        $this->assertTrue(isset($subject['research_field']));
    }

    /**
     * Both accessors normalise camel case, which is what the partials rely on when they
     * pass a type identifier straight into the template expression.
     */
    #[Test]
    public function knownTypeIsReachableInCamelCase(): void
    {
        $subject = new FilterCollection($this->categoryCollectionWithTypes('research_field'));

        $this->assertTrue($subject->offsetExists('researchField'));
        $this->assertSame([], $subject->offsetGet('researchField'));
    }

    #[Test]
    public function unknownTypeIsReportedAsMissing(): void
    {
        $subject = new FilterCollection($this->categoryCollectionWithTypes('research_field'));

        $this->assertFalse($subject->offsetExists('country'));
        $this->assertFalse($subject->offsetGet('country'));
    }

    /**
     * Only the "unknown category type" exception is swallowed. Anything else has to
     * travel, so a different defect in the wrapped collection cannot be mistaken for an
     * absent filter.
     */
    #[Test]
    public function unrelatedInvalidArgumentExceptionIsNotSwallowed(): void
    {
        $categoryCollection = new class () extends CategoryCollection {
            public function getCategoriesByTypeName(string $typeIdentifier): array
            {
                throw new \InvalidArgumentException('Something else went wrong', 1234567890);
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1234567890);

        (new FilterCollection($categoryCollection))->offsetExists('research_field');
    }

    #[Test]
    public function writingThroughArrayAccessIsRejected(): void
    {
        $subject = new FilterCollection();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633632593);

        $subject->offsetSet('research_field', []);
    }

    #[Test]
    public function unsettingThroughArrayAccessIsRejected(): void
    {
        $subject = new FilterCollection();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633656658);

        $subject->offsetUnset('research_field');
    }

    #[Test]
    public function stringRepresentationIsTheClassName(): void
    {
        $this->assertSame(FilterCollection::class, (string)new FilterCollection());
    }
}
