..  _feature-1790592002:

======================================================
Feature: Category type groups have a title and an icon
======================================================

Description
===========

The type select of a category grouped the types by their :yaml:`group`, and
headed every group with its key: :yaml:`programs`, :yaml:`projects`,
:yaml:`partners`. The :yaml:`groups` list of a
:file:`Configuration/CategoryTypes.yaml` was not read.

It is read now:

..  code-block:: yaml
    :caption: EXT:example/Configuration/CategoryTypes.yaml

    groups:
      - identifier: example
        title: 'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.example.group'
        icon: 'EXT:example/Resources/Public/Icons/CategoryGroups/Example.svg'
        inlineIcon: true

*   The :yaml:`title` heads the types of the group in the type select, in the
    backend language of the editor.
*   The :yaml:`icon` is registered as :php:`category_types.group.<identifier>`,
    with the same choice of provider as a type icon, :yaml:`inlineIcon`
    included. The type select itself shows no group icon.
*   A :yaml:`priority` is read and kept, and has no effect yet.
*   A later package can declare the same group again to change its title or
    icon. It replaces what it declares and keeps the rest.

:php:`CategoryTypeRegistry` gains :php:`getGroups()` and :php:`getGroup()`, and
:php:`CategoryTypeGroup` gains the title, the icon and :yaml:`inlineIcon`.

Impact
======

A group declared with a title is headed with that title in the type select.
Any other group is headed with its key, as before, and now comes after the
groups that have a title, because FormEngine lists those first.

A :yaml:`groups` entry without an :yaml:`identifier` now stops the loading with
a :php:`\FGTCLB\CategoryTypes\Exception\CategoryTypeException`, code
:php:`1790592001`, and with it the backend and the frontend. Before, the whole
list was ignored, so check the :yaml:`groups` entries of your own
:file:`Configuration/CategoryTypes.yaml` files before the update.

The groups are cached in an entry of their own, so a types entry written
before the update does not hide them. Flush the caches after the update, as
after every extension update, so the TCA picks up the titles.

A group must not be named :yaml:`group`: the icons of its types would then
share their identifiers with the group icons.

See the :guilabel:`For Developers` chapter, section :guilabel:`Naming a group`.

.. index:: Backend, PHP-API, ext:category_types
