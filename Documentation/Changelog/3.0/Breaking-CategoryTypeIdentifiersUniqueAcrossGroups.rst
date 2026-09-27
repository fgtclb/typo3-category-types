..  _breaking-1790505413:

============================================================
Breaking: Category type identifiers are unique across groups
============================================================

Description
===========

A category stores the identifier of its type in :sql:`sys_category.type`,
without the group. Two groups declaring the same identifier therefore gave the
type select of a category two items with one value. After saving, the backend
selected the first of the two, whichever the editor had picked, and the
category counted as that type for every extension reading the identifier: its
list filters offered it, and so did the filters of the other extension.

Loading the category types now fails when, after the
:file:`Configuration/CategoryTypes.yaml` of every package is read, one
identifier is declared in more than one group. Identifiers are compared
without surrounding whitespace and ignoring case, as MySQL and MariaDB compare
:sql:`sys_category.type`. The
:php:`\FGTCLB\CategoryTypes\Exception\CategoryTypeExistException` with the code
`1790505412` names the identifier, the groups and, for each group, the extension
that declared the type last. A declaration within the same group, with or
without :yaml:`useExisting`, is no collision.

The one collision of the shipped extensions, the type `department` of
`academic_programs` and `academic_projects`, is resolved by renaming the
projects type to `project_department`, see the changelog of
`academic_projects`.

Impact
======

An installation in which two groups declare the same type identifier stops
with the exception when the category types are loaded, in the backend and in
the frontend, instead of offering a type select that cannot tell the two types
apart.

Affected installations
======================

Installations with a package declaring a type identifier that another group
already has. With the shipped extensions alone, none: their only collision is
resolved in the same release.

Migration
=========

Rename one of the two types in the :file:`Configuration/CategoryTypes.yaml`
that declares it, and move the stored categories of that type with an update
of :sql:`sys_category.type`. A package that does not need one of the two types
can remove it instead, with :yaml:`remove: true` - the check runs after every
package is read, so a removal in a package loaded later resolves the
collision.

See :ref:`Identifiers are unique across groups <developers-tca-unique>` in the
`For Developers` chapter.

..  index:: Backend, Frontend, PHP-API, NotScanned, ext:category_types
