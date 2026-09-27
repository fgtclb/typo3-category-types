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

:yaml:`priority`
    Optional integer, :yaml:`0` by default. It is stored with the type and
    returned by :php:`CategoryType::getPriority()`, but nothing in this
    extension sorts by it.

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

The types keep the order in which they were declared: in the order the packages
are loaded, and within one file in the order they are listed.

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
to its default - an empty title and icon and :yaml:`priority: 0` - and is not
taken from the earlier declaration.

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

Redeclared or changed with :yaml:`useExisting`, the type keeps its position
among the declared types and in the type select, and
:php:`CategoryType::getExtensionKey()` returns the extension that changed it,
not the declaring one.

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
        useExisting: true

The icon identifier is derived from the group and the identifier, so it stays
the same, and every template and :php:`typeicon_classes` entry that addresses it
keeps working, see :ref:`Category type icons <developers-icons>`.

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
    All types, in declaration order.

:php:`getCategoryTypesByGroup()`
    The types of one group, keyed by identifier. A group no extension declared a
    type for throws an :php:`\InvalidArgumentException`, code
    :php:`1683633304209`.

A :php:`\FGTCLB\CategoryTypes\Domain\Model\CategoryType` exposes the declared
values through :php:`getIdentifier()`, :php:`getGroup()`, :php:`getTitle()`,
:php:`getIcon()` and :php:`getPriority()`, plus
:php:`getExtensionKey()` for the extension that declared the type or changed it
last, and :php:`getIconIdentifier()` for the registered icon.
