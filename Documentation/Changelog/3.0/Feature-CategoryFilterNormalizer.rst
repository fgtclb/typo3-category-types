.. _feature-1785709700:

====================================================
Feature: Category filter normalizer for demand forms
====================================================

Description
===========

`FGTCLB\\CategoryTypes\\Filter\\CategoryFilterNormalizer` turns the category
filter of a submitted demand form into a list of category uids:

..  code-block:: php

    public function toUidList(mixed $filter): array

The filter reaches a plugin as the raw request array of an action such as
`listAction(?array $demand = null)`, which validates nothing, so every shape has
to be survivable. The shapes that carry a selection:

*   a single select submits one uid per category type,
*   a select with `multiple` submits one value per selected option,
*   a value assembled by a template may be a comma separated list.

Anything that cannot be read is treated as **no filter** rather than as an
error — a crafted request must not be able to take a list plugin down. That
covers a filter that is not an array at all, a value nested deeper than one
level, an object, a boolean and `null`.

Two more properties worth knowing:

*   The category type the values were submitted under is **not** part of the
    result. The consumers pass the list to
    `CategoryRepository::findByGroupAndUidList()`, which resolves the types of
    the whole group anyway.
*   Empty values are dropped, so the prepended "all options" entry of a filter
    select no longer contributes uid `0`, and a uid submitted twice is collected
    once.

The class is stateless and autowired, so it can be injected wherever a submitted
category filter has to be read:

..  code-block:: php

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryFilterNormalizer $categoryFilterNormalizer,
    ) {}

Impact
======

The demand factories of `EXT:academic_partners`, `EXT:academic_programs` and
`EXT:academic_projects` read their category filter through it, which is what
lets them accept the `multiple` filter select added in the same release. Own
code that reads a submitted category filter can use the same class instead of
splitting the request value itself.

References
==========

*   :ref:`feature-1785706700` — the `multiple` argument of the category filter
    select, whose submitted shape this class exists to read.
*   :ref:`important-1785790800` — the empty uid list
    `findByGroupAndUidList()` accepts, which is what makes dropping the empty
    values safe.

.. index:: Frontend, PHP-API, NotScanned
