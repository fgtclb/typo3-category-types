.. _important-1785790800:

==================================================================
Important: Category selection with an empty uid list returns empty
==================================================================

Description
===========

`FGTCLB\\CategoryTypes\\Domain\\Repository\\CategoryRepository::findByGroupAndUidList()`
passed its `$idList` argument as a plain array to the Doctrine DBAL expression
builder:

..  code-block:: php

    $queryBuilder->expr()->in('uid', $idList)

An empty array is not a valid value list there. TYPO3 v13 and v14 raise an
`\InvalidArgumentException` (1701857902), *ExpressionBuilder::in() can not be
used with an empty array value*. That validation was added for TYPO3 v13 only
and never backported, so on TYPO3 v12 the same call produces `uid IN ()` and the
outcome depends on the database: MariaDB, MySQL and PostgreSQL reject the
statement with a syntax error, SQLite accepts it and returns no row.

The uid list is now quoted with
`TYPO3\\CMS\\Core\\Database\\Query\\QueryBuilder::quoteArrayBasedValueListToIntegerList()`,
the API TYPO3 provides for exactly this case:

..  code-block:: php

    $queryBuilder->expr()->in(
        'uid',
        $queryBuilder->quoteArrayBasedValueListToIntegerList($idList),
    )

The helper returns the string `NULL` for an empty array, so the query becomes
`uid IN (NULL)` — valid on every supported DBMS and matching no row. It is the
same API the neighbouring condition on `sys_category.type` already used through
`quoteArrayBasedValueListToStringList()`.

Impact
======

`findByGroupAndUidList()` no longer fails for an empty uid list, on any
supported TYPO3 version and any supported DBMS. It returns an empty category
collection, which still carries the type identifiers of the group like the
result of every other selection, so a template iterating the types keeps its
shape.

The uid list is a positive selection, so "nothing selected" means "no
categories" — it is deliberately not read as "no restriction", which would
return the whole group.

An unknown group is still rejected with an `\InvalidArgumentException`
(1683633304209).

The frontend filters shipped by `EXT:academic_partners`,
`EXT:academic_programs` and `EXT:academic_projects` were not affected: their
`Factory\\DemandFactory` builds the list with
`TYPO3\\CMS\\Core\\Utility\\GeneralUtility::intExplode()`, which returns `[0]`
for an empty value, so a submitted but unselected filter never produced an empty
list. Only a `filterCollection` that is an empty array reached the failing call.

Affected Installations
======================

Installations calling `CategoryRepository::findByGroupAndUidList()` from own
code with a uid list that can be empty, and installations that worked around the
failure with a caller-side guard.

Migration
=========

No configuration change is required. Caller-side guards of the shape

..  code-block:: php

    if ($uids !== []) {
        $categories = $categoryRepository->findByGroupAndUidList($group, $uids);
    }

can be removed.

References
==========

*   TYPO3 issue `#96434 <https://forge.typo3.org/issues/96434>`__,
    *Provide quoted array to string-list implode support* — the change adding
    `quoteArrayBasedValueListToIntegerList()` and
    `quoteArrayBasedValueListToStringList()` to the query builder, released for
    TYPO3 v11.5 and later
    (`commit 4ec4b7e2f9 <https://github.com/TYPO3/typo3/commit/4ec4b7e2f9>`__).
*   TYPO3 issue `#102612 <https://forge.typo3.org/issues/102612>`__,
    *Validate arguments for ExpressionBuilder::in and notIn* — the change adding
    the validation, released for TYPO3 v13 only
    (`commit 7c6b5cc7ac <https://github.com/TYPO3/typo3/commit/7c6b5cc7ac>`__).
*   `QueryBuilder <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Database/QueryBuilder/Index.html>`__
    in the TYPO3 Explained manual.

.. index:: Database, PHP-API, NotScanned
