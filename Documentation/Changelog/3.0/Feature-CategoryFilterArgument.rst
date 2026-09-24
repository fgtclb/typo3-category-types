.. _feature-1790226104:

===============================================
Feature: Write a category filter as a URL value
===============================================

Description
===========

`FGTCLB\\CategoryTypes\\Filter\\CategoryFilterNormalizer` gained the reverse of
`toUidList()`:

..  code-block:: php

    public function toFilterArgument(?FilterCollection $filterCollection): string

It returns the categories of a resolved filter as one comma separated list of
uids, in ascending order, or an empty string when nothing is filtered. That is
the shape a list plugin puts into the URL it redirects a filter submission to,
and `toUidList()` reads it back:

..  code-block:: php

    $argument = $normalizer->toFilterArgument($demand->getFilterCollection());
    // '3,6'
    $normalizer->toUidList(['categories' => $argument]);
    // [3, 6]

The category type a category belongs to is not part of the value, for the
reason `toUidList()` ignores it: the repository resolves the types of the whole
group anyway. The ascending order gives one selection exactly one URL, whatever
order its categories were selected in.

Impact
======

The demand factories of `EXT:academic_partners`, `EXT:academic_programs` and
`EXT:academic_projects` build the filter argument of their list URLs with it.
Own code that links to a filtered list can use the same method: a link built
with `f:link.action` or `UriBuilder::uriFor()` and the same demand arguments
equals the URL the plugins redirect to.

.. index:: Frontend, PHP-API, NotScanned
