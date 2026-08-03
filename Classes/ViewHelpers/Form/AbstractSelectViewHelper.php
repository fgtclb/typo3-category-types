<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\ViewHelpers\Form;

use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class AbstractSelectViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'select';

    /**
     * @var string[]
     */
    protected $selectedValues;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $arguments = [
            'options' => [
                'type' => 'array',
                'defaultValue' => [],
                'description' => 'Associative array with internal IDs as key, and the values are displayed in the select box. Can be combined with or replaced by child f:form.select.* nodes.',
            ],
            'optionsAfterContent' => [
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'If true, places auto-generated option tags after those rendered in the tag content. If false, automatic options come first.',
            ],
            'optionValueField' => [
                'type' => 'string',
                'description' => 'If specified, will call the appropriate getter on each object to determine the value.',
            ],
            'optionLabelField' => [
                'type' => 'string',
                'description' => 'If specified, will call the appropriate getter on each object to determine the label.',
            ],
            'sortByOptionLabel' => [
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'If true, List will be sorted by label.',
            ],
            'selectAllByDefault' => [
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'If specified options are selected if none was set before.',
            ],
            'errorClass' => [
                'type' => 'string',
                'defaultValue' => 'f3-form-error',
                'description' => 'CSS class to set if there are errors for this ViewHelper',
            ],
            'prependOptionLabel' => [
                'type' => 'string',
                'description' => 'If specified, will provide an option at first position with the specified label.',
            ],
            'prependOptionValue' => [
                'type' => 'string',
                'description' => 'If specified, will provide an option at first position with the specified value.',
            ],
            'multiple' => [
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'If set multiple options may be selected.',
            ],
            'required' => [
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'If set no empty value is allowed.',
            ],
            'renderOptions' => [
                'type' => 'bool',
                'defaultValue' => true,
                'description' => 'If true, options will be rendered. Otherwise an "options" variable is created for custom rendering in the template.',
            ],
        ];

        $this->registerArguments($arguments);
    }

    public function render(): string
    {
        if (isset($this->arguments['required']) && $this->arguments['required']) {
            $this->tag->addAttribute('required', 'required');
        }

        $name = $this->getName();
        if ($this->arguments['multiple'] === true) {
            $this->tag->addAttribute('multiple', 'multiple');
            // Without the suffix the browser submits one of the selected values instead of
            // all of them, the same way the core select view helper builds the name.
            $name .= '[]';
        }
        $this->tag->addAttribute('name', $name);

        $this->initSelectedValues();
        $options = $this->getOptions();

        if (isset($this->arguments['renderOptions'])
            && (bool)$this->arguments['renderOptions'] === false
        ) {
            $this->renderingContext->getVariableProvider()->add('options', $options);
        }

        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();
        $content = '';

        // Register field name for token generation.
        $this->registerFieldNameForFormTokenGeneration($name);
        if ($this->arguments['multiple'] === true) {
            $content .= $this->renderHiddenFieldForEmptyValue();
            // A multi select submits one value per selected option, so the field name has to
            // be registered as often as there are options. It is registered once above.
            for ($i = 1; $i < count($options); $i++) {
                $this->registerFieldNameForFormTokenGeneration($name);
            }
            $viewHelperVariableContainer->addOrUpdate(
                self::class,
                'registerFieldNameForFormTokenGeneration',
                $name
            );
        }

        $prependContent = $this->renderPrependOptionTag();

        $tagContent = '';
        if (isset($this->arguments['renderOptions'])
            && (bool)$this->arguments['renderOptions'] === true
        ) {
            $tagContent = $this->renderOptionTags($options);
        }

        $viewHelperVariableContainer->addOrUpdate(self::class, 'selectedValue', $this->selectedValues);

        $childContent = $this->renderChildren();

        $viewHelperVariableContainer->remove(self::class, 'selectedValue');
        $viewHelperVariableContainer->remove(self::class, 'registerFieldNameForFormTokenGeneration');
        if (isset($this->arguments['renderOptions']) && (bool)$this->arguments['renderOptions'] === false) {
            $this->renderingContext->getVariableProvider()->remove('options');
        }

        if (isset($this->arguments['optionsAfterContent']) && $this->arguments['optionsAfterContent']) {
            $tagContent = $childContent . $tagContent;
        } else {
            $tagContent .= $childContent;
        }
        $tagContent = $prependContent . $tagContent;

        $this->tag->forceClosingTag(true);
        $this->tag->setContent($tagContent);
        $content .= $this->tag->render();

        return $content;
    }

    /**
     * Render prepended option tag
     */
    protected function renderPrependOptionTag(): string
    {
        $output = '';
        if ($this->hasArgument('prependOptionLabel')) {
            $value = $this->hasArgument('prependOptionValue') ? $this->arguments['prependOptionValue'] : '';
            $label = $this->arguments['prependOptionLabel'];
            $output .= $this->renderOptionTag((string)$value, (string)$label, false) . LF;
        }
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

    /**
     * Inits the selected value(s) property
     */
    protected function initSelectedValues(): void
    {
        $selectedValues = [];

        $this->setRespectSubmittedDataValue(true);
        $value = $this->getValueAttribute();

        if (!is_array($value) && !$value instanceof \Traversable) {
            $selectedValues[] = $this->getOptionValueScalar($value);
        } else {
            foreach ($value as $selectedValueElement) {
                $selectedValues[] = $this->getOptionValueScalar($selectedValueElement);
            }
        }

        $this->selectedValues = $selectedValues;
    }

    /**
     * Get the option value for an object
     *
     * @return string
     */
    protected function getOptionValueScalar(mixed $valueElement)
    {
        if (is_object($valueElement)) {
            if ($this->hasArgument('optionValueField')) {
                // Cast: `ObjectAccess` hands back the property as it is typed, an `uid` as an
                // integer - and the selected values are compared as strings.
                return (string)ObjectAccess::getPropertyPath($valueElement, $this->arguments['optionValueField']);
            }
            // @todo use $this->persistenceManager->isNewObject() once it is implemented
            if ($this->persistenceManager->getIdentifierByObject($valueElement) !== null) {
                return $this->persistenceManager->getIdentifierByObject($valueElement);
            }
            if ($valueElement instanceof \Stringable) {
                return (string)$valueElement;
            }
            if (method_exists($valueElement, '__toString')) {
                return (string)$valueElement;
            }
            throw new \RuntimeException(
                sprintf(
                    'Cannot cast $valueElement "%s" to string.',
                    $valueElement::class,
                ),
                1742820065,
            );

        }
        return (string)$valueElement;
    }

    /**
     * Render the option tags.
     *
     * @param mixed $value Value to check for
     * @return bool TRUE if the value should be marked a s selected; FALSE otherwise
     */
    protected function isSelected(mixed $value)
    {
        if (in_array((string)$value, $this->selectedValues)) {
            return true;
        }

        // Only a multi select can preselect every option, and only while nothing is selected.
        return $this->arguments['multiple'] === true
            && $this->arguments['selectAllByDefault'] === true
            && $this->hasSelectedValue() === false;
    }

    /**
     * An unselected select still carries one empty selected value, which is what the value
     * attribute of a form without submitted data resolves to.
     */
    private function hasSelectedValue(): bool
    {
        foreach ($this->selectedValues as $selectedValue) {
            if ($selectedValue !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Render the option tags.
     *
     * Every option goes through `renderOptionTag()`, the way the core select view helper
     * does it - so there is one place writing an option, and one place escaping it. An
     * option that needs more than the value, the label and the selected state carries the
     * additional attributes in an `attributes` key.
     *
     * @param array<array<string, mixed>> $options the options for the form.
     * @return string rendered tags.
     */
    protected function renderOptionTags(array $options)
    {
        $output = '';
        foreach ($options as $option) {
            $attributes = $option['attributes'] ?? [];
            $output .= $this->renderOptionTag(
                (string)$option['value'],
                (string)$option['label'],
                (bool)$option['isSelected'],
                is_array($attributes) ? $attributes : [],
            ) . LF;
        }
        return $output;
    }

    /**
     * Render one option tag
     *
     * @param string $value value attribute of the option tag (will be escaped)
     * @param string $label content of the option tag (will be escaped)
     * @param bool $isSelected specifies whether or not to add selected attribute
     * @param array<string, mixed> $attributes additional attributes, keyed by name (values will be escaped)
     * @return string the rendered option tag
     */
    protected function renderOptionTag($value, $label, $isSelected, array $attributes = [])
    {
        $output = '<option value="' . htmlspecialchars((string)$value) . '"';
        foreach ($attributes as $attributeName => $attributeValue) {
            $output .= ' ' . $attributeName . '="' . htmlspecialchars((string)$attributeValue) . '"';
        }
        if ($isSelected) {
            $output .= ' selected="selected"';
        }
        $output .= '>' . htmlspecialchars((string)$label) . '</option>';
        return $output;
    }

    /**
     * Render the option tags.
     *
     * @return array<array<string, mixed>> an associative array of options, key will be the value of the option tag
     */
    protected function getOptions()
    {
        return [];
    }
}
