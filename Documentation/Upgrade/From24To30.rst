..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 drops TYPO3 12, requires a category type identifier to be unique
across all groups, reads the :yaml:`groups` of a
:file:`Configuration/CategoryTypes.yaml` and registers the icons of the types
and groups for the frontend as well. Most of it needs no action, except an own
:file:`Configuration/CategoryTypes.yaml` that declares an identifier another
group has, or a :yaml:`groups` entry without an identifier. Work through the
sections in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`category_types` 3.0 declares no new dependency of its own. It needs
these packages. Upgrade them first or in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, which
    :guilabel:`academic_base` 3.0 requires. Version 2.4 required 1.x. Composer
    updates it on its own. A classic installation updates
    :guilabel:`environment_state_manager` before it activates
    :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.

:guilabel:`academic_partners`, :guilabel:`academic_programs` and
:guilabel:`academic_projects` require this extension in the same release and
follow it in the same deployment.

These changes have to ship in the same deployment as the update, because the
category types stop loading until they are made:

*   A :file:`Configuration/CategoryTypes.yaml` that declares an identifier
    another group declares as well renames one of the two types, see
    :ref:`upgrade-from-2-4-to-3-0-unique-identifiers`.
*   A :yaml:`groups` entry without an :yaml:`identifier` gets one or goes, see
    :ref:`upgrade-from-2-4-to-3-0-groups`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-4-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.4, which supports
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

..  _upgrade-from-2-4-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/category-types':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`category_types` there, in this order,
and after them the academic extensions that require it. Run the commands of
this page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-4-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

This extension changes no column in 3.0. The comparison applies the changes of
the index definitions TYPO3 13.4.35 and 14.3.7 bring, and the changes of the
academic extensions that require this one.

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

Check every :file:`Configuration/CategoryTypes.yaml` of a site package or of a
third party extension before the update. The changes of
:ref:`upgrade-from-2-4-to-3-0-unique-identifiers` and
:ref:`upgrade-from-2-4-to-3-0-groups` stop the loading of the category types
with an exception, in the backend and in the frontend.

..  _upgrade-from-2-4-to-3-0-upgrade-check:

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

..  _upgrade-from-2-4-to-3-0-unique-identifiers:

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
its `upgrade chapter <https://docs.typo3.org/p/fgtclb/academic-projects/main/en-us/Upgrade/From24To30.html>`__.

..  _upgrade-from-2-4-to-3-0-groups:

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

The groups are cached in an entry of their own. Flush the caches after the
update, so the type select of a category shows the titles.

..  _upgrade-from-2-4-to-3-0-templates:

Adapt templates and assets
==========================

No template, partial or layout of this extension changed or was removed in 3.0.

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

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   The backend and the frontend load without the exception `1790505412` or
    `1790592001`.
*   The type select of a category heads the types of the partners, programs
    and projects with the titles of their groups.
*   On TYPO3 14, the page module shows the category summary of an academic
    program, partner or project page, see :ref:`important-1785708000`.
*   The upgrade check exits with ``0``, without and with the extension
    key, see :ref:`upgrade-from-2-4-to-3-0-upgrade-check`.
