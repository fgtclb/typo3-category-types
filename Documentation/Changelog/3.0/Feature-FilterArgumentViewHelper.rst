.. _feature-1790946477:

=================================================
Feature: The filter argument of a link as a value
=================================================

Description
===========

The view helper :html:`ct:filterArgument` returns the category filter of a
list URL, one comma separated list of category uids, optionally without one
category:

..  code-block:: html

    <html xmlns:ct="http://typo3.org/ns/FGTCLB/CategoryTypes/ViewHelpers" data-namespace-typo3-fluid="true">

    <f:variable name="remaining" value="{ct:filterArgument(filterCollection: demand.filterCollection, without: category)}" />

The value is the one
:php:`\FGTCLB\CategoryTypes\Filter\CategoryFilterNormalizer::toFilterArgument()`
builds for the redirect of a filter submission, see :ref:`feature-1790226104`.
That method gained the optional second argument :php:`$withoutCategory`, the
uid of the category to leave out. An empty string means that nothing is left to
filter by. A link leaves the filter argument out then, because a list route
enhancer cannot generate an empty filter.

Impact
======

The active filter tags of `EXT:academic_partners`, `EXT:academic_programs` and
`EXT:academic_projects` link to the list without one category through it. A
template that loops over the categories of a filter collection and calls the
view helper inside the loop has to loop over
:html:`{filterCollection.filterCategories.allCategoriesByType}` rather than over
:html:`{filterCollection.filterCategories}`: the category collection is an
iterator with a single position, which the view helper moves to the end.
:html:`allCategoriesByType` holds the categories of the types the collection
was created for. The collections of
:php:`CategoryRepository::findByGroupAndUidList()` and
:php:`CategoryRepository::getByDatabaseFields()` carry the types of their
group, a collection created with :php:`new CategoryCollection()` carries none
and groups no category.

.. index:: Frontend, Fluid, PHP-API, NotScanned
