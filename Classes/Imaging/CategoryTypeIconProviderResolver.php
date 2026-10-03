<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Imaging;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;

/**
 * Answers the icon provider of a category type or group icon file, for the icon registry
 * of the backend and for the frontend icon registry of EXT:academic_base alike.
 *
 * The provider is the one core detects for the file, by the rule of
 * `IconRegistry::detectIconProvider()`, which is the same on TYPO3 v13 and v14: a file
 * whose name ends in `svg` gets {@see SvgIconProvider}, anything else
 * {@see BitmapIconProvider}. An SVG that asks for inlining gets
 * {@see CurrentColorSvgIconProvider} instead, a bitmap keeps its provider whatever the
 * flag says. The rule is repeated here rather than taken from the core registry, because
 * asking that registry builds it with every core icon, which is what the frontend
 * registry exists to avoid.
 *
 * Public, because the `BootCompletedEvent` closure of {@see \FGTCLB\CategoryTypes\ServiceProvider}
 * takes it from the container.
 *
 * @internal not part of public API.
 */
#[Autoconfigure(public: true)]
final readonly class CategoryTypeIconProviderResolver
{
    /**
     * @return class-string<IconProviderInterface>
     */
    public function resolve(string $file, bool $inline): string
    {
        if (!str_ends_with(strtolower($file), 'svg')) {
            return BitmapIconProvider::class;
        }
        return $inline ? CurrentColorSvgIconProvider::class : SvgIconProvider::class;
    }
}
