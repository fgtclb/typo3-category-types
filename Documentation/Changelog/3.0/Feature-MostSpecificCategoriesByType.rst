..  _feature-1790681427:

===========================================================
Feature: A category collection without the assigned parents
===========================================================

Description
===========

`FGTCLB\\CategoryTypes\\Collection\\CategoryCollection` has a new method:

..  code-block:: php

    public function getMostSpecificCategoriesByType(): array

It returns the same shape as `getAllCategoriesByType()`, the categories grouped
by their type identifier, but without every category that is an ancestor of
another category of the same type in the collection. With "Bachelor" and its
subcategory "Bachelor of Science" attached, only "Bachelor of Science" is
returned.

The ancestors are found through the parents of the attached categories, without
a query:

*   A parent of another type is kept, it stands for another type. The walk still
    passes it, so an ancestor of the same type above it is found.
*   A parent that is not attached ends the walk. With a category and a
    subcategory two levels below it attached, but not the level between them,
    both are returned.
*   A parent chain that leads back to itself hides none of its own
    categories, since each of them would otherwise hide the others. A category
    attached below such a chain still hides it.

Impact
======

`EXT:academic_programs` uses the method for its setting
:typoscript:`plugin.tx_academicprograms.facts.mostSpecificOnly`. Own code that
prints the categories of a record can use it the same way.
`getAllCategoriesByType()` is unchanged.

..  index:: Frontend, PHP-API, NotScanned
