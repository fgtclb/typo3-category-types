<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\CategoryTypes\Imaging\CategoryTypeIconProviderResolver;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    protected static function getPackageName(): string
    {
        return 'fgtclb/category-types';
    }

    public function getFactories(): array
    {
        return [
            'category-types.icons' => static::addIcons(...),
        ];
    }

    public function getExtensions(): array
    {
        return [
            ListenerProvider::class => static::addEventListeners(...),
        ] + parent::getExtensions();
    }

    /**
     * Category type icons are registered here rather than in a `Configuration/Icons.php`,
     * because the set of them is whatever the loaded extensions declare in their
     * `Configuration/CategoryTypes.yaml`. Every loaded extension contributes, including
     * site packages this repository knows nothing about, so the registrar must not decide
     * for them what happens to their files.
     *
     * A type therefore asks for it, with `inlineIcon: true` in its own
     * `Configuration/CategoryTypes.yaml`. Only then is an SVG registered with
     * {@see CurrentColorSvgIconProvider} of EXT:academic_base, which inlines the file in
     * both markups so it follows the colour of the text around it. Without the flag the
     * icon keeps what core detects for the file - the core `SvgIconProvider`, whose
     * default markup is an `<img>`. That is the conservative answer for a file nobody here
     * has drawn for inlining: an inlined SVG is part of the document, so its `id`
     * attributes and its `<style>` rules are global and collide with every other inlined
     * icon on the page, and its content is executed rather than rendered as an image.
     *
     * A bitmap has no such option in either case and always keeps what core detected.
     * {@see CategoryTypeIconProviderResolver} applies both rules, here and for the
     * frontend icon registry.
     *
     * A group declared with an icon in the `groups:` section is registered the same way,
     * under `category_types_group.<identifier>`.
     *
     * The frontend icon registry of EXT:academic_base receives the same icons from
     * {@see \FGTCLB\CategoryTypes\EventListener\AddCategoryTypeFrontendIcons}, with the
     * frontend file and flag of each declaration.
     */
    public static function addIcons(ContainerInterface $container): \Closure
    {
        return static function (BootCompletedEvent $event) use ($container): void {
            $iconRegistry = $container->get(IconRegistry::class);
            $iconProviderResolver = $container->get(CategoryTypeIconProviderResolver::class);

            $categoryTypeRegistry = $container->get(CategoryTypeRegistry::class);
            $categoryTypes = $categoryTypeRegistry->getCategoryTypes();

            foreach ($categoryTypes as $categoryType) {
                $iconProviderClassName = $iconProviderResolver->resolve($categoryType->getIcon(), $categoryType->isInlineIcon());

                $iconRegistry->registerIcon(
                    $categoryType->getIconIdentifier(),
                    $iconProviderClassName,
                    [
                        'source' => $categoryType->getIcon(),
                    ]
                );
            }

            // A declared group icon follows the same rule, under `category_types_group.<identifier>`.
            foreach ($categoryTypeRegistry->getGroups() as $group) {
                if ($group->getIcon() === '') {
                    continue;
                }
                $iconProviderClassName = $iconProviderResolver->resolve($group->getIcon(), $group->isInlineIcon());

                $iconRegistry->registerIcon(
                    $group->getIconIdentifier(),
                    $iconProviderClassName,
                    [
                        'source' => $group->getIcon(),
                    ]
                );
            }
        };
    }

    public static function addEventListeners(ContainerInterface $container, ListenerProvider $listenerProvider): ListenerProvider
    {
        $listenerProvider->addListener(BootCompletedEvent::class, 'category-types.icons');
        return $listenerProvider;
    }
}
