.. _important-ace-314-category-types:

=====================================================
Important: The categories of a plugin apply on SQLite
=====================================================

Description
===========

The list content elements of `EXT:academic_partners`, `EXT:academic_programs`
and `EXT:academic_projects` can be restricted to categories an editor picks in
the content element (:guilabel:`Default categories`, :guilabel:`Categories` for
projects). The content element reads them with
:php:`\FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository::getByDatabaseFields()`.

That method returned an empty category collection whenever
:php:`Result::rowCount()` reported no row. For a :sql:`SELECT` statement that
number depends on the database driver, and SQLite reports 0 even for a result
that carries rows. On SQLite the categories of the content element were
therefore always dropped, and the list showed every record instead of the
records of those categories.

The method now reads the result and decides by its rows.

Impact
======

On SQLite, a partner, program or project list restricted to categories in its
content element shows only the records of those categories. TYPO3 v12 and v13
behave alike. Installations on MariaDB, MySQL or PostgreSQL, whose drivers
report the number of selected rows, see no change.

Affected Installations
======================

Installations running on SQLite with a partner, program or project list whose
content element selects categories.

.. index:: Database, Frontend, ext:category_types
