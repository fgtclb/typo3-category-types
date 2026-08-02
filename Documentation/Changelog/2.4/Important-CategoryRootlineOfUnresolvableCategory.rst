.. _important-1785874800:

============================================================
Important: Category rootline no longer fails on broken trees
============================================================

Description
===========

`FGTCLB\\CategoryTypes\\Domain\\Repository\\CategoryRepository::getCategoryRootline()`
walks from a category up to its root through the private `getCategoryArray()`,
which returned the result of `fetchAssociative()` and was typed `array`. For a
uid without a row that result is `false`:

..  code-block:: text

    TypeError: FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository::getCategoryArray():
    Return value must be of type array, bool returned

Three inputs produced it, on every supported TYPO3 version and every supported
DBMS: an unknown uid, the uid `0`, and a **deleted** category — the restrictions
are lifted except for the deleted one. The last case is the reachable one:
deleting a category leaves its children in place, so `sys_category.parent` can
point at a deleted record in ordinary editorial data, and the rootline of every
descendant failed with it.

A category referencing itself, or two categories referencing each other, made
the method recurse until the memory limit was reached — a fatal error, which
unlike the `TypeError` no caller can catch.

`getCategoryArray()` now returns `null` for a record it cannot resolve, which
also covers the two cases where TYPO3 drops the record afterwards: a workspace
that deleted or moved it, and a language overlay that yields nothing.
`getCategoryRootline()` ends the walk instead of failing, and it stops when it
reaches a uid it has already collected.

The comparison ending the recursion at the root was `$category['parent'] !== 0`,
a strict comparison against an integer. All four supported DBMS return `parent`
as an integer, so this was not a defect, but a string `'0'` would have recursed
into uid `0`. The value is cast now.

Impact
======

`getCategoryRootline()` no longer throws for a category it cannot resolve.

*   The requested uid cannot be resolved — an empty rootline.
*   An ancestor cannot be resolved — the part of the rootline that could be
    resolved, root first, rather than nothing. A category whose parent was
    deleted still yields its own record.
*   A cycle — the walk ends at the uid it has already seen, so the result holds
    each category once.

An intact tree is unchanged.

Affected Installations
======================

Installations calling `CategoryRepository::getCategoryRootline()` from own code.
The only call site inside these extensions,
`EXT:academic_programs` `FGTCLB\\AcademicPrograms\\Backend\\Tca\\Labels::category()`,
is not active, so no shipped feature changes behaviour.

Migration
=========

No configuration change is required. A caller-side guard of the shape

..  code-block:: php

    if ($categoryRepository->findParent($group, $uid) !== null) {
        $rootline = $categoryRepository->getCategoryRootline($uid);
    }

can be removed, and a `try`/`catch` around the call can be dropped. Callers that
have to tell "no such category" from "a broken chain" apart should check the
result for an empty array, which now means the requested category itself could
not be resolved.

References
==========

*   `PageRepository::versionOL()
    <https://api.typo3.org/main/classes/TYPO3-CMS-Core-Domain-Repository-PageRepository.html>`__
    — it sets the record to `false` when the workspace deleted or moved it away,
    which is why that case is checked after the call and not only after the
    query. See `Workspaces
    <https://docs.typo3.org/permalink/t3coreapi:workspaces>`__.
*   `Categories <https://docs.typo3.org/permalink/t3coreapi:categories>`__ in
    the TYPO3 Explained manual — `sys_category` and its `parent` field.

.. index:: Database, PHP-API, NotScanned
