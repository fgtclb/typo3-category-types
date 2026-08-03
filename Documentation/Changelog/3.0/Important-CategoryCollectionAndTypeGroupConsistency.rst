.. _important-1785968400:

============================================================
Important: Collection and type group answer consistently now
============================================================

Description
===========

Three leftovers of the original category type handling are corrected. All three
were unreachable through the callers this extension ships, so nothing an
installation does changes; they matter to code that uses these classes directly.

`CategoryCollection::getAllCategoriesByType()` builds the grouped view on every
call. It used to keep the result in a `$typeSortedCollection` property whose
empty entries were seeded only while that property was still empty. Identifiers
passed to `setTypeIdentifiers()` *after* a first grouping therefore never got an
entry, and `getCategoriesByTypeName()` then read a missing key and returned
:php:`null` against its :php:`array` return type:

..  code-block:: text

    Undefined array key "country"
    TypeError: …::getCategoriesByTypeName(): Return value must be of type array,
    null returned

The property is gone. Nothing was cached by it — the loop over the categories
re-ran on every call regardless.

`CategoryCollection::exist()` answers on the uid now:

..  code-block:: php

    // before
    return in_array($category, $this->collection, false);
    // after
    return array_key_exists($category->getUid(), $this->collection);

`attach()` has always guarded on the uid, so the two disagreed on the case that
matters: the same record read a second time after an edit is a different object
with an equal uid, which `attach()` rejects as already present while `exist()`
reported it as absent.

`CategoryTypeGroup::fromArray()` is :php:`static`, like its counterpart
`CategoryType::fromArray()` has always been.

Impact
======

`CategoryCollection::exist()` now returns :php:`true` for a category whose uid is
in the collection, even when the object differs in any other property. It
returned :php:`false` in that case before, and :php:`true` only for an object all
of whose properties were equal.

Calling `CategoryTypeGroup::fromArray()` on an instance keeps working — PHP
permits an arrow call to a static method — so no call site has to change. Only a
subclass overriding the method non-statically is a fatal error and has to follow.

The removed `$typeSortedCollection` property was :php:`protected`; a subclass
reading it has to build the grouping itself.

Affected Installations
======================

None through the plugins and view helpers this extension ships. Only projects
calling `CategoryCollection::exist()`, overriding `CategoryTypeGroup::fromArray()`
or extending `CategoryCollection` are affected.

.. index:: PHP, ext:category_types
