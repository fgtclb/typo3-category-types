.. _important-1785711600:

==============================================================
Important: Backend category view helper renders without static
==============================================================

Description
===========

`FGTCLB\\CategoryTypes\\ViewHelpers\\Be\\CategoryViewHelper` rendered through a
static `renderStatic()` method and the `CompileWithRenderStatic` trait. Fluid 4,
shipped with TYPO3 v13, raises a deprecation on every render of such a view
helper:

..  code-block:: text

    CompileWithRenderStatic has been deprecated and will be removed in Fluid v5.

The notice comes from the trait itself, so it fired on every render of
`<ct:be.category>`. TYPO3 v12 ships Fluid 2 and was silent.

The view helper now implements a non-static `render()`, which behaves the same
and is available on TYPO3 v12 as well. Its arguments are unchanged.

References
----------

* TYPO3 v13 changelog `Deprecation: #104789 - renderStatic() for Fluid
  ViewHelpers
  <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.3/Deprecation-104789-RenderStaticForFluidViewHelpers.html>`__
* `CompileWithRenderStatic in the Fluid repository
  <https://github.com/TYPO3/Fluid/blob/main/src/Core/ViewHelper/Traits/CompileWithRenderStatic.php>`__

Impact
======

Installations on TYPO3 v13 no longer collect a deprecation entry per render of
`<ct:be.category>`.

The page module partials of `EXT:academic_partners`, `EXT:academic_programs`
and `EXT:academic_projects` that called the view helper were never rendered by
TYPO3, see :ref:`feature-1789660802`, so the shipped templates did not raise
the deprecation. It reached installations whose own templates call the view
helper.

Affected Installations
======================

Installations on TYPO3 v13 with an own template calling `<ct:be.category>`, and
installations with an own view helper that extends this one.

Migration
=========

None for templates. The view helper is used the same way. A project view helper
inheriting from this one and overriding `renderStatic()` has to be migrated to
`render()` as described in the TYPO3 changelog entry above.

.. index:: Backend, Fluid, PHP-API, NotScanned, ext:category_types
