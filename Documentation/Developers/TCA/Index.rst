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
