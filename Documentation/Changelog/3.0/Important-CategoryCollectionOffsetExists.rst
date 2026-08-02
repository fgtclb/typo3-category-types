.. _important-1785704400:

===========================================================
Important: Category types resolve in templates on first use
===========================================================

Description
===========

`FGTCLB\\CategoryTypes\\Collection\\CategoryCollection::offsetExists()` answered
from `$typeSortedCollection`, the grouped view the collection builds lazily. That
array stays empty until `getAllCategoriesByType()` has been called once on the
instance, so a category type was reported as absent although the collection
accepted it — while `offsetGet()`, which resolves through
`getCategoriesByTypeName()`, answered from the start:

..  code-block:: php

    $collection = new CategoryCollection();
    $collection->setTypeIdentifiers(['research_field']);

    $collection->offsetExists('researchField');   // false
    $collection->offsetGet('researchField');      // array

This reached the frontend. Fluid resolves a path segment on an `ArrayAccess`
subject through `offsetExists()` and only reads the offset once that returned
`true`, so `{categories.researchField}` produced nothing — no exception, no log
entry — until something else had computed the grouping first.

`offsetExists()` now answers from the registered type identifiers, the same
source the lookup it guards resolves against. `FilterCollection::offsetExists()`
already behaved that way.

Impact
======

A template can access a category type by name without preparing the collection
first:

..  code-block:: html

    <f:for each="{categories.researchField}" as="category">
        {category.title}
    </f:for>

Previously that rendered nothing unless the template had accessed
`{categories.allCategoriesByType}` — or anything else computing the grouping —
beforehand.

The Fluid files shipped by `EXT:academic_partners`, `EXT:academic_programs` and
`EXT:academic_projects` were not affected: their `DemandCategories.html` partials
iterate `{categories.allCategoriesByType}` before reaching a type by name, so the
grouping was always computed in time.

Affected Installations
======================

Installations with project templates or partials that read a category type by
name from a `CategoryCollection`, and any code calling `offsetExists()` or
`isset()` on one.

Migration
=========

No configuration change is required. Workarounds that accessed
`{categories.allCategoriesByType}` only to make a later expression resolve can be
removed.

.. index:: Fluid, Frontend, PHP-API, NotScanned
