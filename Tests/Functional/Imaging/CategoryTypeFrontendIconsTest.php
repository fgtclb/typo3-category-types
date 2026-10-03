<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconFactory;
use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * Covers which file and which provider a category type or group icon gets in the icon
 * registry of the backend and in the frontend icon registry of EXT:academic_base: the
 * backend registration of {@see \FGTCLB\CategoryTypes\ServiceProvider::addIcons()} and the
 * contribution of {@see \FGTCLB\CategoryTypes\EventListener\AddCategoryTypeFrontendIcons}.
 *
 * Everything asserted here comes from the `test_category_types_frontend_icons` fixture
 * extension, one type per case of the spec `typo3-category-types/category-type-icons`.
 */
final class CategoryTypeFrontendIconsTest extends AbstractCategoryTypesTestCase
{
    use FrontendIconsAssertionTrait;

    private const ICONS = 'EXT:test_category_types_frontend_icons/Resources/Public/Icons/';

    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-frontend-icons');
        parent::setUp();
    }

    /**
     * @return array{provider: string|null, source: string|null}
     */
    private function backendRegistration(string $identifier): array
    {
        $iconRegistry = $this->get(IconRegistry::class);
        $this->assertTrue($iconRegistry->isRegistered($identifier), sprintf('"%s" is not a backend icon.', $identifier));
        $configuration = $iconRegistry->getIconConfigurationByIdentifier($identifier);
        return [
            'provider' => $configuration['provider'] ?? null,
            'source' => $configuration['options']['source'] ?? null,
        ];
    }

    /**
     * @return array{provider: string|null, source: string|null}
     */
    private function frontendRegistration(string $identifier): array
    {
        $configuration = $this->get(FrontendIconRegistry::class)->getIconConfiguration($identifier);
        $this->assertNotNull($configuration, sprintf('"%s" is not a frontend icon.', $identifier));
        return [
            'provider' => $configuration['provider'],
            'source' => $configuration['options']['source'] ?? null,
        ];
    }

    #[Test]
    public function oneFileWithoutAFlagIsTheSameImageInBothRegistries(): void
    {
        $this->assertIconIsRegisteredInBothRegistries('category_types.frontend.same');
        $this->assertSame(
            ['provider' => SvgIconProvider::class, 'source' => self::ICONS . 'Plain.svg'],
            $this->frontendRegistration('category_types.frontend.same'),
        );
        $this->assertStringStartsWith('<img', $this->getFrontendIcon('category_types.frontend.same')->getMarkup());
        $this->assertRenderedFrontendIconCarriesItsIdentifier('category_types.frontend.same');
    }

    #[Test]
    public function oneFileInlinedEverywhereIsInlinedInBothRegistries(): void
    {
        $this->assertIconIsRegisteredInBothRegistries('category_types.frontend.inlined');
        $this->assertFrontendIconIsRegisteredWithProvider('category_types.frontend.inlined', CurrentColorSvgIconProvider::class);
        $this->assertFrontendIconMarkupFollowsTheTextColour('category_types.frontend.inlined');
    }

    #[Test]
    public function aFrontendFileWithoutItsOwnFlagIsAnImageInTheFrontendOnly(): void
    {
        $this->assertSame(
            ['provider' => CurrentColorSvgIconProvider::class, 'source' => self::ICONS . 'Vector.svg'],
            $this->backendRegistration('category_types.frontend.dedicated'),
        );
        $this->assertSame(
            ['provider' => SvgIconProvider::class, 'source' => self::ICONS . 'Frontend.svg'],
            $this->frontendRegistration('category_types.frontend.dedicated'),
        );
        $this->assertStringStartsWith('<img', $this->getFrontendIcon('category_types.frontend.dedicated')->getMarkup());
    }

    #[Test]
    public function aFrontendFileWithItsOwnFlagIsInlinedInTheFrontend(): void
    {
        $this->assertSame(
            ['provider' => SvgIconProvider::class, 'source' => self::ICONS . 'Plain.svg'],
            $this->backendRegistration('category_types.frontend.dedicatedinline'),
        );
        $this->assertSame(
            ['provider' => CurrentColorSvgIconProvider::class, 'source' => self::ICONS . 'Frontend.svg'],
            $this->frontendRegistration('category_types.frontend.dedicatedinline'),
        );
        $this->assertFrontendIconMarkupFollowsTheTextColour('category_types.frontend.dedicatedinline');
    }

    #[Test]
    public function theSameFileCanBeInlinedInTheBackendOnly(): void
    {
        $this->assertSame(
            ['provider' => CurrentColorSvgIconProvider::class, 'source' => self::ICONS . 'Vector.svg'],
            $this->backendRegistration('category_types.frontend.backendinline'),
        );
        $this->assertSame(
            ['provider' => SvgIconProvider::class, 'source' => self::ICONS . 'Vector.svg'],
            $this->frontendRegistration('category_types.frontend.backendinline'),
        );
    }

    #[Test]
    public function aBitmapIsAnImageWhateverTheFlagSays(): void
    {
        $this->assertSame(
            ['provider' => BitmapIconProvider::class, 'source' => self::ICONS . 'Bitmap.png'],
            $this->frontendRegistration('category_types.frontend.bitmap'),
        );
        $this->assertStringStartsWith('<img', $this->getFrontendIcon('category_types.frontend.bitmap')->getMarkup());
    }

    /**
     * Spec "A frontend only replacement": the fixture registers the identifier in its
     * `Configuration/FrontendIcons.php`, as a site package does.
     */
    #[Test]
    public function aFrontendIconsFileReplacesTheIconInTheFrontendOnly(): void
    {
        $this->assertSame(
            ['provider' => CurrentColorSvgIconProvider::class, 'source' => self::ICONS . 'SiteReplaced.svg'],
            $this->frontendRegistration('category_types.frontend.replaced'),
        );
        $this->assertSame(
            ['provider' => CurrentColorSvgIconProvider::class, 'source' => self::ICONS . 'Vector.svg'],
            $this->backendRegistration('category_types.frontend.replaced'),
        );
    }

    /**
     * Spec "A type declared without an icon": no frontend entry, so the frontend renders its
     * placeholder for an unknown icon instead of the exception the core provider throws for
     * an empty source.
     */
    #[Test]
    public function aTypeWithoutAFileHasNoFrontendIconAndRendersThePlaceholder(): void
    {
        $this->assertFalse($this->get(FrontendIconRegistry::class)->isRegistered('category_types.frontend.none'));

        $rendered = $this->get(FrontendIconFactory::class)->getIcon('category_types.frontend.none', IconSize::SMALL)->render();

        $this->assertStringContainsString('data-identifier="default-not-found"', $rendered);
    }

    #[Test]
    public function aGroupIconHasItsOwnFrontendFile(): void
    {
        $this->assertSame(
            ['provider' => SvgIconProvider::class, 'source' => self::ICONS . 'Plain.svg'],
            $this->backendRegistration('category_types_group.frontend'),
        );
        $this->assertSame(
            ['provider' => CurrentColorSvgIconProvider::class, 'source' => self::ICONS . 'Frontend.svg'],
            $this->frontendRegistration('category_types_group.frontend'),
        );
        $this->assertRenderedFrontendIconCarriesItsIdentifier('category_types_group.frontend');
    }

    /**
     * Spec "A group named group": the type `collision` of the group `group` and the group
     * `collision` both keep their own file, in both registries. Under the earlier group
     * identifier `category_types.group.<group>` the two were the same identifier, and the
     * group icon replaced the type icon.
     */
    #[Test]
    public function aGroupNamedGroupDoesNotCollideWithTheTypesOfAnotherGroup(): void
    {
        $this->assertIconIsRegisteredInBothRegistries('category_types.group.collision');
        $this->assertSame(self::ICONS . 'Frontend.svg', $this->frontendRegistration('category_types.group.collision')['source']);

        $this->assertIconIsRegisteredInBothRegistries('category_types_group.collision');
        $this->assertSame(self::ICONS . 'Plain.svg', $this->frontendRegistration('category_types_group.collision')['source']);

        $this->assertIconIsRegisteredInBothRegistries('category_types_group.group');
        $this->assertSame(self::ICONS . 'Vector.svg', $this->frontendRegistration('category_types_group.group')['source']);
    }

    #[Test]
    public function theEarlierGroupIdentifierIsGone(): void
    {
        $this->assertFalse($this->get(IconRegistry::class)->isRegistered('category_types.group.frontend'));
        $this->assertFalse($this->get(FrontendIconRegistry::class)->isRegistered('category_types.group.frontend'));
    }
}
