# Upgrade 2.0

## 2.0.1

## 2.0.0

### Replace enumeration category-type declaration with yaml file

The category type handling has been completely reworked for 2.0, moving the
definition to the category-type extension, providing a generic way to read
specific extension files (`Configuration/CategoryTypes.yaml`) to register
category types in a grouped way.

`Configuration/CategoryTypes.yaml` format uses following syntax:

```yaml
types:
  - identifier: <unique_identifier>
    title: '<type-translation-lable-using-full-LLL-syntax'
    group: <group-identifier>
    icon: '<icon-file-using-EXT-syntax-needs-to-be-an-as-svg>'
```

The group identifier is specified by the extension providing category-type
handling based on the `EXT:category_types` extension and the yaml file format.

The yaml file based implementation replaces the TYPO3 Enumeration implementation
strategy known from the 1.x.x and does no longer work for extension switched from
`1.x.x` to `2.x.x`. Known extensions with corresponding known group-identifier are:

- `EXT:academic_partners`: partners
- `EXT:academic_programs`: programs
- `EXT:academic_projects`: projects

With the newly introduced yaml file a new PHP API has been implemented to be
used by extension building category-type handling based on this extension.

The PHP API is considered experimental for now and may still change in the
early days until it has been stabilized throughout using in projects along
with related aforementioned extensions.

A comprehensive documentation for developers will be added in a later point.
