.. _important-1785700800:

=============================================================
Important: Category type registry without any registered type
=============================================================

Description
===========

`FGTCLB\\CategoryTypes\\Registry\\CategoryTypeRegistry` kept its grouped
registry in a typed property that was never initialised, and
`CategoryTypeRegistry::attach()` returns early when it is called without a
category type — which is what `CategoryTypeLoader::load()` does when no
installed extension ships a `Configuration/CategoryTypes.yaml`.

On such an installation the registry was left in a state where four of its
methods raised a fatal error instead of answering:

..  code-block:: text

    Error: Typed property CategoryTypeRegistry::$groupedRegistry
    must not be accessed before initialization

That affected `getGroupedCategoryTypes()`, `toArray()`,
`getCategoryTypesByGroup()` and `getCategoryTypeIdentifierByGroup()`. The last
two are called by every method of
`FGTCLB\\CategoryTypes\\Domain\\Repository\\CategoryRepository` and by
`FGTCLB\\CategoryTypes\\Factory\\CategoryCollectionFactory`, so a category
lookup on an installation without registered types brought the request down.

The property is now initialised, so an empty registry answers like a registry
whose groups simply do not match:

*   `getCategoryTypes()`, `getGroupedCategoryTypes()` and `toArray()` return an
    empty array,
*   `getCategoryTypesByGroup()` and `getCategoryTypeIdentifierByGroup()` throw
    the documented `\\InvalidArgumentException` (code `1683633304209`) for the
    group that was asked for.

Impact
======

A category lookup on an installation that has `EXT:category_types` installed
but no extension registering category types no longer fails with a fatal error.

Installations that already had registered category types are unaffected: the
property was initialised by the first `attach()` call there.

Affected Installations
======================

Installations using `EXT:category_types` without any extension shipping
`Configuration/CategoryTypes.yaml`. Among the extensions of this repository
those are `EXT:academic_partners`, `EXT:academic_programs` and
`EXT:academic_projects`.

Migration
=========

No configuration change is required.

.. index:: PHP-API, NotScanned
