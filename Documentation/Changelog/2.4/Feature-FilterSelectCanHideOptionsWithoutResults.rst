.. _feature-1790377825:

====================================================================
Feature: The category filter select can hide options without results
====================================================================

Description
===========

The partner, project and program lists offer every category of each category
type they filter by, and mark the ones no listed record carries as disabled
options: `CategoryRepository::findAllApplicable()` disables them, and
`<ct:form.filterSelect>` renders them with `disabled="disabled"`.

`FGTCLB\\CategoryTypes\\ViewHelpers\\Form\\FilterSelectViewHelper` has a new
argument `hideDisabledOptions`, default `false`. When it is `true`, a
disabled option is left out instead, with two exceptions:

*   An option that is selected stays, so the visitor sees the active filter and
    can change it. Only the value the select is bound to counts: with
    `selectAllByDefault` and nothing selected, disabled options are left out.
*   With `groupByParent`, a disabled option stays, still disabled, while one
    of its descendants is shown, so the hierarchy the level classes indent is
    kept. Without grouping a disabled parent is left out like any other
    disabled option.

..  code-block:: html

    <ct:form.filterSelect
        property="filterCollection.{categoryKey}"
        value="{demand.filterCollection.filterCategories.{categoryKey}}"
        options="{categories.{categoryKey}}"
        hideDisabledOptions="1"
    />

With `renderOptions="false"`, the `options` variable carries the same
reduced list, so a template that renders the options itself no longer needs a
loop of its own to leave the disabled ones out.

Impact
======

Nothing changes until a template sets the argument. The `DemandCategories.html`
partials of `EXT:academic_partners`, `EXT:academic_projects` and
`EXT:academic_programs` do not set it; a project that overrides them can add it
in place of their own option loop.

The prepended "all" option is not one of the options and always stays, so a
filter whose options are all without results still shows it.

.. index:: Fluid, Frontend, NotScanned
