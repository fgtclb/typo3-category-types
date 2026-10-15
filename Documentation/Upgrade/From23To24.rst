..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 orders category types by their priority and categories by their
backend sorting, gives the category filter select the arguments of the core
select view helper and renders every option through one method. Most of it
needs no action, except a :yaml:`priority` in an own
:file:`Configuration/CategoryTypes.yaml`, a template that passes
``optionValueField`` to the filter select, and PHP that extends the view
helpers or the collection of this extension. Work through the sections in
order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`category_types` 2.4 declares no new dependency of its own. It needs
these packages. Upgrade them first or in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 1.x, new in 2.4, which
    :guilabel:`academic_base` 2.4 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 2.4.
*   :composer:`fgtclb/academic-base` 2.4, the same release as this extension.

:guilabel:`academic_partners`, :guilabel:`academic_programs` and
:guilabel:`academic_projects` require this extension in the same release and
follow it in the same deployment.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0' \
        'fgtclb/category-types':'~2.4.0'

A project that requires branch ``2`` as ``2.x-dev`` keeps an inline alias such
as ``2.x-dev as 2.4.x-dev`` working, see :ref:`important-1790796670`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`category_types` there, in this order,
and after them the academic extensions that require it. Run the commands of
this page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-configuration:

Adapt the configuration
=======================

The :yaml:`priority` of a category type decides its place in its group now,
the highest first. It used to be stored and ignored. A
:file:`Configuration/CategoryTypes.yaml` of a site package or of a third party
extension that already sets a :yaml:`priority` therefore reorders the types of
its group: in the type select of a category, in the page module and in the
frontend outputs that list the types. No category type shipped with the
academic extensions declares one.

Remove the :yaml:`priority` to keep the order of the declaration, or check that
the new order is the intended one. See :ref:`important-1790499574`.

A project that wants another order of the types an extension ships can set it
with an override that declares only the priority, see
:ref:`feature-1790499573`.

The category type registry is built from the
:file:`Configuration/CategoryTypes.yaml` files of all extensions and cached, so
a changed order of the types shows once the caches are flushed.

..  _upgrade-from-2-3-to-2-4-templates:

Adapt templates and assets
==========================

No template, partial or layout this extension shipped in 2.3 changed or was
removed. 2.4 adds the backend template
:file:`Resources/Private/Templates/PageCategorySummary.html` of the page module
category summary.

What changes is the output of the view helpers in the templates of a project:

*   A template that passes `optionValueField` to `<ct:form.filterSelect>` with
    anything other than `uid` gets the value of that property as the value of
    every option now. Before, the option value stayed the uid. The list
    plugins of :guilabel:`academic_partners`, :guilabel:`academic_programs`
    and :guilabel:`academic_projects` read category uids from the submitted
    filter, so a template that feeds one of them keeps
    `optionValueField="uid"`, as their shipped partials do. See
    :ref:`breaking-1785706800`, which also describes the `value` key an own
    :php:`getOptions()` needs, see :ref:`upgrade-from-2-3-to-2-4-code`.
*   A category type read by name, :html:`{categories.researchField}` for
    example, resolves on its first use now. A template that accessed
    :html:`{categories.allCategoriesByType}` only to make such an expression
    work can drop that access, see :ref:`important-1785704400`.

..  _upgrade-from-2-3-to-2-4-code:

Adapt your code
===============

..  _upgrade-from-2-3-to-2-4-select-view-helpers:

The select view helpers
-----------------------

A subclass of
:php:`\FGTCLB\CategoryTypes\ViewHelpers\Form\AbstractSelectViewHelper`, of
:php:`FilterSelectViewHelper` or of one of the :php:`SortingSelectViewHelper`
classes of the academic extensions has to follow the new structure, in which
:php:`renderOptionTags()` hands every option to :php:`renderOptionTag()`:

*   An override of :php:`renderOptionTag()` takes the fourth parameter
    :php:`array $attributes = []`. Without it PHP raises a fatal error.
*   An override of :php:`FilterSelectViewHelper::getOptions()` adds the keys
    `value` and `attributes` to every option, or its options render without a
    value, without the level class and without the disabled state.
*   An override of :php:`renderOptionTags()` that writes its own markup escapes
    nothing unless it does so itself. Let it render through
    :php:`renderOptionTag()`, which escapes the value and every attribute, see
    :ref:`important-1785882100`.

See :ref:`breaking-1785882000`.

..  _upgrade-from-2-3-to-2-4-backend-category-view-helper:

The backend category view helper
--------------------------------

:php:`\FGTCLB\CategoryTypes\ViewHelpers\Be\CategoryViewHelper` renders through
a non-static :php:`render()` now. A subclass that overrides
:php:`renderStatic()` moves its code to :php:`render()`. Templates using
`<ct:be.category>` need no change. See :ref:`important-1785711600`.

..  _upgrade-from-2-3-to-2-4-collection-repository-registry:

The collection, the repository and the registry
-----------------------------------------------

*   :php:`CategoryCollection::exist()` compares the uid now, not every property
    of the object. The protected property :php:`$typeSortedCollection` is gone,
    a subclass reading it calls :php:`getAllCategoriesByType()` instead.
    :php:`CategoryTypeGroup::fromArray()` is static, so a subclass that
    overrides it non-statically has to follow. See
    :ref:`important-1785968400`.
*   :php:`CategoryRepository::getCategoryRootline()` returns an empty array for
    a category it cannot resolve, and the resolvable part of the rootline for a
    broken chain, instead of throwing a :php:`\TypeError`. A caller that has to
    tell the two cases apart checks for the empty array. A guard or a
    :php:`try` around the call can go. See :ref:`important-1785874800`.
*   :php:`CategoryRepository::findByGroupAndUidList()` returns an empty
    collection for an empty uid list on every database. A guard for that case
    can go, see :ref:`important-1785790800`.
*   :php:`CategoryTypeRegistry::getCategoryTypes()` returns the types of one
    group after the other. Code that relied on the old interleaved order of
    groups reads the types per group instead. This is part of
    :ref:`important-1790499574`, linked in
    :ref:`upgrade-from-2-3-to-2-4-configuration`.

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

*   The category filters and category lists of the partner, program and
    project lists follow the backend :sql:`sorting` of the categories, the
    order of the siblings in the category tree. Categories nobody reordered can
    therefore appear newest first. Arrange them in the backend where the new
    order is not the intended one. See
    :ref:`important-category-lists-follow-the-backend-sorting-order`.
*   On SQLite, a partner, program or project list whose content element
    selects categories shows only the records of those categories now, where it
    showed every record before. See :ref:`important-ace-314-category-types`.
*   The type select of a category, the page module and the frontend outputs
    list the types of a group in the intended order.
*   The page of an academic program, partner or project shows the category
    summary in the page module, see :ref:`feature-1789660802`.
