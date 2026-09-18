..  _developers-page-module-summary:

The page module category summary
================================

An extension that registers a page type of its own can show the categories of
such a page in the page module, above the content grid: one row per registered
category type of its group, with the categories assigned to that page, the ones
without a category marked as not set, and hidden categories marked as hidden.

:php:`EXT:academic_programs`, :php:`EXT:academic_projects` and
:php:`EXT:academic_partners` ship it for their page types. What this chapter
describes is how an own extension adds it, and how an installation replaces the
markup.

..  _developers-page-module-summary-listener:

Adding the summary to an own page type
--------------------------------------

Listen to
:php:`\TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent` and hand
the request, the page type and the category group to the renderer:

..  code-block:: php
    :caption: EXT:example/Classes/EventListener/AddPageModuleCategorySummary.php

    <?php

    declare(strict_types=1);

    namespace Vendor\Example\EventListener;

    use FGTCLB\CategoryTypes\Backend\PageCategorySummaryRenderer;
    use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;

    final class AddPageModuleCategorySummary
    {
        public function __construct(
            private readonly PageCategorySummaryRenderer $renderer,
        ) {}

        public function __invoke(ModifyPageLayoutContentEvent $event): void
        {
            $event->addHeaderContent($this->renderer->renderForPageOfType(
                $event->getRequest(),
                123,
                'example',
            ));
        }
    }

Register it with the :yaml:`event.listener` tag:

..  code-block:: yaml
    :caption: EXT:example/Configuration/Services.yaml

    services:
      Vendor\Example\EventListener\AddPageModuleCategorySummary:
        tags:
          - name: event.listener
            identifier: 'example/page-module-category-summary'
            event: TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent

The tag and not an attribute, as long as the extension supports TYPO3 v12:
:php:`\TYPO3\CMS\Core\Attribute\AsEventListener` does not exist there. Never
Symfony's attribute of the same name either - it registers nothing in TYPO3, and
the listener silently never fires.

The listener needs no condition of its own.
:php:`PageCategorySummaryRenderer::renderForPageOfType()` answers with an empty
string for every page that is not of the given type, and
:php:`addHeaderContent()` appends an empty string without a trace. It answers
the same way for a page the backend user may not read, a request that addresses
no page, and a category group no active extension registers - the page module
never fails because of a summary.

The header content is shared with the listeners of other extensions. Always
:php:`addHeaderContent()`, never :php:`setHeaderContent()`, which replaces what
they contributed.

..  _developers-page-module-summary-override:

Replacing the template
----------------------

The summary is rendered with
:php:`\TYPO3\CMS\Backend\View\BackendViewFactory` under the package name
:php:`fgtclb/category-types`, so an installation replaces the template through
page TSconfig:

..  code-block:: typoscript
    :caption: EXT:my_site/Configuration/page.tsconfig

    templates.fgtclb/category-types.my-site = my-vendor/my-site:Resources/Private/Backend

The value is a composer package name and a path inside it. The file is looked up
below that path as :file:`Templates/PageCategorySummary.html` - for the example
above that is
:file:`EXT:my_site/Resources/Private/Backend/Templates/PageCategorySummary.html`.

Several keys may be registered; they are applied in alphabetical order of the
key, so the last one wins.

..  _developers-page-module-summary-variables:

What the template is given
--------------------------

..  confval:: rows
    :name: page-module-summary-rows
    :type: array

    One entry per registered category type of the group, in registry order, and
    including the types the page carries no category of.

    Each entry has :php:`title`, the resolved title of the type;
    :php:`iconIdentifier`, its icon identifier; :php:`categories`, the
    categories of that type assigned to the page, hidden ones included; and
    :php:`type`, the
    :php:`\FGTCLB\CategoryTypes\Domain\Model\CategoryType` itself for anything
    the first three do not carry.

..  confval:: group
    :name: page-module-summary-group
    :type: string

    The category group the summary was rendered for.

..  confval:: pageId
    :name: page-module-summary-page-id
    :type: int

    The page the summary belongs to.

The title is resolved before it reaches the template, with
:php:`\TYPO3\CMS\Core\Localization\LanguageService::sL()`. A type whose
:yaml:`title` is an :php:`LLL:` reference is translated, and a type with a
literal title keeps it.
