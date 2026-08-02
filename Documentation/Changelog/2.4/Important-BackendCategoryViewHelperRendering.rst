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

The notice comes from the trait itself, so it fired on every backend page layout
preview using `<ct:be.category>`. TYPO3 v12 ships Fluid 2 and was silent.

The view helper now implements a non-static `render()`, which behaves the same
and is available on TYPO3 v12 as well. The class is identical to the one of the
3.x branch again.

In the same step the two required arguments `page` and `group` lost their default
value. The default of a required argument can never be used, and the default of
`page` was an empty array for an argument typed `int`. Fluid 5 rejects the
combination outright, which is why the 3.x branch had to drop it to render on
TYPO3 v14 at all.

References
----------

* TYPO3 v13 changelog `Deprecation: #104789 - renderStatic() for Fluid
  ViewHelpers
  <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.3/Deprecation-104789-RenderStaticForFluidViewHelpers.html>`__
* `CompileWithRenderStatic in the Fluid repository
  <https://github.com/TYPO3/Fluid/blob/main/src/Core/ViewHelper/Traits/CompileWithRenderStatic.php>`__
* Fluid pull request `#1157 - Disallow argument definitions with a default value
  while also being required <https://github.com/TYPO3/Fluid/pull/1157>`__, for
  the argument defaults

Impact
======

Installations on TYPO3 v13 no longer collect a deprecation entry per rendered
backend page layout of `EXT:academic_partners`, `EXT:academic_programs` and
`EXT:academic_projects`.

Affected Installations
======================

Installations on TYPO3 v13 using one of the three extensions above, or a project
template of their own calling `<ct:be.category>`.

Migration
=========

None. The view helper is used the same way, and both arguments were required
before and still are. A project view helper inheriting from this one and
overriding `renderStatic()` has to be migrated to `render()` as described in the
TYPO3 changelog entry above.

.. index:: Backend, Fluid, PHP-API, NotScanned
