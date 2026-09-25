..  _developers-tca:

TCA integration
===============

This extension changes the :sql:`sys_category` TCA itself, in its
:file:`Configuration/TCA/Overrides/sys_category.php`, from the declared
:ref:`category types <developers-category-types>`:

*   :php:`ctrl.type` and :php:`ctrl.typeicon_column` point to the :sql:`type`
    column.
*   The :sql:`type` column is a :php:`selectSingle` field. Its first item is
    :php:`default`, followed by one item per declared type, with the title as
    label, the identifier as value, the icon identifier as icon and the group as
    item group.
*   :php:`ctrl.typeicon_classes` maps every identifier to its icon identifier,
    so the record icon follows the type.
*   The :sql:`type` field is shown before :sql:`title` in every record type.

An extension that declares types therefore adds no :php:`addTcaSelectItem()`
call and no :php:`typeicon_classes` entry of its own. Items added by hand are
unknown to :php:`CategoryTypeRegistry`: they get no registered icon and no
:php:`CategoryType` object, and the :php:`typeicon_classes` list is assigned as
a whole when this extension builds it.

..  _developers-tca-unique:

Keep identifiers unique across groups
-------------------------------------

The :sql:`type` column stores the identifier only, not the group. Two groups
declaring the same identifier produce two items with the same value, and a
record carrying that value cannot tell which of the two it is. Keep type
identifiers unique across all groups of all installed extensions.

..  _developers-tca-type-select:

A select of the types of one group
----------------------------------

A setting that names category types, such as the filters a list offers, gets
its items from :php:`\FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc`
rather than from a fixed item list. It offers one item per type of the group
named in :php:`itemsProcConfig.group`, in the order the types are registered
in: the title as label, the identifier as value and the icon of the type as
icon. A type a project adds to the group is offered, and a type it removes is
not.

..  code-block:: php
    :caption: EXT:example/Configuration/TCA/Overrides/tx_example_domain_model_list.php

    $GLOBALS['TCA']['tx_example_domain_model_list']['columns']['filter_types'] = [
        'label' => 'LLL:EXT:example/Resources/Private/Language/locallang_db.xlf:filter_types',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'itemsProcFunc' => \FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc::class . '->itemsForGroup',
            'itemsProcConfig' => [
                'group' => 'example',
            ],
            'maxitems' => 999,
        ],
    ];

The same configuration works in a FlexForm:

..  code-block:: xml
    :caption: EXT:example/Configuration/FlexForms/List.xml

    <settings.filter.categoryTypes>
        <label>LLL:EXT:example/Resources/Private/Language/locallang_be.xlf:filter_types</label>
        <config>
            <type>select</type>
            <renderType>selectMultipleSideBySide</renderType>
            <itemsProcFunc>FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc->itemsForGroup</itemsProcFunc>
            <itemsProcConfig>
                <group>example</group>
            </itemsProcConfig>
            <maxitems>999</maxitems>
        </config>
    </settings.filter.categoryTypes>

The side by side select stores the identifiers as a comma separated list, in
the order the editor arranged them. A field without a group, or with a group no
active extension registers, offers no items and shows no error.

A stored identifier the group no longer has is no item any more: FormEngine
does not show it as selected, and the next save of the record removes it. Code
reading the value has to ignore an identifier it does not know, as it has to
for a record saved before the type was removed.
