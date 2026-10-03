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

The :yaml:`types` list declares the types. An optional :yaml:`groups` list
gives the groups they use a title and an icon, see :ref:`Naming a group
<developers-category-types-groups>`. Any other top-level key is ignored. An
empty file is allowed and declares nothing.

..  _developers-category-types-keys:

The keys of a type
------------------

:yaml:`identifier`
    Required, a non-empty string. The value stored in the :sql:`type` column of
    a category record. It is stored without the group, so it has to be unique
    across all groups, see :ref:`Identifiers are unique across groups
    <developers-tca-unique>`.

:yaml:`group`
    Required, a non-empty string. Groups the types of one extension. Together
    with :yaml:`identifier` it identifies the type, and it is part of the icon
    identifier, see :ref:`Category type icons <developers-icons>`.

:yaml:`title`
    The label of the type in the backend, usually an :php:`LLL:` reference. A
    literal title is shown as written. The frontend shows it for a type the
    extension of the group has no label for, see :ref:`Naming a type in a
    template <developers-category-types-title>`.

:yaml:`icon`
    The icon file, as an :php:`EXT:` path.

:yaml:`inlineIcon`
    Optional, :yaml:`false` by default. Asks for an SVG icon that follows the
    text colour, see :ref:`Which provider the icon gets
    <developers-icons-provider>`.

:yaml:`frontendIcon`
    Optional. Another icon file for the frontend, as an :php:`EXT:` path. The
    frontend shows :yaml:`icon` without it, see :ref:`A file of its own for
    the frontend <developers-icons-frontend>`.

:yaml:`frontendInlineIcon`
    Optional boolean. Whether the frontend inlines the file it shows. Without
    it, the frontend follows :yaml:`inlineIcon` while it shows the
    :yaml:`icon` file and shows a :yaml:`frontendIcon` as an image, see
    :ref:`A file of its own for the frontend <developers-icons-frontend>`.

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
with an exception, code :php:`1678979375330`. So does an identifier that more
than one group declares, with code :php:`1790505412`.

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
with the new file, in the backend and in a frontend that shows :yaml:`icon`.
Set :yaml:`inlineIcon: false` for a file that does not meet :ref:`the rules
for an inlined icon <developers-icons-svg>`, or :yaml:`inlineIcon: true` for
one that does, see :ref:`Category type icons <developers-icons>`. A new
:yaml:`icon` does not change a :yaml:`frontendIcon` the type declares.

:yaml:`frontendIcon` follows a different rule. An override that names a new
:yaml:`frontendIcon` without :yaml:`frontendInlineIcon` shows the new file as
an image, it does not inherit the flag the declaration set for its own
frontend file. Say :yaml:`frontendInlineIcon: true` again for a file that is
drawn for inlining:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    types:
      - identifier: degree
        group: example
        frontendIcon: 'EXT:site_package/Resources/Public/Icons/CategoryTypes/DegreeFrontend.svg'
        frontendInlineIcon: true
        useExisting: true

An override that names only :yaml:`frontendInlineIcon` applies it to the file
the frontend already shows. To replace the frontend drawing of a type without
touching its declaration, register its icon identifier in the
:file:`Configuration/FrontendIcons.php` of the site package instead, see
:ref:`Replacing an icon in the frontend only
<developers-icons-frontend-replace>`.

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

..  _developers-category-types-groups:

Naming a group
--------------

The type select of a category shows a heading above the types of each group.
Without a declaration, the heading is the bare group key, here
:yaml:`example`. The :yaml:`groups` list gives a group a title:

..  code-block:: yaml
    :caption: EXT:example/Configuration/CategoryTypes.yaml

    groups:
      - identifier: example
        title: 'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.example.group'
        icon: 'EXT:example/Resources/Public/Icons/CategoryGroups/Example.svg'
        inlineIcon: true
    types:
      - identifier: degree
        title: 'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.example.degree'
        group: example
        icon: 'EXT:example/Resources/Public/Icons/CategoryTypes/Degree.svg'

:yaml:`identifier`
    Required, a non-empty string: the key the types name in :yaml:`group`. A
    group without it stops the loading with a
    :php:`\FGTCLB\CategoryTypes\Exception\CategoryTypeException`, code
    :php:`1790592001`.

:yaml:`title`
    The heading of the group in the type select, usually an :php:`LLL:`
    reference, translated into the backend language of the editor.

:yaml:`icon`
    An icon file, registered as :php:`category_types_group.<identifier>`, see
    :ref:`Group icons <developers-icons-groups>`. The type select shows no
    group icon: the option groups of a select carry a label only.

:yaml:`inlineIcon`
    Optional boolean, :yaml:`false` by default. As for a type, see
    :ref:`Which provider the icon gets <developers-icons-provider>`.

:yaml:`frontendIcon`, :yaml:`frontendInlineIcon`
    Optional. As for a type, see :ref:`A file of its own for the frontend
    <developers-icons-frontend>`.

:yaml:`priority`
    Optional integer, :yaml:`0` by default. It is read and kept on the group,
    but has no effect yet: groups keep the order in which they were first
    declared, which is the load order of their packages.

A group does not have to be declared for its types to work, and a declared
group does not need a type. The type select leaves out a group without types.

More than one package can declare the same group. A later package replaces
the title, the icon, :yaml:`inlineIcon`, :yaml:`frontendIcon`,
:yaml:`frontendInlineIcon` and :yaml:`priority` it declares and keeps what it
leaves out, and the group keeps its position. As for a type, a new
:yaml:`frontendIcon` without :yaml:`frontendInlineIcon` is shown as an image,
while a new :yaml:`icon` keeps the earlier :yaml:`inlineIcon`. A site package
that requires :php:`EXT:academic_programs` relabels its group like this:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    groups:
      - identifier: programs
        title: 'LLL:EXT:site_package/Resources/Private/Language/locallang_be.xlf:sys_category.programs.group'

..  _developers-category-types-title:

Naming a type in a template
---------------------------

The templates of `EXT:academic_programs`, `EXT:academic_partners` and
`EXT:academic_projects` name a type by the label
:xml:`sys_category.<group>.<identifier>` of their language file. That file only
knows the types the extension ships. For any other type, the view helper
:html:`ct:categoryTypeTitle` returns the registered :yaml:`title` in the
language of the page: the translation of an :php:`LLL:` reference, a literal
title as written. It returns an empty string for a type that is not registered
in the group.

The templates hand it the label as its content. It renders the content when the
content is not empty, so a label of the extension or one a site sets through
:typoscript:`_LOCAL_LANG` still wins, and the title otherwise:

..  code-block:: html
    :caption: EXT:site_package/Resources/Private/Partials/Program/DemandCategories.html

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:ct="http://typo3.org/ns/FGTCLB/CategoryTypes/ViewHelpers"
          data-namespace-typo3-fluid="true">

    {f:translate(key: 'sys_category.programs.{categoryKey}', extensionName: 'AcademicPrograms')
        -> ct:categoryTypeTitle(group: 'programs', identifier: categoryKey)}

Both arguments are required:

:html:`group`
    The group of the type, for example `programs`.

:html:`identifier`
    The identifier of the type.

Do not pass the title as the :html:`default` of :html:`f:translate` instead.
Fluid evaluates an argument before the view helper runs, and the language
service caches a resolved label for the whole request by locale and reference,
without the overrides of the site. On TYPO3 v13 that reference is the one
:html:`f:translate` reads, and the title of a shipped type of
`EXT:academic_partners` and `EXT:academic_projects` is that very reference, so
resolving the title first hides a :typoscript:`_LOCAL_LANG` label of the site.

An empty label counts as none, so a label a site blanks falls back to the
title. On TYPO3 v13 a shipped type of `EXT:academic_partners` and
`EXT:academic_projects` stays unlabelled instead, because its title is the
blanked label itself.

The view helper is meant for frontend rendering. It reads the language from the
site language of the request it renders for. Without one, in a command for
example, it resolves the title in the default language.

..  _developers-category-types-cache:

Caching
-------

The declarations of all packages are read once and kept in the core cache,
the types and the groups in an entry each.
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

:php:`getGroups()`
    Every declared group as a
    :php:`\FGTCLB\CategoryTypes\Domain\Model\CategoryTypeGroup`, keyed by
    identifier, in the order the groups were first declared. A group only
    types use, and nobody declared, is not in the list.

:php:`getGroup()`
    One declared group by identifier, or :php:`null`.

A :php:`\FGTCLB\CategoryTypes\Domain\Model\CategoryType` exposes the declared
values through :php:`getIdentifier()`, :php:`getGroup()`, :php:`getTitle()`,
:php:`getIcon()`, :php:`isInlineIcon()` and :php:`getPriority()`, plus
:php:`getExtensionKey()` for the extension that declared the type or changed it
last, and :php:`getIconIdentifier()` for the registered icon.
:php:`getFrontendIcon()` and :php:`isFrontendInlineIcon()` answer what the
frontend shows, with the fallback to :yaml:`icon` and :yaml:`inlineIcon`
already applied. A :php:`CategoryTypeGroup` exposes :php:`getIdentifier()`,
:php:`getTitle()`, :php:`getIcon()`, :php:`isInlineIcon()`,
:php:`getFrontendIcon()`, :php:`isFrontendInlineIcon()`, :php:`getPriority()`
and :php:`getIconIdentifier()` the same way.
