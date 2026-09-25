..  _feature-1790361243:

=======================================================
Feature: A backend select offers the types of one group
=======================================================

Description
===========

A setting that names category types had to list them as fixed items, which
missed a type a project adds to the group and kept one it removes.

:php:`\FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc`
builds the items of a select field from the registered types of the group
named in the field configuration, for a TCA column and a FlexForm field alike:

..  code-block:: php

    'config' => [
        'type' => 'select',
        'renderType' => 'selectMultipleSideBySide',
        'itemsProcFunc' => \FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc::class . '->itemsForGroup',
        'itemsProcConfig' => [
            'group' => 'programs',
        ],
    ],

Each item carries the title of the type as label, its identifier as value and
its icon, in the order the types of the group are registered in. A field
without a group, or with a group no active extension registers, offers no
items.

See :ref:`developers-tca-type-select`.

Impact
======

Nothing changes for an existing field. The field :guilabel:`Filter types` of
the :guilabel:`Program List` of :php:`EXT:academic_programs` is the first to
use it.

..  index:: Backend, TCA, FlexForm, ext:category_types
