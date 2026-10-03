.. _feature-1791035981:

====================================================================
Feature: A view helper names a category type by its registered title
====================================================================

Description
===========

The view helper :html:`ct:categoryTypeTitle` returns the title a category type
is registered with in :file:`Configuration/CategoryTypes.yaml`, in the language
of the page. A title that is an :php:`LLL:` reference is translated, a literal
title is returned as written, and a type the group does not know has an empty
title. Content that is not empty, the label of the type, is rendered instead of
the title:

..  code-block:: html

    <html xmlns:ct="http://typo3.org/ns/FGTCLB/CategoryTypes/ViewHelpers" data-namespace-typo3-fluid="true">

    {f:translate(key: 'sys_category.programs.{categoryKey}', extensionName: 'AcademicPrograms')
        -> ct:categoryTypeTitle(group: 'programs', identifier: categoryKey)}

See :ref:`developers-category-types-title`.

Impact
======

`EXT:academic_programs`, `EXT:academic_partners` and `EXT:academic_projects`
hand every label of a category type to it, so a type a project adds to their
groups is named in the frontend by its title.

.. index:: Frontend, Fluid, NotScanned, ext:category_types
