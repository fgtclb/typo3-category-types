.. _feature-1789660802:

==========================================================
Feature: The page module can summarise a page's categories
==========================================================

Description
===========

EXT:academic_programs, EXT:academic_projects and
EXT:academic_partners each carried a Fluid partial meant to show the
categories of their page type in the page module, and none of the three
renders: all of them are registered as an override of a partial of
EXT:backend that no core template renders, on TYPO3 v12 and v13 alike.

None of the three has shown the table in any 2.x release.

The summary is implemented here now, once, and the three extensions ask for it
with a four line event listener each.

:php:`\FGTCLB\CategoryTypes\Backend\PageCategorySummaryRenderer` renders the
table for the page a backend request addresses:

..  code-block:: php

    $event->addHeaderContent($this->renderer->renderForPageOfType(
        $event->getRequest(),
        123,
        'example',
    ));

It answers with an empty string for a page that is not of the given type, a page
the backend user may not read, a request that addresses no page, and a category
group no active extension registers.

The table lists every registered type of the group, in registry order, with the
title the type was registered with. Types the page carries no category of are
listed with a `Not set` note, and assigned categories that are switched off are
listed with the core hidden overlay, which the page properties do not show an
editor.

Impact
======

*   An extension that registers a page type of its own can show a category
    summary in the page module without copying a template of EXT:backend
    and without a partial of its own.
*   The labels come from the :file:`Configuration/CategoryTypes.yaml` of the
    extension that registered the type, so a type an integrator adds is
    labelled too. A :yaml:`title` that is an :php:`LLL:` reference is
    translated, a literal title is used as it stands.
*   The template is replaceable through page TSconfig:

    ..  code-block:: typoscript

        templates.fgtclb/category-types.my-site = my-vendor/my-site:Resources/Private/Backend

    The file is looked up below that path as
    :file:`Templates/PageCategorySummary.html`.

*   :php:`\FGTCLB\CategoryTypes\ViewHelpers\Be\CategoryViewHelper` is
    unchanged and stays public API. The summary does not use it. It reads the
    categories through
    :php:`\FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository`
    directly.

Affected Installations
======================

None have to act. The summary appears where an extension asks for it, and the
three academic extensions that do are updated in the same release.

References
==========

*   The chapter :guilabel:`For Developers`, section
    :guilabel:`The page module category summary`, describes the listener, the
    override key and the variables the template is given.

.. index:: Backend, PHP-API, TSConfig, NotScanned, ext:category_types
