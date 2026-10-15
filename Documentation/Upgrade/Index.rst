..  _upgrade:

=======
Upgrade
=======

This chapter lists, for each upgrade path, the steps for
:guilabel:`category_types` in the order to take them. Each step links
the changelog entry with the detail.

..  list-table::
    :header-rows: 1

    *   -   Installed version
        -   Target version
        -   Page
    *   -   2.3
        -   2.4
        -   :ref:`upgrade-from-2-3-to-2-4`
    *   -   2.4
        -   3.0
        -   :ref:`upgrade-from-2-4-to-3-0`
    *   -   2.3
        -   3.0
        -   :ref:`upgrade-from-2-3-to-3-0`

The file :file:`UPGRADE.md` of the package describes the older upgrade that
replaced the enumeration classes by the file
:file:`Configuration/CategoryTypes.yaml`. That upgrade led from version 1.x to
2.0.

..  _upgrade-before-you-start:

Before you start
================

*   Make a copy of the database and of :file:`fileadmin/`, so the upgrade can
    be repeated or rolled back.
*   Run the upgrade on a staging copy first, and on the live installation once
    the staging copy works.
*   Upgrade all academic extensions of an installation together, in one
    deployment, to the same release.
*   :guilabel:`category_types` requires :guilabel:`academic_base`. Upgrade it
    first, by the upgrade chapter of its own manual.

..  toctree::
    :hidden:
    :titlesonly:

    From23To24
    From24To30
    From23To30
