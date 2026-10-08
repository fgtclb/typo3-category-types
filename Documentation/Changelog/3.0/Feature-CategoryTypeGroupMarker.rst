..  _feature-category-type-group-marker:

===============================================================
Feature: A category field can offer the categories of one group
===============================================================

Description
===========

A FlexForm field of type :php:`category` can name a category type group in its
:php:`foreign_table_where` with the marker
:php:`###CATEGORY_TYPE_GROUP:<group>###`:

..  code-block:: xml

    <foreign_table_where>AND {#sys_category}.{#uid} IN (###CATEGORY_TYPE_GROUP:partners###) AND {#sys_category}.{#sys_language_uid} IN (-1, 0)</foreign_table_where>

When the data structure is parsed, the marker is replaced with a subselect of
the categories that carry a type of the group, and of their ancestors, so the
category tree stays navigable when a category of the group sits below a parent
of another type. The types are read from the category type registry, so types
an integrator adds to a group are offered without a change of the FlexForm
file. See :ref:`developers-tca-group-marker`.

Impact
======

The list plugins of :composer:`fgtclb/academic-partners`,
:composer:`fgtclb/academic-programs` and :composer:`fgtclb/academic-projects`
use the marker for their category field.

..  index:: Backend, FlexForm, ext:category_types
