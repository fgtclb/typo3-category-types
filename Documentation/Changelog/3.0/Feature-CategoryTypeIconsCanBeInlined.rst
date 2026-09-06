..  _feature-category-type-icons-can-be-inlined:

============================================
Feature: A category type can inline its icon
============================================

Description
===========

A category type icon is registered programmatically, on
:php:`BootCompletedEvent`, from whatever the loaded extensions declare in their
:file:`Configuration/CategoryTypes.yaml`. The registrar asked
:php:`IconRegistry::detectIconProvider()` for the provider, and that method
knows bitmap from SVG by file extension and nothing else - so every category
type icon got the core provider
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`, whose default
markup is an :html:`<img>` tag. An image is opaque to CSS, so the icon kept the
ink of its file whatever the backend colour scheme said.

A type can now ask for its icon to be inlined instead:

..  code-block:: yaml
    :caption: EXT:example/Configuration/CategoryTypes.yaml

    types:
      - identifier: degree
        title: 'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.example.degree'
        group: example
        icon: 'EXT:example/Resources/Public/Icons/CategoryTypes/Degree.svg'
        inlineIcon: true

With :yaml:`inlineIcon: true` an SVG icon is registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider` of
:php:`EXT:academic_base`, which inlines the file in both markups, so an icon
drawn in `currentColor` takes the colour of the text around it - the backend
colour scheme, or the theme of the frontend.

Impact
======

The option is off by default, and that is deliberate rather than cautious. An
inlined SVG is part of the document: its :html:`id` attributes and its
:html:`<style>` rules are global. Two files that both carry Adobe Illustrator's
export defaults - literally :html:`id="SVGID_1_"` and :html:`.st0` - collide by
construction, and the result is one icon painted in the other's colour or
clipped by the other's :html:`clipPath`. The registrar sees every loaded
extension, site packages included, and must not take that decision for files it
did not draw. Nothing changes for an existing category type until its own
extension asks.

An extension that does ask has to draw the file for it: a :html:`viewBox`,
:html:`fill="currentColor"` or :html:`stroke="currentColor"` on the shapes, no
hardcoded colour, no :html:`id` attribute, no :html:`<style>` element, and
:html:`width="1em" height="1em"` where the icon is rendered in the frontend. See
the :guilabel:`For Developers` chapter, section :guilabel:`Category type icons`.

A bitmap icon cannot be inlined and keeps the provider core detects for it, with
or without the flag. An icon file that does not exist renders empty markup
rather than raising an exception.

.. index:: Backend, Frontend, PHP-API, ext:category_types
