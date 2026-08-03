.. _important-1785882100:

===========================================
Important: Select option values are escaped
===========================================

Description
===========

`FGTCLB\\CategoryTypes\\ViewHelpers\\Form\\AbstractSelectViewHelper` escaped the
label of a generated option and concatenated its value unchanged:

..  code-block:: php

    $output .= '<option value="' . $option['value'] . '"';
    // ...
    $output .= '>' . htmlspecialchars((string)$option['label']) . '</option>' . LF;

`FilterSelectViewHelper` did the same, and additionally wrote the
`groupLevelClassPrefix` argument into the `class` attribute unchanged. The entry
`prependOptionLabel` adds was never affected - it goes through
`renderOptionTag()`, which escaped both from the start.

A value that contains a quotation mark ended the attribute and everything after
it became markup:

..  code-block:: html

    <option value="" autofocus onfocus="alert(1)">A category</option>

Every option value and every additional attribute is escaped with
`htmlspecialchars()` now, in the one method that writes an option.

Impact
======

The rendered markup is unchanged for every value that needs no escaping, which
is what the shipped templates produce: the three `DemandCategories.html`
partials of `EXT:academic_partners`, `EXT:academic_programs` and
`EXT:academic_projects` pass `optionValueField="uid"`, and the sorting selects
of those extensions render their own constants.

It matters for a template that points `optionValueField` at a text property -
supported since :ref:`feature-1785706700` - because a category title is free
text entered in the backend. A title carrying a quotation mark used to reach the
`value` attribute unchanged.

An own subclass that overrides `renderOptionTags()` and writes its own markup is
not covered by this. Let it render through `renderOptionTag()` instead, see
:ref:`breaking-1785882000`.

References
==========

*   :ref:`breaking-1785882000` - the single option writer this relies on.
*   :ref:`feature-1785706700` - `optionValueField`, which decides what the value
    of an option is.
*   `htmlspecialchars <https://www.php.net/manual/en/function.htmlspecialchars.php>`__

.. index:: Fluid, Frontend, PHP-API, NotScanned
