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

Category filters and lists now render in the order the categories have in the
backend. An installation whose editors reordered categories will see the
frontend follow that order — which is the order the editor expressed, but was
never delivered before. Installations that never reordered categories keep
their practical order: for them :sql:`sorting` and :sql:`uid` agree.

Affected Installations
======================

Every installation of this extension, and the extensions building their
category filters on it: :php:`academic_partners`, :php:`academic_programs`
and :php:`academic_projects`.

.. index:: Frontend, PHP-API, ext:category_types
