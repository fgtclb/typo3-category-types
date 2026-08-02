<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\ViewHelpers\Be;

use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class CategoryViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $arguments = [
            'page' => [
                'type' => 'int',
                'defaultValue' => [],
                'description' => 'The page ID for which the categories should be fetched',
                'required' => true,
            ],
            'group' => [
                'type' => 'string',
                'defaultValue' => 'default',
                'description' => 'The group identifier for the categories for this page type',
                'required' => true,
            ],
            'as' => [
                'type' => 'string',
                'defaultValue' => 'category',
                'description' => 'The variable name the categories should be assigned to',
            ],
        ];

        $this->registerArguments($arguments);
    }

    public function render(): string
    {
        $templateVariableContainer = $this->renderingContext->getVariableProvider();

        /** @var CategoryRepository $repository */
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->findByGroupAndPageId($this->arguments['group'], $this->arguments['page'], true);

        $templateVariableContainer->add($this->arguments['as'], $categories);
        $output = $this->renderChildren();
        $templateVariableContainer->remove($this->arguments['as']);

        return $output;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    protected function registerArguments(array $arguments): void
    {
        foreach ($arguments as $argumentName => $argumentConfiguration) {
            $this->registerArgument(
                $argumentName,
                $argumentConfiguration['type'],
                $argumentConfiguration['description'],
                $argumentConfiguration['required'] ?? false,
                $argumentConfiguration['defaultValue'] ?? null,
                $argumentConfiguration['escape'] ?? null
            );
        }
    }
}
