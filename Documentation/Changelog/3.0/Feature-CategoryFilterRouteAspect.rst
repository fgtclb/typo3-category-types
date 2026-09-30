.. _feature-1790760526:

====================================================
Feature: A routing aspect for category filter values
====================================================

Description
===========

The extension registers the routing aspect type `CategoryFilterMapper`. A route
enhancer uses it to put the category filter of a list into the path, readable
and in the language of the page, instead of a query argument:

..  code-block:: text

    /partner?tx_academicpartners_list[demand][filterCollection][categories]=2,6
    /partner/filter/americas-2,europe-6
    /de/partner/filter/amerika-2,europa-6

Every part of the segment is the slug of the category title followed by the
uid. When a URL is resolved, only the uid is read, so a link still works after
a category was renamed, and two categories with the same title stay apart. A
part resolves only for a visible category of the configured category group,
otherwise the route does not match and the page answers as for any unknown URL.
An empty filter value maps to a token, `all` unless configured otherwise, which
can differ per language. The lists never generate it, as they leave an empty
filter out of their links.

..  code-block:: yaml

    routeEnhancers:
      PartnerList:
        type: Extbase
        extension: AcademicPartners
        plugin: List
        limitToPages: [42]
        routes:
          - routePath: '/filter/{categories}'
            _controller: 'Partner::list'
            _arguments:
              categories: 'demand/filterCollection/categories'
        defaultController: 'Partner::list'
        requirements:
          categories: '[^/]+'
        aspects:
          categories:
            type: CategoryFilterMapper
            group: partners
            localeMap:
              - locale: 'de_DE.*'
                value: alle

The filter stays a dynamic route argument. Behind the lists of
`EXT:academic_partners`, `EXT:academic_programs` and `EXT:academic_projects`,
which keep their demand out of the cache hash, the readable URL carries no
`cHash`, and all filter URLs of a page share one page cache entry, as the query
argument URLs do.

Impact
======

Nothing changes until a site's route enhancer uses the aspect. A project that
ships its own aspect for the same purpose can replace it. The settings, and
why the uid stays in the path, are described in the chapter
:ref:`Routing category filters <developers-routing>`.

.. index:: Frontend, NotScanned
