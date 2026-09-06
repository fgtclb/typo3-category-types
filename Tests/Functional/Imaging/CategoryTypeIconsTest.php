<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Imaging;

use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * Covers the icon registration of {@see \FGTCLB\CategoryTypes\ServiceProvider::addIcons()}.
 *
 * Category type icons are not registered from a `Configuration/Icons.php` - the set of them
 * is whatever the loaded extensions declare in their `Configuration/CategoryTypes.yaml`, and
 * that includes site packages this repository knows nothing about. Inlining is therefore
 * opt in, per type, with `inlineIcon: true`: a file nobody here has drawn for inlining keeps
 * the core provider and the `<img>` rendering it has always had, because an inlined SVG is
 * part of the document and its `id` attributes and `<style>` rules collide with every other
 * inlined icon on the page.
 *
 * Four branches, and all four are needed. Without the opted-out case the opted-in assertions
 * would also pass for a registrar that inlines everything, which is exactly the defect this
 * test exists for; without the bitmap case they would pass for one that ignores what core
 * detected; and a missing file must degrade to empty markup rather than an exception.
 *
 * Everything asserted here comes from the `test_category_types_icons` fixture extension:
 * `EXT:category_types` registers no category type of its own.
 */
final class CategoryTypeIconsTest extends AbstractCategoryTypesTestCase
{
    use ColourSchemeAwareIconsTrait;

    private const IDENTIFIER_VECTOR = 'category_types.testicons.vector';
    private const IDENTIFIER_PLAIN = 'category_types.testicons.plain';
    private const IDENTIFIER_BITMAP = 'category_types.testicons.bitmap';
    private const IDENTIFIER_MISSING = 'category_types.testicons.missing';

    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-icons');
        parent::setUp();
    }

    #[Test]
    public function optedInCategoryTypeIconIsRegisteredWithTheColourSchemeAwareProvider(): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider(self::IDENTIFIER_VECTOR);
    }

    #[Test]
    public function optedInCategoryTypeIconIsInlinedInBothMarkups(): void
    {
        $this->assertIconIsInlinedInBothMarkups(self::IDENTIFIER_VECTOR);
    }

    #[Test]
    public function optedInCategoryTypeIconMarkupFollowsTheTextColour(): void
    {
        $this->assertIconMarkupFollowsTheTextColour(self::IDENTIFIER_VECTOR);
    }

    #[Test]
    public function renderedOptedInCategoryTypeIconCarriesItsIdentifier(): void
    {
        $this->assertRenderedIconCarriesItsIdentifier(self::IDENTIFIER_VECTOR);
    }

    /**
     * The type that does not ask for it keeps the core provider, so its default markup is
     * the `<img>` it has always been and the colours, the `id` and the `<style>` of the
     * file never enter the document. That is what makes the two Illustrator defaults
     * `id="SVGID_1_"` and `.st0` in the fixture file harmless.
     */
    #[Test]
    public function categoryTypeIconWithoutOptInKeepsTheCoreProvider(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);

        $this->assertTrue($iconRegistry->isRegistered(self::IDENTIFIER_PLAIN));
        $this->assertSame(
            SvgIconProvider::class,
            $iconRegistry->getIconConfigurationByIdentifier(self::IDENTIFIER_PLAIN)['provider'] ?? null,
        );

        $markup = $this->getColourSchemeAwareIcon(self::IDENTIFIER_PLAIN)->getMarkup();
        $this->assertStringStartsWith('<img', $markup);
        $this->assertStringNotContainsString('SVGID_1_', $markup);
        $this->assertStringNotContainsString('#ff0000', $markup);
    }

    /**
     * A bitmap cannot be inlined. The fixture type asks for it all the same, so this pins
     * that the flag never overrides what {@see IconRegistry::detectIconProvider()} answered.
     */
    #[Test]
    public function bitmapCategoryTypeIconKeepsTheDetectedProviderDespiteTheOptIn(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);

        $this->assertTrue($iconRegistry->isRegistered(self::IDENTIFIER_BITMAP));
        $this->assertSame(
            BitmapIconProvider::class,
            $iconRegistry->getIconConfigurationByIdentifier(self::IDENTIFIER_BITMAP)['provider'] ?? null,
        );
        $this->assertStringContainsString(
            '<img',
            $this->getColourSchemeAwareIcon(self::IDENTIFIER_BITMAP)->getMarkup(),
        );
    }

    /**
     * A YAML file can name a file that is not there. That has to stay an empty icon rather
     * than an exception on either core, and the identifier still has to resolve to itself.
     */
    #[Test]
    public function missingCategoryTypeIconFileRendersEmptyMarkup(): void
    {
        $icon = $this->getColourSchemeAwareIcon(self::IDENTIFIER_MISSING);

        $this->assertSame(self::IDENTIFIER_MISSING, $icon->getIdentifier());
        $this->assertSame('', $icon->getMarkup());
        $this->assertSame('', $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
        $this->assertStringNotContainsString('default-not-found', $icon->render());
    }
}
