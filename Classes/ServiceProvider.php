<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes;

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

    public static function addIcons(ContainerInterface $container): \Closure
    {
        return static function (BootCompletedEvent $event) use ($container): void {
            $iconRegistry = $container->get(IconRegistry::class);

            $categoryTypeRegistry = $container->get(CategoryTypeRegistry::class);
            $categoryTypes = $categoryTypeRegistry->getCategoryTypes();

            foreach ($categoryTypes as $categoryType) {
                $iconProviderClassName = $iconRegistry->detectIconProvider($categoryType->getIcon());

                $iconRegistry->registerIcon(
                    $categoryType->getIconIdentifier(),
                    $iconProviderClassName,
                    [
                        'source' => $categoryType->getIcon(),
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
