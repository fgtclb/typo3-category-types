<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Routing\Aspect;

use FGTCLB\CategoryTypes\Routing\Aspect\CategoryFilterMapper;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Routing\Aspect\AspectFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The routing aspect `CategoryFilterMapper`, built the way a route enhancer builds it: by
 * core's `AspectFactory`, from aspect settings, for one language of a site.
 *
 * The group `testing` with its types `testing_first` and `testing_second` comes from the
 * `test_category_types_group` fixture extension, the group `late` with `testing_late` from
 * `test_category_types_undeclared_group`.
 *
 * Categories: Europe (12, German "Europa", Swiss "Europa (CH)"), Berlin (14, German
 * "Berlin (DE)") and a second Berlin (15), "?!" (16), a hidden (17) and a deleted (18) one,
 * Elsewhere (19) of the group `late`, one without a type (20), "Research / Teaching" (21),
 * Asia (22) for all languages and University (31), whose German translation is hidden.
 * The Swiss language falls back to German, then to English.
 */
final class CategoryFilterMapperTest extends AbstractCategoryTypesTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
        'CH' => ['id' => 2, 'title' => 'Schweiz', 'locale' => 'de_CH.UTF8', 'iso' => 'de', 'hrefLang' => 'de-CH', 'direction' => ''],
    ];

    private const EMPTY_VALUE_SETTINGS = [
        'emptyValue' => 'any',
        'localeMap' => [
            ['locale' => 'de_DE.*', 'value' => 'alle'],
            ['locale' => 'de_CH.*', 'value' => 'saemtliche'],
            // Matches the Swiss language too, but comes after the item that does.
            ['locale' => 'de_.*', 'value' => 'jede'],
        ],
    ];

    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-group', 'tests/category-types-undeclared-group');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryFilterMapper/categories.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
                $this->buildLanguageConfiguration(identifier: 'CH', base: '/ch/', fallbackIdentifiers: ['DE', 'EN']),
            ],
        );
    }

    #[Test]
    public function aRouteEnhancerConfigurationBuildsTheMapper(): void
    {
        $this->assertInstanceOf(CategoryFilterMapper::class, $this->createMapper(0));
    }

    /**
     * @return \Generator<string, array{0: array<string, mixed>, 1: int}>
     */
    public static function invalidSettingsDataProvider(): \Generator
    {
        yield 'no group' => [[], 1790760601];
        yield 'an empty group' => [['group' => ''], 1790760601];
        yield 'a group that is not a string' => [['group' => ['testing']], 1790760601];
        yield 'an empty emptyValue' => [['group' => 'testing', 'emptyValue' => ''], 1790760602];
        yield 'an emptyValue with a slash' => [['group' => 'testing', 'emptyValue' => 'all/none'], 1790760602];
        yield 'an emptyValue with a comma' => [['group' => 'testing', 'emptyValue' => 'all,none'], 1790760602];
        yield 'an emptyValue that reads as a category' => [['group' => 'testing', 'emptyValue' => 'all-12'], 1790760602];
        yield 'a localeMap value that reads as a category' => [['group' => 'testing', 'localeMap' => [['locale' => 'de_DE.*', 'value' => 'alle-12']]], 1790760604];
        yield 'a localeMap that is not a list' => [['group' => 'testing', 'localeMap' => 'alle'], 1790760603];
        yield 'a localeMap item without a value' => [['group' => 'testing', 'localeMap' => [['locale' => 'de_DE.*']]], 1790760604];
        yield 'a localeMap item with an empty value' => [['group' => 'testing', 'localeMap' => [['locale' => 'de_DE.*', 'value' => '']]], 1790760604];
        yield 'a group without category types' => [['group' => 'unknown'], 1790760605];
    }

    /**
     * @param array<string, mixed> $settings
     */
    #[DataProvider('invalidSettingsDataProvider')]
    #[Test]
    public function invalidSettingsAreRefused(array $settings, int $expectedCode): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedCode);

        $this->createMapper(0, $settings);
    }

    #[Test]
    public function aFallbackValueIsOffered(): void
    {
        $mapper = $this->createMapper(0, ['group' => 'testing', 'fallbackValue' => '']);

        $this->assertTrue($mapper->hasFallbackValue());
        $this->assertSame('', $mapper->getFallbackValue());
    }

    /**
     * @return \Generator<string, array{0: int, 1: string, 2: string}>
     */
    public static function generatedSegmentsDataProvider(): \Generator
    {
        yield 'one category' => [0, '12', 'europe-12'];
        yield 'two categories in list order' => [0, '31,12', 'university-31,europe-12'];
        yield 'the same two the other way round' => [0, '12,31', 'europe-12,university-31'];
        yield 'a translated title' => [1, '12', 'europa-12'];
        yield 'a hidden translation keeps the default title' => [1, '12,31', 'europa-12,university-31'];
        yield 'the own translation of a fallback language' => [2, '12', 'europa-ch-12'];
        yield 'the translation of the language fallen back to' => [2, '14', 'berlin-de-14'];
        yield 'a category of all languages' => [1, '22', 'asia-22'];
        yield 'a title that sanitises to nothing' => [0, '16', 'category-16'];
        yield 'a slash in the title' => [0, '21', 'research-teaching-21'];
        yield 'two categories with the same title' => [0, '14,15', 'berlin-14,berlin-15'];
    }

    #[DataProvider('generatedSegmentsDataProvider')]
    #[Test]
    public function filterValuesGenerateReadableSegments(int $languageId, string $value, string $expected): void
    {
        $this->assertSame($expected, $this->createMapper($languageId)->generate($value));
    }

    /**
     * @return \Generator<string, array{0: int, 1: string}>
     */
    public static function emptyValueDataProvider(): \Generator
    {
        yield 'English, no item of the map matches' => [0, 'any'];
        yield 'German' => [1, 'alle'];
        yield 'Swiss, the first of two matching items' => [2, 'saemtliche'];
    }

    #[DataProvider('emptyValueDataProvider')]
    #[Test]
    public function anEmptyFilterGeneratesTheTokenOfTheLanguage(int $languageId, string $expected): void
    {
        $mapper = $this->createMapper($languageId, ['group' => 'testing', ...self::EMPTY_VALUE_SETTINGS]);

        $this->assertSame($expected, $mapper->generate(''));
        $this->assertSame('', $mapper->resolve($expected));
    }

    #[Test]
    public function theEmptyTokenIsAllWithoutConfiguration(): void
    {
        $mapper = $this->createMapper(1);

        $this->assertSame('all', $mapper->generate(''));
        $this->assertSame('', $mapper->resolve('all'));
    }

    #[Test]
    public function theTokenOfAnotherLanguageDoesNotResolve(): void
    {
        $mapper = $this->createMapper(0, ['group' => 'testing', ...self::EMPTY_VALUE_SETTINGS]);

        $this->assertNull($mapper->resolve('alle'));
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function valuesWithoutSegmentDataProvider(): \Generator
    {
        yield 'a category of another group' => ['19'];
        yield 'a category without a type' => ['20'];
        yield 'a hidden category' => ['17'];
        yield 'a deleted category' => ['18'];
        yield 'an unknown category' => ['999'];
        yield 'a translation instead of its category' => ['112'];
        yield 'one unusable category among usable ones' => ['12,19'];
        yield 'a value that is no uid' => ['europe'];
        yield 'zero' => ['0'];
        yield 'a negative uid' => ['-12'];
        yield 'a leading zero' => ['012'];
        yield 'an empty part' => ['12,'];
        yield 'a space' => ['12, 31'];
        yield 'a uid beyond the column' => ['2147483648'];
        yield 'a trailing line break' => ["12\n"];
    }

    /**
     * A value that cannot be mapped generates no segment, so the link keeps the plain query
     * argument rather than getting a path that would not resolve.
     */
    #[DataProvider('valuesWithoutSegmentDataProvider')]
    #[Test]
    public function valuesThatCannotBeMappedGenerateNothing(string $value): void
    {
        $this->assertNull($this->createMapper(0)->generate($value));
    }

    /**
     * @return \Generator<string, array{0: int, 1: string}>
     */
    public static function roundTripDataProvider(): \Generator
    {
        foreach (self::generatedSegmentsDataProvider() as $name => [$languageId, $value]) {
            yield $name => [$languageId, $value];
        }
    }

    #[DataProvider('roundTripDataProvider')]
    #[Test]
    public function everyGeneratedSegmentResolvesToItsValue(int $languageId, string $value): void
    {
        $mapper = $this->createMapper($languageId);

        $this->assertSame($value, $mapper->resolve((string)$mapper->generate($value)));
    }

    /**
     * Only the uid is read: a URL generated before the category was renamed, or in
     * another language, resolves to the same category.
     */
    #[Test]
    public function aSegmentResolvesByUidWhateverItsTitle(): void
    {
        $mapper = $this->createMapper(0);

        $this->assertSame('12', $mapper->resolve('europa-12'));
        $this->assertSame('12,31', $mapper->resolve('europaeische-union-12,hochschule-31'));
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function unresolvableSegmentsDataProvider(): \Generator
    {
        yield 'a category of another group' => ['elsewhere-19'];
        yield 'a category without a type' => ['without-type-20'];
        yield 'a hidden category' => ['hidden-17'];
        yield 'a deleted category' => ['deleted-18'];
        yield 'an unknown category' => ['unknown-999'];
        yield 'a translation instead of its category' => ['europa-112'];
        yield 'one unusable part among usable ones' => ['europe-12,elsewhere-19'];
        yield 'a bare uid' => ['12'];
        yield 'a slug without uid' => ['europe'];
        yield 'a slug with a dash but no uid' => ['europe-'];
        yield 'a uid with a leading zero' => ['europe-012'];
        yield 'a uid of zero' => ['europe-0'];
        yield 'an empty part' => ['europe-12,'];
        yield 'two commas' => ['europe-12,,university-31'];
        yield 'a character a slug never has' => ['eu.rope-12'];
        yield 'a uid beyond the column' => ['europe-2147483648'];
        yield 'a uid longer than any integer' => ['europe-99999999999999999999'];
        yield 'an empty segment' => [''];
        yield 'a trailing line break' => ["europe-12\n"];
    }

    #[DataProvider('unresolvableSegmentsDataProvider')]
    #[Test]
    public function aSegmentThatDoesNotMapDoesNotResolve(string $segment): void
    {
        $this->assertNull($this->createMapper(0)->resolve($segment));
    }

    #[Test]
    public function twoCategoriesWithTheSameTitleResolveToTheirOwnUid(): void
    {
        $mapper = $this->createMapper(0);

        $this->assertSame('14', $mapper->resolve('berlin-14'));
        $this->assertSame('15', $mapper->resolve('berlin-15'));
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createMapper(int $languageId, array $settings = ['group' => 'testing']): CategoryFilterMapper
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme');
        // Core creates the factory itself in `PageRouter`, it is no public service.
        $aspects = GeneralUtility::makeInstance(AspectFactory::class)->createAspects(
            ['categories' => ['type' => 'CategoryFilterMapper', ...$settings]],
            $site->getLanguageById($languageId),
            $site,
        );
        $mapper = $aspects['categories'] ?? null;
        $this->assertInstanceOf(CategoryFilterMapper::class, $mapper);

        return $mapper;
    }
}
