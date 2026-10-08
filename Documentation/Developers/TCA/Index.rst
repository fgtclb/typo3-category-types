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
*   :php:`itemGroups` maps every :ref:`declared group
    <developers-category-types-groups>` with a title to that title, so the type
    select heads the types of a group with it. A group without a title keeps
    its key as the heading. FormEngine lists the groups of :php:`itemGroups`
    first, in the order they were declared, and then the groups it only finds
    in the items.
*   :php:`ctrl.typeicon_classes` maps every identifier to its icon identifier,
    so the record icon follows the type.
*   The :sql:`type` field is shown before :sql:`title` in every record type.

An extension that declares types therefore adds no :php:`addTcaSelectItem()`
call and no :php:`typeicon_classes` entry of its own. Items added by hand are
unknown to :php:`CategoryTypeRegistry`: they get no registered icon and no
:php:`CategoryType` object, and the :php:`typeicon_classes` list is assigned as
a whole when this extension builds it.

..  _developers-tca-unique:

Identifiers are unique across groups
------------------------------------

The :sql:`type` column stores the identifier only, not the group. Two groups
declaring the same identifier would produce two items with the same value, and
a record carrying that value could not tell which of the two it is. Loading the
category types therefore fails when an identifier is declared in more than one
group, with a :php:`\FGTCLB\CategoryTypes\Exception\CategoryTypeExistException`,
code :php:`1790505412`. Identifiers that differ only in case or in surrounding
whitespace count as the same: MySQL and MariaDB compare the :sql:`type` column
case-insensitively. The message names the identifier, the groups and, per
group, the extension that declared the type last.

The check runs once the :file:`Configuration/CategoryTypes.yaml` of every
package is read, so a package loaded later can resolve a collision by removing
one of the two types, see :ref:`Removing a type
<developers-category-types-remove>`. Declaring a type again within its own
group, or changing it with :yaml:`useExisting`, is no collision.

Until 3.0, `academic_programs` and `academic_projects` both declared
`department`. Since 3.0 the projects type is `project_department`.

..  _developers-tca-type-select:

A select of the types of one group
----------------------------------

A setting that names category types, such as the filters a list offers, gets
its items from :php:`\FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc`
rather than from a fixed item list. It offers one item per type of the group
named in :php:`itemsProcConfig.group`, in :ref:`the order of the types
<developers-category-types-order>`: the title as label, the identifier as
value and the icon of the type as icon. A type a project adds to the group is
offered, and a type it removes is not.

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

..  _developers-tca-group-marker:

Offer the categories of one group in a category field
-----------------------------------------------------

A FlexForm field of :php:`type` :php:`category` can name a group in its
:php:`foreign_table_where`. The marker :php:`###CATEGORY_TYPE_GROUP:<group>###`
is replaced with a subselect of the categories that carry a type of that group,
and of their ancestors, so the category tree offers what a plugin of that group
can use:

..  code-block:: xml
    :caption: EXT:example/Configuration/FlexForms/List.xml

    <settings.categories>
        <config>
            <type>category</type>
            <relationship>oneToMany</relationship>
            <foreign_table_where>AND {#sys_category}.{#uid} IN (###CATEGORY_TYPE_GROUP:example###) AND {#sys_category}.{#sys_language_uid} IN (-1, 0)</foreign_table_where>
        </config>
    </settings.categories>

*   The ancestors are offered because the tree shows a category only together
    with every parent above it. A category of the group below a parent of
    another type, or of none, stays reachable, and that parent can be selected
    as well.
*   Hidden categories are offered, deleted ones are not. The language
    condition of the example keeps translations out.
*   A group without a type, or one no extension declares, resolves to
    :sql:`NULL`, so the tree is empty.
*   The type identifiers are written into the statement literally. An
    identifier that is not made of letters, digits, :php:`_` and :php:`-` is
    left out, and its categories are not offered.
*   The marker is resolved in the fields of a sheet. A category field cannot
    sit inside a section container, core rejects fields with database relations
    there.

The types are read from the :file:`CategoryTypes.yaml` of every installed
extension when the data structure is parsed, so types an integrator adds to the
group are offered without a change of the FlexForm file. The categories
themselves are not looked up then: the subselect is a recursive common table
expression the query of the tree evaluates. Resolving the marker therefore
needs no database, which matters because TYPO3 v13 and v14 parse every data
structure during bootstrap.
