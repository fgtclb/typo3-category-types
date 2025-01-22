<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes;

use Closure;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Configuration\Event\BeforeTcaOverridesEvent;
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
            'category-types.tca' => static::addTca(...),
            'category-types.typoscript' => static::addTypoScript(...),
        ];
    }

    public function getExtensions(): array
    {
        return [
            ListenerProvider::class => static::addEventListeners(...),
        ] + parent::getExtensions();
    }

    public static function addIcons(ContainerInterface $container): Closure
    {
        return static function (BootCompletedEvent $event) use ($container) {
            $iconRegistry = $container->get(IconRegistry::class);

            $categoryTypeRegistry = $container->get(CategoryTypeRegistry::class);
            $categoryTypes = $categoryTypeRegistry->getCategoryTypes();

            /*
            foreach ($iconsFromPackages as $icon => $options) {
                $provider = $options['provider'] ?? null;
                unset($options['provider']);
                $options ??= [];
                if ($provider === null && ($options['source'] ?? false)) {
                    $provider = $iconRegistry->detectIconProvider($options['source']);
                }
                if ($provider === null) {
                    continue;
                }
                $iconRegistry->registerIcon($icon, $provider, $options);
            }
            */
        };
    }

    public static function addTca(ContainerInterface $container): Closure
    {
        return static function (BeforeTcaOverridesEvent $event) use ($container) {
        };
    }

    public static function addTypoScript(ContainerInterface $container): Closure
    {
        return static function (BootCompletedEvent $event) use ($container) {
        };
    }

    public static function addEventListeners(ContainerInterface $container, ListenerProvider $listenerProvider): ListenerProvider
    {
        $listenerProvider->addListener(BootCompletedEvent::class, 'category-types.icons');
        $listenerProvider->addListener(BootCompletedEvent::class, 'category-types.typoscript');
        $listenerProvider->addListener(BeforeTcaOverridesEvent::class, 'category-types.tca');
        return $listenerProvider;
    }
}
