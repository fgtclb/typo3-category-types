Category type icons
===================

A category type does not register its icon in :file:`Configuration/Icons.php`.
It names an icon **file** in :file:`Configuration/CategoryTypes.yaml`, and this
extension registers it on :php:`BootCompletedEvent`:

..  code-block:: yaml
    :caption: EXT:example/Configuration/CategoryTypes.yaml

    types:
      - identifier: degree
        title: 'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.example.degree'
        group: example
        icon: 'EXT:example/Resources/Public/Icons/CategoryTypes/Degree.svg'

The registration happens in
:php:`\FGTCLB\CategoryTypes\ServiceProvider::addIcons()`. The icon identifier is
derived, never written by hand -
:php:`\FGTCLB\CategoryTypes\Domain\Model\CategoryType::getIconIdentifier()`
builds it from the group and the type:

..  code-block:: text

    category_types.<group>.<type>

For the example above that is :php:`category_types.example.degree`, and that is
the identifier the :php:`sys_category` :php:`typeicon_classes` entry uses and
the identifier a template addresses:

..  code-block:: html

    <core:icon identifier="category_types.example.degree" />

..  _developers-icons-provider:

Which provider the icon gets
----------------------------

By default the one
:php:`\TYPO3\CMS\Core\Imaging\IconRegistry::detectIconProvider()` answers for
the file: :php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider` for an
SVG, :php:`\TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider` for a
bitmap. The default markup of both is an :html:`<img>` tag, and an image is
opaque to CSS: such an icon keeps the colours of its file whatever the backend
colour scheme or the frontend theme says.

An SVG type can ask for something else with :yaml:`inlineIcon: true`:

..  code-block:: yaml
    :caption: EXT:example/Configuration/CategoryTypes.yaml

    types:
      - identifier: degree
        title: 'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.example.degree'
        group: example
        icon: 'EXT:example/Resources/Public/Icons/CategoryTypes/Degree.svg'
        inlineIcon: true

The icon is then registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider` of
:php:`EXT:academic_base`, which inlines the file as the icon markup - in the
default markup as well as in the `inline` alternative - so an icon drawn in
`currentColor` takes the colour of the text around it.

A **bitmap** file cannot be inlined and keeps what core detected for it, with or
without the flag.

..  _developers-icons-opt-in:

Why inlining is opt in
----------------------

Because the registrar sees every loaded extension. The set of category types is
whatever the installed extensions declare, site packages included, and inlining
a file changes what that file can do to the page around it. It is not a decision
this extension may take on behalf of a file it has never seen.

An inlined SVG is part of the document, and two things about it are therefore
global rather than local to the icon:

*   Its :html:`id` attributes. A :html:`clip-path="url(#SVGID_1_)"` resolves to
    the *first* :html:`clipPath` with that id in the document, which may well
    belong to a different icon.
*   Its :html:`<style>` element. A rule like :code:`.st0{fill:#ff0000}` is
    document-wide CSS and applies to every :html:`.st0` on the page.

Those two names are not hypothetical: :html:`id="SVGID_1_"` and :html:`.st0` are
what an Adobe Illustrator export writes, so two unrelated vendors collide by
construction. Rendered together, one icon ends up painted in the other's colour
and the other ends up clipped to the first one's rectangle. A file left with the
core provider cannot do either, because an :html:`<img>` renders in isolation.

So a type that says nothing keeps the behaviour it has always had. A type that
opts in takes on the rules below.

..  _developers-icons-svg:

What the SVG file must look like
--------------------------------

The file is inlined into the HTML of the page, possibly several times, so it
has to be drawn for that:

*   A `viewBox` attribute on the root element.
*   `fill="currentColor"` or `stroke="currentColor"` on every drawable element,
    and no hardcoded colour anywhere - not as an attribute and not in a
    `<style>` element.
*   No `id` attributes and no `<style>` element. The markup may appear more than
    once in one document, a duplicated `id` is invalid HTML, and both reach past
    the icon into the rest of the page - see above. That rules out the
    `clip-path="url(#…)"` construction an Adobe Illustrator export produces.
*   `width="1em"` and `height="1em"` where the icon is rendered in the
    frontend, so it follows the font size of the text around it. The backend
    needs neither: its CSS sizes every icon through the wrapper.
*   No `<script>` element and no event handler attributes.

All five are requirements, not recommendations. A file that carries its own
colours does not merely fail to follow the colour scheme - together with an
:html:`id` or a :html:`<style>` element it changes how *other* icons on the same
page are drawn. Leave :yaml:`inlineIcon` off for a file that does not meet them.

The content is sanitised before it is inlined, on both supported TYPO3
versions, so a :html:`<script>` element, an event handler attribute and a
:html:`javascript:` href are removed rather than rendered. That is a filter and
not a warranty: it does nothing about the two collision cases above.

The chapter :guilabel:`Configuration` of :php:`EXT:academic_base`, section
:guilabel:`Icons that follow the text colour`, carries the full rules and the
per-core-version details.
