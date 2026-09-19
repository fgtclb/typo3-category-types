.. _important-category-lists-follow-the-backend-sorting-order:

==========================================================
Important: Category lists follow the backend sorting order
==========================================================

Description
===========

The four list queries of :php:`CategoryRepository` —
:php:`findByGroupAndPageId()`, :php:`findAllApplicable()`,
:php:`findByGroupAndUidList()` and :php:`getByDatabaseFields()` — executed
without an ordering, so the order of every category filter and category list
built from them was whatever the database happened to yield. On PostgreSQL
that is not the same list twice. They now order by the manual backend
:sql:`sorting` of :sql:`sys_category` (TCA ctrl :php:`sortby`), with
:sql:`uid` settling ties.

Impact
======

Category filters and lists now follow the backend :sql:`sorting` of the
categories — the order siblings have in the category tree; categories of
different parents interleave by that value. That order was never delivered
before, and it differs from the previous one even where nobody reordered
anything: the database returned categories oldest first (:sql:`uid` order),
while the backend typically places a new category above the existing ones of
its page, so categories nobody reordered are sorted newest first. Such lists
can therefore appear reversed. Arrange the categories in the backend where the
new order is not the intended one.

Affected Installations
======================

Every installation of this extension, and the extensions building their
category filters on it: :php:`academic_partners`, :php:`academic_programs`
and :php:`academic_projects`.

.. index:: Frontend, PHP-API, ext:category_types
