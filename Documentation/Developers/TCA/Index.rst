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
needs no database, which matters because TYPO3 v13 parses every data structure
during bootstrap.
