..  _developers-category-types:

Declaring category types
========================

A category type is declared in the :file:`Configuration/CategoryTypes.yaml` of
any loaded extension, site packages included. There is no PHP class to write
and no TCA to add: this extension reads that file from every active package
and derives the :sql:`sys_category` type items, the type icons and their icon
identifiers from it.

..  code-block:: yaml
    :caption: EXT:example/Configuration/CategoryTypes.yaml

    types:
      - identifier: degree
        title: 'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.example.degree'
        group: example
        icon: 'EXT:example/Resources/Public/Icons/CategoryTypes/Degree.svg'
      - identifier: topic
        title: 'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.example.topic'
        group: example
        icon: 'EXT:example/Resources/Public/Icons/CategoryTypes/Topic.svg'

Only the :yaml:`types` list is read; any other top-level key is ignored. That
includes a :yaml:`groups` list with a title and an icon per group: it is not
read, and the type select of a category shows the group key as it is. An empty
file is allowed and declares nothing.

..  _developers-category-types-keys:

The keys of a type
------------------

:yaml:`identifier`
    Required, a non-empty string. The value stored in the :sql:`type` column of
    a category record. It is stored without the group, so keep it unique across
    all groups, see :ref:`Keep identifiers unique across groups
    <developers-tca-unique>`.

:yaml:`group`
    Required, a non-empty string. Groups the types of one extension. Together
    with :yaml:`identifier` it identifies the type, and it is part of the icon
    identifier, see :ref:`Category type icons <developers-icons>`.

:yaml:`title`
    The label of the type in the backend, usually an :php:`LLL:` reference.

:yaml:`icon`
    The icon file, as an :php:`EXT:` path.

:yaml:`inlineIcon`
    Optional, :yaml:`false` by default. Asks for an SVG icon that follows the
    text colour, see :ref:`Which provider the icon gets
    <developers-icons-provider>`.

:yaml:`priority`
    Optional integer, :yaml:`0` by default. Orders the types of a group, the
    highest first, see :ref:`The order of the types
    <developers-category-types-order>`.

:yaml:`useExisting`
    Optional, :yaml:`false` by default. Changes a type an earlier loaded
    extension declares instead of declaring it anew, see :ref:`Changing a type
    another extension declares <developers-category-types-override>`.

:yaml:`remove`
    Optional, :yaml:`false` by default. Takes away a type an earlier loaded
    extension declares, see :ref:`Removing a type
    <developers-category-types-remove>`.

A type without :yaml:`identifier` or without :yaml:`group` stops the loading
with an exception, code :php:`1678979375330`.

..  _developers-category-types-order:

The order of the types
----------------------

The types of a group are ordered by :yaml:`priority`, the highest first. Types
with the same priority keep the order in which they were declared: in the order
the packages are loaded, and within one file in the order they are listed. A
type that declares no priority has :yaml:`0`, so as long as no type declares
one, the declaration order is the order.

Every list of the types of a group follows that order: the type select of a
category, the :ref:`select of the types of one group
<developers-tca-type-select>`, the :ref:`page module category summary
<developers-page-module-summary>` and the outputs of the extensions that use
the group, such as the facts and the list filters of the programs.

To move a type another extension declares, give it a priority with
:yaml:`useExisting`, see :ref:`Changing a type another extension declares
<developers-category-types-override>`:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    types:
      - identifier: topic
        group: example
        priority: 10
        useExisting: true

:yaml:`topic` is then the first type of the group :yaml:`example`, ahead of every
type without a priority. A negative priority moves a type behind them. Several
types are ordered by giving each its own priority.

..  _developers-category-types-override:

Changing a type another extension declares
------------------------------------------

A type is addressed by its :yaml:`group` and :yaml:`identifier`, so an
extension can change what an earlier loaded extension declared. The overriding
extension has to be loaded **after** the declaring one: name the package of the
declaring extension in ``require`` of the overriding extension's
:file:`composer.json`, and its extension key in ``depends`` of the
:file:`ext_emconf.php` if there is one.

Declaring the same :yaml:`group` and :yaml:`identifier` again replaces the
earlier declaration as a whole. A key the new declaration leaves out falls back
to its default - an empty title and icon, :yaml:`inlineIcon: false` and
:yaml:`priority: 0` - and is not taken from the earlier declaration.

To change single keys only, add :yaml:`useExisting: true`. The given keys are
then merged into the existing declaration, and every other key keeps its value:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    types:
      - identifier: degree
        group: example
        title: 'LLL:EXT:site_package/Resources/Private/Language/locallang.xlf:sys_category.example.degree'
        useExisting: true

:yaml:`useExisting` for a type that is not declared at that point stops the
loading with the exception "Category type does not exist for override.", code
:php:`1678979375330`. That happens when no extension declares the type, when
the declaring extension is loaded after the overriding one, or when an
extension loaded before the overriding one removed the type.

Redeclared or changed with :yaml:`useExisting`, the type keeps its place in the
declaration order, which decides among types of the same priority, and
:php:`CategoryType::getExtensionKey()` returns the extension that changed it,
not the declaring one. A :yaml:`priority` the override sets moves it, see
:ref:`The order of the types <developers-category-types-order>`; a
redeclaration without :yaml:`useExisting` falls back to :yaml:`priority: 0`.

..  _developers-category-types-override-icon:

Changing only the icon
~~~~~~~~~~~~~~~~~~~~~~

A common override is another icon. Name the new file and leave the title to the
declaring extension, so the label follows that extension when it renames its
label keys:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    types:
      - identifier: degree
        group: example
        icon: 'EXT:site_package/Resources/Public/Icons/CategoryTypes/Degree.svg'
        inlineIcon: false
        useExisting: true

An override that leaves out :yaml:`inlineIcon` keeps the value of the
declaration it changes, so a type that asked for inlining still asks for it
with the new file. Set :yaml:`inlineIcon: false` for a file that does not meet
:ref:`the rules for an inlined icon <developers-icons-svg>`, or
:yaml:`inlineIcon: true` for one that does, see :ref:`Category type icons
<developers-icons>`.

..  _developers-category-types-remove:

Removing a type
~~~~~~~~~~~~~~~

:yaml:`remove: true` takes a declared type away:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    types:
      - identifier: topic
        group: example
        remove: true

A removal acts on what is loaded before it. Removing a type no extension
declares is not an error, and a type declared by an extension loaded after the
removing one is not removed. To remove a type of an extension that is not
always installed, name its package in ``suggest`` of :file:`composer.json` and
its extension key in ``suggests`` of :file:`ext_emconf.php` if there is one,
rather than in ``require`` and ``depends``: it is then loaded first whenever it
is installed.

Records that already carry a removed type keep their value in the database;
the type is only no longer offered and no longer resolved.

..  _developers-category-types-cache:

Caching
-------

The declarations of all packages are read once and kept in the core cache.
Flush the caches after changing a :file:`Configuration/CategoryTypes.yaml`, for
example with :bash:`vendor/bin/typo3 cache:flush`.

..  _developers-category-types-php:

Reading the types in PHP
------------------------

:php:`\FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry` is a service holding
every declared type. Inject it rather than instantiating it:

..  code-block:: php
    :caption: EXT:example/Classes/Service/DegreeType.php

    <?php

    declare(strict_types=1);

    namespace FGTCLB\Example\Service;

    use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
    use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;

    final class DegreeType
    {
        public function __construct(
            private readonly CategoryTypeRegistry $categoryTypeRegistry,
        ) {}

        public function get(): ?CategoryType
        {
            return $this->categoryTypeRegistry->getCategoryType('example', 'degree');
        }
    }

:php:`getCategoryType()`
    One type by group and identifier, or :php:`null`.

:php:`getCategoryTypes()`
    All types, group by group in the order the groups were first declared,
    and each group in :ref:`its order <developers-category-types-order>`.

:php:`getCategoryTypesByGroup()`
    The types of one group in their order, keyed by identifier. A group no
    extension declared a type for throws an :php:`\InvalidArgumentException`,
    code :php:`1683633304209`.

A :php:`\FGTCLB\CategoryTypes\Domain\Model\CategoryType` exposes the declared
values through :php:`getIdentifier()`, :php:`getGroup()`, :php:`getTitle()`,
:php:`getIcon()`, :php:`isInlineIcon()` and :php:`getPriority()`, plus
:php:`getExtensionKey()` for the extension that declared the type or changed it
last, and :php:`getIconIdentifier()` for the registered icon.
