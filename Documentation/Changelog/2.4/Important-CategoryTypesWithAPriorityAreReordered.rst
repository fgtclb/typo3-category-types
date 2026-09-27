.. _important-1790499574:

=======================================================
Important: Category types with a priority are reordered
=======================================================

Description
===========

The :yaml:`priority` of a category type now decides its place in its group, the
highest first, see :ref:`feature-1790499573`. Before, it was stored and ignored.

Impact
======

An installation whose own :file:`Configuration/CategoryTypes.yaml` - or that of
a third party extension - already sets a :yaml:`priority` sees those types
reordered after the update, wherever the types of the group are listed: in the
type select of a category, in the page module and in the frontend outputs that
list the types. No category type shipped with the academic extensions
declares one.

Remove the :yaml:`priority` to keep the declaration order, or check that the new
order is the intended one.

:php:`CategoryTypeRegistry::getCategoryTypes()` no longer interleaves groups: it
returns the types of one group after the other. That differs from before when a
type is added to a group after a group that was first declared later already
has types: for example a type a site package adds to the group
:yaml:`programs`, while :php:`academic_projects`, loaded after
:php:`academic_programs`, declared its types in between. That type moves from
the end of the list into its group.

.. index:: Backend, Frontend, PHP-API, NotScanned, ext:category_types
