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

Only the :yaml:`types` list is read; any other top-level key is ignored. An
empty file is allowed and declares nothing.

..  _developers-category-types-keys:

The keys of a type
------------------

:yaml:`identifier`
    Required, a non-empty string. The value stored in the :sql:`type` column of
    a category record.

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
    Optional integer, :yaml:`0` by default. It is stored with the type and
    returned by :php:`CategoryType::getPriority()`, but nothing in this
    extension sorts by it.

A type without :yaml:`identifier` or without :yaml:`group` stops the loading
with an exception, code :php:`1678979375330`.

The types keep the order in which they were declared: in the order the packages
are loaded, and within one file in the order they are listed.

..  _developers-category-types-override:

Changing a type another extension declares
------------------------------------------

A type is addressed by its :yaml:`group` and :yaml:`identifier`, so an
extension can change what an earlier loaded extension declared. The extension
has to be loaded **after** the declaring one, so declare the dependency in its
:file:`composer.json`.

Declaring the same :yaml:`group` and :yaml:`identifier` again replaces the
earlier declaration as a whole. To change single keys only, add
:yaml:`useExisting: true`; the given keys are then merged into the existing
declaration:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    types:
      - identifier: degree
        group: example
        title: 'LLL:EXT:site_package/Resources/Private/Language/locallang.xlf:sys_category.example.degree'
        useExisting: true

:yaml:`useExisting` for a type no earlier extension declared stops the loading
with an exception, code :php:`1678979375330`.

:yaml:`remove: true` takes a declared type away:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    types:
      - identifier: topic
        group: example
        remove: true

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
:php:`getIcon()`, :php:`isInlineIcon()` and :php:`getPriority()`, plus
:php:`getExtensionKey()` for the declaring extension and
:php:`getIconIdentifier()` for the registered icon.
