..  _developers-routing:

========================
Routing category filters
========================

The lists of the academic extensions carry their category filter as one
argument, a comma separated list of category uids, for example
`tx_academicpartners_list[demand][filterCollection][categories]=2,6`. The
routing aspect type `CategoryFilterMapper` turns that argument into a readable
path segment and back:

..  code-block:: text

    2,6   ->  americas-2,europe-6      (English)
    2,6   ->  amerika-2,europa-6       (German)

The core mappers cannot do this. `sys_category` has no slug field, and a core
mapper maps exactly one record, while a filter is a list of categories.

..  _developers-routing-configuration:

Configuration
=============

The aspect is configured like any core aspect, in the `aspects` of a route
enhancer:

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
            emptyValue: all
            localeMap:
              - locale: 'de_DE.*'
                value: alle

..  confval:: group
    :name: routing-category-filter-group
    :type: string
    :required: true

    The category group whose categories the aspect maps, for example
    `partners`, `programs` or `projects`. A category of any other group, or of
    no group, is not mapped. A missing group, or one no category type belongs
    to, stops the site with an exception, so that a typing error does not turn
    every filter URL into a page that is not found.

..  confval:: emptyValue
    :name: routing-category-filter-empty-value
    :type: string
    :default: all

    The segment for an empty filter value. It must not contain a slash or a
    comma and must not look like a category part such as `all-5`, otherwise
    the aspect is refused when it is built.

    The lists of the academic extensions never generate this segment: a list
    without a category filter leaves the filter argument out of its links, and
    a route whose variable is missing is not used for a link at all. The token
    matters for a link that passes an empty filter explicitly, and for a
    visitor who types it. Such a request carries an empty filter, so the preset
    categories of the content element do not apply, as with any other filtered
    URL.

..  confval:: localeMap
    :name: routing-category-filter-locale-map
    :type: array

    The segment for an empty filter value per language, as a list of `locale`
    and `value` pairs, each value following the rules of `emptyValue`. A locale
    is matched as core's `LocaleModifier` matches it, so `de_DE.*` covers
    `de-DE`. The first item that matches wins, and `emptyValue` applies where
    none does.

..  confval:: fallbackValue
    :name: routing-category-filter-fallback-value
    :type: string

    The value a segment that cannot be resolved stands for, as with the core
    mappers. Without it, such a segment makes the route not match.

The `requirements` entry is not optional in practice. A variable with an aspect
matches greedily, and without `[^/]+` it swallows the segments that follow it.

..  _developers-routing-segment:

The segment
===========

Every category of the filter becomes one part, `<slug>-<uid>`, in the order of
the filter argument, and the parts are joined by commas.

*   The slug is made from the title of the category in the language of the
    URL: the translation of the site language where one exists and is visible,
    then the translation of each language the site language falls back to,
    then the title in the default language. A category for all languages has
    one title in every language.
*   A slash in a title becomes a dash, and a title that leaves nothing behind,
    such as `?!`, becomes `category`.
*   A value the aspect cannot map generates nothing, and the link keeps its
    query argument: a category that is hidden, deleted or of another group, an
    unknown uid, or a value that is not a list of uids.

When a URL is resolved, only the uid at the end of each part is read. A part
resolves when the category exists, is visible and belongs to the group. One
part that does not resolve makes the whole segment unresolvable, and the route
does not match. The token of the empty filter resolves in its own language
only.

..  _developers-routing-uid:

Why the uid stays in the path
=============================

Two categories may share a title, a region and a partner type both called
"Europe" for example, and the uid keeps them apart without a slug field that
would have to be kept unique. It also keeps old links working. A renamed
category generates a new segment, while the old one still resolves, because the
title part is never compared. A list can therefore be reached under any slug and
with its categories in any order, and every generated link uses the current
title. The canonical URL of EXT:seo leaves the filter out altogether, because
the lists exclude it from the cache hash, so these variants do not compete in
search engines.

..  _developers-routing-cache:

No cache hash, one cache entry
==============================

The aspect is deliberately not a static mapper, so the filter is a dynamic
route argument, never a static one. A dynamic argument reaches the page cache
identifier only through a `cHash`. The lists of `EXT:academic_partners`,
`EXT:academic_programs` and `EXT:academic_projects` exclude their demand from
the cache hash, so a readable filter URL carries no `cHash` and all filter URLs
of a page share one page cache entry. A plugin that does not exclude the
argument gets a `cHash` appended to the path, as it would for any dynamic
argument.

..  note::

    The aspect type `CategoryFilterMapper` and its settings are public API. The
    PHP class behind it is not.
