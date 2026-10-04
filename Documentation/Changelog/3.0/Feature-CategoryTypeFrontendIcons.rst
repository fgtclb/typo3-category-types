..  _feature-1791067923:

==========================================================
Feature: Category type icons in the frontend icon registry
==========================================================

Description
===========

The icon of every category type and group was registered in the icon registry
of TYPO3 only, and the frontend reached it through that backend registry.
Every type and group icon is now registered in the frontend icon registry of
:php:`EXT:academic_base` as well, under the identifier it has in the backend,
for example :php:`category_types.example.degree`. A frontend template renders
it with the icon view helper of :php:`EXT:academic_base`:

..  code-block:: html

    <html xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <ab:icon identifier="category_types.example.degree" />

    </html>

A type or group can name a second file for the frontend with the new optional
keys :yaml:`frontendIcon` and :yaml:`frontendInlineIcon`:

..  code-block:: yaml
    :caption: EXT:example/Configuration/CategoryTypes.yaml

    types:
      - identifier: degree
        title: 'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.example.degree'
        group: example
        icon: 'EXT:example/Resources/Public/Icons/CategoryTypes/Degree.svg'
        inlineIcon: true
        frontendIcon: 'EXT:example/Resources/Public/Icons/CategoryTypes/DegreeFrontend.svg'
        frontendInlineIcon: true

*   The frontend shows :yaml:`frontendIcon`, or :yaml:`icon` when there is
    none. The backend always shows :yaml:`icon`.
*   A declared :yaml:`frontendInlineIcon` decides whether the frontend inlines
    the file it shows. Without it, the frontend follows :yaml:`inlineIcon`
    while it shows :yaml:`icon`, and shows a :yaml:`frontendIcon` as an image.
*   An override with :yaml:`useExisting`, or a later declaration of a group,
    that names a new :yaml:`frontendIcon` without :yaml:`frontendInlineIcon`
    shows the new file as an image.
*   A site package replaces a category type icon for the frontend only by
    registering its identifier in its own
    :file:`Configuration/FrontendIcons.php`. Such an entry wins over every
    :file:`Configuration/CategoryTypes.yaml`.
*   A type without any icon file has no frontend icon, and the frontend shows
    the placeholder for an unknown icon instead of failing.

:php:`CategoryType` and :php:`CategoryTypeGroup` gain
:php:`getFrontendIcon()` and :php:`isFrontendInlineIcon()`.

Impact
======

Nothing changes for an installation that declares neither new key: the
frontend registry carries the same file and the same provider as the backend
registry, apart from a type without an icon file, which has no frontend
entry. The templates of :php:`EXT:academic_programs`,
:php:`EXT:academic_partners` and :php:`EXT:academic_projects` render the
category type icons with the icon view helper of :php:`EXT:academic_base`, so a
:yaml:`frontendIcon` or a :file:`Configuration/FrontendIcons.php` entry reaches
them. A template override that still renders them with :html:`<core:icon>`
reads the backend registry and does not see either.

An integrator who wants another frontend drawing for a shipped type registers
its identifier in :file:`Configuration/FrontendIcons.php` of the site package,
rather than overriding the type. An override that replaces
:yaml:`frontendIcon` and wants the new file inlined says
:yaml:`frontendInlineIcon: true` again.

Flush the caches after the update, as after every update.

See the :guilabel:`For Developers` chapter, section :guilabel:`Category type
icons`.

..  index:: Frontend, Fluid, PHP-API, ext:category_types
