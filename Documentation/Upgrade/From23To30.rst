..  _upgrade-from-2-3-to-3-0:

=========================
Upgrading from 2.3 to 3.0
=========================

This page takes an installation of :guilabel:`category_types` 2.3 straight to
3.0, without the step to 2.4. Version 2.4 orders category types by their
priority and categories by their backend sorting, gives the category filter
select the arguments of the core select view helper and renders every option
through one method. Version 3.0 drops TYPO3 12, requires a category type
identifier to be unique across all groups, reads the :yaml:`groups` of a
:file:`Configuration/CategoryTypes.yaml` and registers the icons of the types
and groups for the frontend as well. Version 3.0 replaces no state of 2.4, so
the page leaves out no step of 2.4. Work through the sections in order. Skip a
step that does not apply to the installation.

..  _upgrade-from-2-3-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`category_types` 3.0 declares no new dependency of its own. It needs
these packages. Upgrade them first or in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, new since 2.4, which
    :guilabel:`academic_base` 3.0 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.

:guilabel:`academic_partners`, :guilabel:`academic_programs` and
:guilabel:`academic_projects` require this extension in the same release and
follow it in the same deployment.

These changes have to ship in the same deployment as the update, because the
category types stop loading until they are made:

*   A :file:`Configuration/CategoryTypes.yaml` that declares an identifier
    another group declares as well renames one of the two types, see
    :ref:`upgrade-from-2-3-to-3-0-unique-identifiers`.
*   A :yaml:`groups` entry without an :yaml:`identifier` gets one or goes, see
    :ref:`upgrade-from-2-3-to-3-0-groups`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-3-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.3, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete when the installation also has
:guilabel:`academic_jobs`, :guilabel:`academic_persons`,
:guilabel:`academic_persons_edit` or :guilabel:`academic_projects`. Their
content element wizards read the column :sql:`tt_content.list_type`, which
TYPO3 14 removes, so they only run on TYPO3 13. Move to TYPO3 14.3.7 or later
once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-3-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/category-types':'~3.0.0'

On a classic installation, install :guilabel:`environment_state_manager` 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`category_types` there, in this order,
and after them the academic extensions that require it. Run the commands of
this page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

This extension changes no column since 2.3. The comparison applies the
changes of the index definitions TYPO3 13.4.35 and 14.3.7 bring, and the
changes of the academic extensions that require this one.

..  _upgrade-from-2-3-to-3-0-configuration:

Adapt the configuration
=======================

Check every :file:`Configuration/CategoryTypes.yaml` of a site package or of a
third party extension before the update. The changes of
:ref:`upgrade-from-2-3-to-3-0-unique-identifiers` and
:ref:`upgrade-from-2-3-to-3-0-groups` stop the loading of the category types
with an exception, in the backend and in the frontend, and the one of
:ref:`upgrade-from-2-3-to-3-0-priority` reorders the types.

..  _upgrade-from-2-3-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. Run it on the staging copy right after the
package update:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key it also compares template overrides with the templates
this extension ships. This extension ships no view root paths, so the check
cannot read its overrides from a site with :bash:`--site`. Name their folder
instead:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check category_types --override-path=<folder>

See the `upgrade check
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/UpgradeCheck/Index.html>`__
of :guilabel:`academic_base`.

..  _upgrade-from-2-3-to-3-0-unique-identifiers:

Category type identifiers
-------------------------

A category type identifier may be declared in one group only. Two groups that
declare the same identifier, compared without surrounding whitespace and
ignoring case, raise the exception `1790505412`, which names the identifier,
the groups and the extensions that declared it. A declaration in the same
group, with or without :yaml:`useExisting`, is no collision.

Rename one of the two types in the :file:`Configuration/CategoryTypes.yaml`
that declares it, and move the stored categories of that type with an update
of :sql:`sys_category.type`. A package that does not need one of the two types
removes it with :yaml:`remove: true` instead. See :ref:`breaking-1790505413`
and :ref:`developers-tca-unique`.

The one collision of the shipped extensions, the type `department` of
:guilabel:`academic_programs` and :guilabel:`academic_projects`, is resolved in
3.0 by renaming the projects type to `project_department`. The stored
categories move with a command of :guilabel:`academic_projects`, described in
its `upgrade chapter <https://docs.typo3.org/p/fgtclb/academic-projects/main/en-us/Upgrade/From23To30.html>`__.

..  _upgrade-from-2-3-to-3-0-groups:

Category type groups
--------------------

The :yaml:`groups` list of a :file:`Configuration/CategoryTypes.yaml` is read
now. A group with a :yaml:`title` is headed with it in the type select of a
category, and a group without one is headed with its key, as before, after the
groups that have a title. The groups of :guilabel:`academic_partners`,
:guilabel:`academic_programs` and :guilabel:`academic_projects` have a title.

A :yaml:`groups` entry without an :yaml:`identifier` stops the loading with the
exception `1790592001`. Before, the whole list was ignored, so an entry that
never had an effect can break the installation now. Add the identifier or
remove the entry. See :ref:`feature-1790592002` and
:ref:`developers-category-types-groups`.

..  _upgrade-from-2-3-to-3-0-priority:

Priority of a category type
---------------------------

The :yaml:`priority` of a category type decides its place in its group, the
highest first. In 2.3 it was stored and ignored. A
:file:`Configuration/CategoryTypes.yaml` of a site package or of a third party
extension that already sets a :yaml:`priority` therefore reorders the types of
its group: in the type select of a category, in the page module and in the
frontend outputs that list the types. No category type shipped with the
academic extensions declares one.

Remove the :yaml:`priority` to keep the order of the declaration, or check that
the new order is the intended one. See :ref:`important-1790499574`.

A project that wants another order of the types an extension ships can set it
with an override that declares only the priority, see
:ref:`feature-1790499573`.

The category type registry and the groups are cached, so the changes of this
section show once the caches are flushed.

..  _upgrade-from-2-3-to-3-0-templates:

Adapt templates and assets
==========================

No template, partial or layout this extension shipped in 2.3 changed or was
removed. 2.4 added the backend template
:file:`Resources/Private/Templates/PageCategorySummary.html` of the page module
category summary.

What changes is the output of the view helpers in the templates of a project:

*   A template that passes `optionValueField` to `<ct:form.filterSelect>` with
    anything other than `uid` gets the value of that property as the value of
    every option. In 2.3 the option value stayed the uid. The list plugins of
    :guilabel:`academic_partners`, :guilabel:`academic_programs` and
    :guilabel:`academic_projects` read category uids from the submitted
    filter, so a template that feeds one of them keeps
    `optionValueField="uid"`, as their shipped partials do. See
    :ref:`breaking-1785706800`, which also describes the `value` key an own
    :php:`getOptions()` needs, see :ref:`upgrade-from-2-3-to-3-0-code`.
*   A category type read by name, :html:`{categories.researchField}` for
    example, resolves on its first use. A template that accessed
    :html:`{categories.allCategoriesByType}` only to make such an expression
    work can drop that access, see :ref:`important-1785704400`.

The icon of every category type and group is registered in the frontend icon
registry of :guilabel:`academic_base` as well, under the identifier it has in
the backend. The templates of :guilabel:`academic_partners`,
:guilabel:`academic_programs` and :guilabel:`academic_projects` render the
icons with the icon view helper of :guilabel:`academic_base` now. A template
override that still renders them with :html:`<core:icon>` keeps showing the
backend icon, and does not see a :yaml:`frontendIcon` or an entry of a
:file:`Configuration/FrontendIcons.php`. A site package that wants another
frontend drawing for a shipped type registers its identifier in its own
:file:`Configuration/FrontendIcons.php`. See :ref:`feature-1791067923` and
:ref:`developers-icons-frontend-replace`.

..  _upgrade-from-2-3-to-3-0-code:

Adapt your code
===============

..  _upgrade-from-2-3-to-3-0-select-view-helpers:

The select view helpers
-----------------------

A subclass of
:php:`\FGTCLB\CategoryTypes\ViewHelpers\Form\AbstractSelectViewHelper`, of
:php:`FilterSelectViewHelper` or of one of the :php:`SortingSelectViewHelper`
classes of the academic extensions has to follow the structure of 2.4, in
which :php:`renderOptionTags()` hands every option to :php:`renderOptionTag()`:

*   An override of :php:`renderOptionTag()` takes the fourth parameter
    :php:`array $attributes = []`. Without it PHP raises a fatal error.
*   An override of :php:`FilterSelectViewHelper::getOptions()` adds the keys
    `value` and `attributes` to every option, or its options render without a
    value, without the level class and without the disabled state.
*   An override of :php:`renderOptionTags()` that writes its own markup escapes
    nothing unless it does so itself. Let it render through
    :php:`renderOptionTag()`, which escapes the value and every attribute, see
    :ref:`important-1785882100`.

See :ref:`breaking-1785882000`.

..  _upgrade-from-2-3-to-3-0-backend-category-view-helper:

The backend category view helper
--------------------------------

:php:`\FGTCLB\CategoryTypes\ViewHelpers\Be\CategoryViewHelper` renders through
a non-static :php:`render()`. A subclass that overrides :php:`renderStatic()`
moves its code to :php:`render()`. Templates using `<ct:be.category>` need no
change. See :ref:`important-1785711600`.

..  _upgrade-from-2-3-to-3-0-collection-repository-registry:

The collection, the repository and the registry
-----------------------------------------------

*   :php:`CategoryCollection::exist()` compares the uid, not every property of
    the object. The protected property :php:`$typeSortedCollection` is gone, a
    subclass reading it calls :php:`getAllCategoriesByType()` instead.
    :php:`CategoryTypeGroup::fromArray()` is static, so a subclass that
    overrides it non-statically has to follow. See
    :ref:`important-1785968400`.
*   :php:`CategoryRepository::getCategoryRootline()` returns an empty array for
    a category it cannot resolve, and the resolvable part of the rootline for a
    broken chain, instead of throwing a :php:`\TypeError`. A caller that has to
    tell the two cases apart checks for the empty array. A guard or a
    :php:`try` around the call can go. See :ref:`important-1785874800`.
*   :php:`CategoryRepository::findByGroupAndUidList()` returns an empty
    collection for an empty uid list on every database. A guard for that case
    can go, see :ref:`important-1785790800`.
*   :php:`CategoryTypeRegistry::getCategoryTypes()` returns the types of one
    group after the other. Code that relied on the old interleaved order of
    groups reads the types per group instead. This is part of
    :ref:`important-1790499574`, linked in
    :ref:`upgrade-from-2-3-to-3-0-priority`.

..  _upgrade-from-2-3-to-3-0-check:

Check the result
================

*   The backend and the frontend load without the exception `1790505412` or
    `1790592001`.
*   The type select of a category heads the types of the partners, programs
    and projects with the titles of their groups, and lists the types of a
    group in the intended order.
*   The category filters and category lists of the partner, program and
    project lists follow the backend :sql:`sorting` of the categories, the
    order of the siblings in the category tree. Categories nobody reordered can
    therefore appear newest first. Arrange them in the backend where the new
    order is not the intended one. See
    :ref:`important-category-lists-follow-the-backend-sorting-order`.
*   On SQLite, a partner, program or project list whose content element
    selects categories shows only the records of those categories, where it
    showed every record in 2.3. See :ref:`important-ace-314-category-types`.
*   The page of an academic program, partner or project shows the category
    summary in the page module, on TYPO3 13 and TYPO3 14, see
    :ref:`feature-1789660802` and :ref:`important-1785708000`.
*   The upgrade check exits with ``0``, without and with the extension
    key, see :ref:`upgrade-from-2-3-to-3-0-upgrade-check`.
