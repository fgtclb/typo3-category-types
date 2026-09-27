.. _feature-1790499573:

===============================================
Feature: Category types are ordered by priority
===============================================

Description
===========

Every category type of a :file:`Configuration/CategoryTypes.yaml` could declare
a :yaml:`priority`, and nothing read it. The types of a group came in the order
the packages were loaded and the files listed them. A :yaml:`useExisting`
override kept a type where it was, so a project could not reorder the types an
extension ships, and several projects fixed the order in their own template
overrides instead.

The types of a group are now ordered by :yaml:`priority`, the highest first.
Types with the same priority keep the order in which they were declared. A type
that declares no priority has :yaml:`0`.

A project moves a type another extension declares with an override that sets
only the priority:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    types:
      - identifier: location
        group: programs
        priority: 10
        useExisting: true

The extension of the override has to be loaded after the one that declares the
type, as for every override.

Impact
======

Every list of the types of a group follows the order, without a change of its
own: the type select of a category, the page module category summary and the
outputs of the extensions that use the group - among them the categories of a
program, partner or project page and the filter selects of the program,
partner and project lists.

:php:`CategoryTypeRegistry::getCategoryTypes()` returns the types group by
group, in the order each group was first declared, and each group in its order.

No category type shipped with the academic extensions declares a priority, so
the order does not change until a project sets one. The types are ordered when
the registry is built, so a changed priority shows after the caches are flushed.

.. index:: Backend, Frontend, PHP-API, NotScanned, ext:category_types
