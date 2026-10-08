<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\EventListener;

use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;

/**
 * Resolves `###CATEGORY_TYPE_GROUP:<group>###` in the `foreign_table_where` of a
 * FlexForm field, so a category tree offers the categories a plugin of that group can
 * use rather than every category:
 *
 *     AND {#sys_category}.{#uid} IN (###CATEGORY_TYPE_GROUP:partners###)
 *
 * The marker becomes a subselect of the categories that carry a type of the group, and
 * of their ancestors. A category tree shows a category only when every ancestor is
 * offered too, and a category of the group below a parent of another type, or of no
 * type, is a structure the filters of this extension support (`groupByParent`). Such a
 * parent can be selected in the tree like before, and is ignored by the plugin like
 * before.
 *
 * The types are not known when the FlexForm file is written. They come from the
 * `CategoryTypes.yaml` of every installed extension, and an integrator can add types to
 * a group or remove them, so a literal list in the file would go stale.
 *
 * **No database access here.** TYPO3 v13 parses every data structure while it builds
 * the TCA schema during bootstrap, before a table may exist, and caches the result. So
 * the ancestors are not looked up but left to the query that builds the tree, as a
 * recursive common table expression, which every supported DBMS runs in a subselect.
 * `UNION` rather than `UNION ALL` ends the recursion on a parent loop.
 *
 * A group without a type, or one no extension declares, resolves to `NULL`: the
 * condition `IN (NULL)` matches no category on every DBMS. Type identifiers are written
 * into the statement literally, so one that is not made of letters, digits, `_` and `-`
 * is left out rather than quoted through a connection, which would open one.
 *
 * Only the fields of a sheet's root element are looked at. That is the only place a
 * category field can be: core rejects a field with database relations, `category`,
 * `MM` or `foreign_table`, inside a section container (exception 1458745468).
 */
final class ResolveCategoryTypeGroupMarker
{
    private const MARKER = '/###CATEGORY_TYPE_GROUP:([^#]+)###/';

    public function __construct(
        private readonly CategoryTypeRegistry $categoryTypeRegistry,
    ) {}

    public function __invoke(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $dataStructure = $event->getDataStructure();
        $changed = false;
        foreach ($dataStructure['sheets'] ?? [] as $sheetName => $sheet) {
            if (!is_array($sheet)) {
                continue;
            }
            foreach ($sheet['ROOT']['el'] ?? [] as $fieldName => $field) {
                $where = $field['config']['foreign_table_where'] ?? null;
                if (!is_string($where) || !str_contains($where, '###CATEGORY_TYPE_GROUP:')) {
                    continue;
                }
                $dataStructure['sheets'][$sheetName]['ROOT']['el'][$fieldName]['config']['foreign_table_where']
                    = (string)preg_replace_callback(
                        self::MARKER,
                        fn(array $match): string => $this->categorySubselect(trim($match[1])),
                        $where,
                    );
                $changed = true;
            }
        }
        if ($changed) {
            $event->setDataStructure($dataStructure);
        }
    }

    private function categorySubselect(string $group): string
    {
        $types = [];
        foreach (array_keys($this->categoryTypeRegistry->getGroupedCategoryTypes()[$group] ?? []) as $type) {
            if (preg_match('/^[A-Za-z0-9_-]+$/', (string)$type) === 1) {
                $types[] = "'" . $type . "'";
            }
        }
        if ($types === []) {
            return 'NULL';
        }

        return sprintf(
            'WITH RECURSIVE {#category_type_group} ({#uid}, {#parent}) AS ('
            . 'SELECT {#uid}, {#parent} FROM {#sys_category}'
            . ' WHERE {#type} IN (%s) AND {#deleted} = 0'
            . ' UNION'
            . ' SELECT {#ancestor}.{#uid}, {#ancestor}.{#parent} FROM {#sys_category} {#ancestor}'
            . ' INNER JOIN {#category_type_group} ON {#category_type_group}.{#parent} = {#ancestor}.{#uid}'
            . ' WHERE {#ancestor}.{#deleted} = 0'
            . ') SELECT {#uid} FROM {#category_type_group}',
            implode(',', $types),
        );
    }
}
