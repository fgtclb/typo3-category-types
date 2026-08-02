.. _breaking-1785706800:

==============================================================
Breaking: Category filter select options carry their own value
==============================================================

Description
===========

`FGTCLB\\CategoryTypes\\ViewHelpers\\Form\\FilterSelectViewHelper::getOptions()`
built one array per category with a `uid` key, and `renderOptionTags()` rendered
that uid as the value of the option:

..  code-block:: php

    $options[] = [
        'label' => $option->getTitle(),
        'uid' => $option->getUid(),
        // ...
    ];

Now that `optionValueField` and `optionLabelField` are registered arguments, the
value of an option is no longer necessarily the uid. Every option carries an
additional `value` key holding the resolved value, and `renderOptionTags()`
renders that key:

..  code-block:: php

    $options[] = [
        'label' => $this->optionProperty($option, 'optionLabelField') ?? (string)$option->getTitle(),
        'value' => $value,
        'uid' => $option->getUid(),
        // ...
    ];

The `uid` key is kept. It is what the grouping by parent matches on, and what the
`renderOptions="false"` template variable is read with.

Impact
======

Two things change for own code.

*   A class extending `FilterSelectViewHelper` and overriding `getOptions()`
    without adding the `value` key renders an option without a value, because
    `renderOptionTags()` reads a key that is not there.
*   `optionValueField` used to affect only the value an object handed over as
    `value` was compared against, never the rendered option value. It determines
    both now. A template passing `optionValueField` with anything but `uid` gets
    different option values than before — and a selection that finally matches
    them, which it could not before.

Nothing changes for a template that does not pass `optionValueField`, and
nothing changes for the three `DemandCategories.html` partials of
`EXT:academic_partners`, `EXT:academic_programs` and `EXT:academic_projects`,
which pass `optionValueField="uid"`.

Affected Installations
======================

Installations with an own subclass of `FilterSelectViewHelper` that overrides
`getOptions()`, and installations whose templates pass `optionValueField` to
`ct:form.filterSelect`.

Migration
=========

Add the `value` key to an overridden `getOptions()`:

..  code-block:: php

    $options[] = [
        'label' => $category->getTitle(),
        'value' => (string)$category->getUid(),
        'uid' => $category->getUid(),
        // ...
    ];

An override of `renderOptionTags()` that reads `$option['uid']` keeps working,
since the key is still there — but it ignores `optionValueField`. Read
`$option['value']` instead.

References
==========

*   :ref:`feature-1785706700` — the four arguments this change comes with.
*   `SelectViewHelper
    <https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-select>`__ —
    the core view helper whose behaviour `optionValueField` now follows.

.. index:: Fluid, Frontend, PHP-API, NotScanned
