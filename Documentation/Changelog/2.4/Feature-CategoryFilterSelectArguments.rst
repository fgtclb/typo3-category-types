.. _feature-1785706700:

=================================================================
Feature: Category filter select accepts the core select arguments
=================================================================

Description
===========

`FGTCLB\\CategoryTypes\\ViewHelpers\\Form\\AbstractSelectViewHelper` registers the
argument list of the TYPO3 core `<f:form.select>` view helper, apart from four
arguments it never registered: `optionValueField`, `optionLabelField`,
`multiple` and `selectAllByDefault`.

Unregistered arguments are not rejected. `AbstractTagBasedViewHelper` turns them
into attributes of the rendered tag, so a template passing them produced

..  code-block:: html

    <select optionValueField="uid" optionLabelField="title" name="filter[researchField]">

None of these is a valid attribute of a `select` element. The four arguments are
registered now and do what their core counterparts do.

`optionValueField`
------------------

Determines the value of every option, and the value an object handed over as
`value` is compared against. It defaulted to the category uid and still does
when the argument is not given.

`optionLabelField`
------------------

Determines the label of every option. Without the argument the label is the
category title, as before.

Both are resolved with
`TYPO3\\CMS\\Extbase\\Reflection\\ObjectAccess::getPropertyPath()`, so a property
path such as `type.title` is allowed. A property that is neither a scalar nor
`\\Stringable` is rejected with a `\\RuntimeException` (1785706600).

`multiple`
----------

Renders the `multiple` attribute and appends `[]` to the field name, registers
the field name once per option for the form token, and adds the hidden field
that lets an empty selection reach the controller — all of it the way
`TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\SelectViewHelper` does.

`selectAllByDefault`
--------------------

Selects every option of a multiple select while nothing else is selected.

Impact
======

A template can use the four arguments, and none of them reaches the markup as an
attribute any more.

The three `DemandCategories.html` partials of `EXT:academic_partners`,
`EXT:academic_programs` and `EXT:academic_projects` pass `optionValueField="uid"`
and `optionLabelField="title"`. Their rendered options are unchanged — both
fields resolve to what the filter select used by default — but the two invalid
attributes are gone from the `select` element.

The arguments are registered on `AbstractSelectViewHelper`, so they also apply to
the `SortingSelectViewHelper` of those three extensions. Their options are label
strings rather than objects, where `optionValueField` and `optionLabelField` have
nothing to resolve; `multiple` and `selectAllByDefault` work there as well.

References
==========

*   `SelectViewHelper
    <https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-select>`__ —
    the core view helper this argument list follows.
*   `ObjectAccess
    <https://docs.typo3.org/permalink/t3coreapi:extbase>`__ — the property path
    resolution used for the two option fields.

.. index:: Fluid, Frontend, NotScanned
