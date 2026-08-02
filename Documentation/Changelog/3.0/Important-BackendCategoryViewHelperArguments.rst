.. _important-1785708000:

==========================================================
Important: Page categories render in the TYPO3 v14 backend
==========================================================

Description
===========

`FGTCLB\\CategoryTypes\\ViewHelpers\\Be\\CategoryViewHelper` registered its two
required arguments with a default value:

..  code-block:: php

    'page' => [
        'type' => 'int',
        'defaultValue' => [],
        'description' => 'The page ID for which the categories should be fetched',
        'required' => true,
    ],
    'group' => [
        'type' => 'string',
        'defaultValue' => 'default',
        'description' => 'The group identifier for the categories for this page type',
        'required' => true,
    ],

Fluid 5, shipped with TYPO3 v14, rejects that combination while the template is
being parsed:

..  code-block:: text

    TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentDefinitionException:
    ArgumentDefinition "page" cannot have a default value while also being
    required. Either remove the default or mark it as optional. (1754235900)

The default value of a required argument can never be used, and Fluid now says
so instead of accepting the definition. Both default values are dropped; the
arguments stay required, which is what the registration meant. The one of `page`
was wrong in its own right as well - an empty array for an argument typed `int`.

References
----------

* Fluid pull request `#1157 - Disallow argument definitions with a default value
  while also being required
  <https://github.com/TYPO3/Fluid/pull/1157>`__, first released in
  `Fluid 5.0.0 <https://github.com/TYPO3/Fluid/releases/tag/5.0.0>`__. The commit
  message states that it "will not be backported to 4.x because it might break
  existing ViewHelpers or components if they are not configured properly" -
  which is why TYPO3 v13, running Fluid 4, accepts the same registration.
* `Fluid changelog 5.x
  <https://docs.typo3.org/other/typo3fluid/fluid/main/en-us/Changelog/5.x.html>`__
* TYPO3 v14 changelog `Breaking: #108148 - Strict Types in Fluid ViewHelpers
  <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.0/Breaking-108148-StrictTypesInFluidViewHelpers.html>`__,
  the wider move to stricter ViewHelper argument handling this belongs to.

Impact
======

On TYPO3 v14 every template using `<ct:be.category>` threw while being parsed, so
the backend page layout preview of `EXT:academic_partners`,
`EXT:academic_programs` and `EXT:academic_projects` could not render at all. The
exception came from the parser, before the view helper was invoked, so there was
no partial output either.

TYPO3 v13 was not affected.

Affected Installations
======================

Installations on TYPO3 v14 using the backend page layout preview of one of the
three extensions above, or a project template of their own calling
`<ct:be.category>`.

Migration
=========

None. Both arguments were required before and still are.

.. index:: Backend, Fluid, PHP-API, NotScanned
