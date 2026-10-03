<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\EventListener;

use FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent;
use FGTCLB\CategoryTypes\Imaging\CategoryTypeIconProviderResolver;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Contributes the icon of every category type and group to the frontend icon registry of
 * EXT:academic_base, under the identifier it has in the icon registry of the backend. The
 * frontend shows `frontendIcon`, or `icon` when none is declared, see
 * {@see \FGTCLB\CategoryTypes\Domain\Model\CategoryType::getFrontendIcon()}.
 *
 * A type or group without a frontend file contributes nothing, so the frontend answers
 * with its placeholder for an unknown icon rather than with the exception core throws for
 * an empty source. The registry is cached with the system caches, so this runs when it is
 * built, not per request. An entry of a `Configuration/FrontendIcons.php` with the same
 * identifier replaces what is contributed here.
 *
 * @internal not part of public API.
 */
#[AsEventListener(identifier: 'category-types/add-category-type-frontend-icons')]
final readonly class AddCategoryTypeFrontendIcons
{
    public function __construct(
        private CategoryTypeRegistry $categoryTypeRegistry,
        private CategoryTypeIconProviderResolver $iconProviderResolver,
    ) {}

    public function __invoke(CollectFrontendIconsEvent $event): void
    {
        foreach ($this->categoryTypeRegistry->getCategoryTypes() as $categoryType) {
            $this->addIcon(
                $event,
                $categoryType->getIconIdentifier(),
                $categoryType->getFrontendIcon(),
                $categoryType->isFrontendInlineIcon(),
            );
        }
        foreach ($this->categoryTypeRegistry->getGroups() as $group) {
            $this->addIcon(
                $event,
                $group->getIconIdentifier(),
                $group->getFrontendIcon(),
                $group->isFrontendInlineIcon(),
            );
        }
    }

    private function addIcon(CollectFrontendIconsEvent $event, string $identifier, string $file, bool $inline): void
    {
        if ($file === '') {
            return;
        }
        $event->addIcon($identifier, $this->iconProviderResolver->resolve($file, $inline), ['source' => $file]);
    }
}
