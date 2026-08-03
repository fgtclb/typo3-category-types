.. _breaking-1785882000:

===================================================
Breaking: Select options are rendered by one method
===================================================

Description
===========

`FGTCLB\\CategoryTypes\\ViewHelpers\\Form\\AbstractSelectViewHelper` had two
methods writing an `<option>` element: `renderOptionTag()` for the entry
`prependOptionLabel` adds, and `renderOptionTags()` for the generated ones. Both
built the markup themselves, and every subclass that needed one more attribute
copied the whole loop - `FilterSelectViewHelper` for the level class and the
disabled state, the three `SortingSelectViewHelper` implementations of
`EXT:academic_partners`, `EXT:academic_programs` and `EXT:academic_projects`
without changing anything at all.

`renderOptionTags()` now writes no markup of its own. It hands every option to
`renderOptionTag()`, the way `TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\SelectViewHelper`
does it:

..  code-block:: php

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

`renderOptionTag()` takes a fourth parameter for that, an array of additional
attributes keyed by name:

..  code-block:: php

    protected function renderOptionTag($value, $label, $isSelected, array $attributes = [])

An option carries those attributes in an `attributes` key.
`FilterSelectViewHelper::getOptions()` fills it with the level class and, where
applicable, `disabled`; its `renderOptionTags()` override and the three of the
sorting selects were removed.

Impact
======

*   A subclass overriding `renderOptionTag()` with the previous three-parameter
    signature is a fatal error - PHP rejects an override with fewer parameters
    than the parent declares.
*   A subclass overriding `FilterSelectViewHelper::getOptions()` without adding
    an `attributes` key renders its options without the level class and without
    the disabled attribute. Both used to be added while the markup was written.
*   `disabled` precedes `selected` in the markup of the category filter now,
    because it comes from the option array while `selected` is appended last.
    The two only ever appear together on a disabled category that is the
    selected one.
*   Removing the three `SortingSelectViewHelper::renderOptionTags()` methods
    changes no markup. They were identical to the one they inherit, and a
    subclass overriding or calling them keeps working.

Affected Installations
======================

Installations with an own subclass of `AbstractSelectViewHelper`,
`FilterSelectViewHelper` or one of the three `SortingSelectViewHelper`
implementations that overrides `renderOptionTag()`, `renderOptionTags()` or
`FilterSelectViewHelper::getOptions()`.

Migration
=========

Add the parameter to an overridden `renderOptionTag()`:

..  code-block:: php

    protected function renderOptionTag($value, $label, $isSelected, array $attributes = [])

Add the attributes to an overridden `getOptions()`:

..  code-block:: php

    $options[] = [
        'label' => $category->getTitle(),
        'value' => (string)$category->getUid(),
        'isSelected' => $this->isSelected((string)$category->getUid()),
        'attributes' => ['class' => 'level-0'],
        // ...
    ];

An override of `renderOptionTags()` that writes its own markup keeps working,
but it escapes nothing unless it does so itself - see
:ref:`important-1785882100`.

References
==========

*   :ref:`important-1785882100` - the escaping this change makes possible in one
    place.
*   :ref:`breaking-1785706800` - the `value` key of an option, which this builds
    on.
*   `SelectViewHelper
    <https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-select>`__ -
    the core view helper whose structure the base class now follows.

.. index:: Fluid, Frontend, PHP-API, NotScanned
