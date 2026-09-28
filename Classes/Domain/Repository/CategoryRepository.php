<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Repository;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\GetCategoryCollectionInterface;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Factory\CategoryCollectionFactory;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoryRepository
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly CategoryCollectionFactory $categoryCollectionFactory,
        protected readonly CategoryTypeRegistry $categoryTypeRegistry,
    ) {}

    /**
     * Find all categories for a given page and group
     * @param string $group
     * @param int $pageId
     * @param bool $includeHidden @internal Argument is not part of Public API and may change at any given time.
     */
    public function findByGroupAndPageId(
        string $group,
        int $pageId,
        bool $includeHidden = false
    ): CategoryCollection {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');

        // Remove the deleted restriction for the pages AND the sys_category table
        // to show all categories in the backend even if the page or category is hidden.
        if ($includeHidden === true) {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->join(
                'sys_category',
                'sys_category_record_mm',
                'mm',
                'sys_category.uid=mm.uid_local'
            )
            ->join(
                'mm',
                'pages',
                'pages',
                'mm.uid_foreign=pages.uid'
            )
            ->where(
                $queryBuilder->expr()->in(
                    'sys_category.type',
                    $queryBuilder->quoteArrayBasedValueListToStringList(
                        $this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group),
                    ),
                ),
                $queryBuilder->expr()->in('sys_category.sys_language_uid', [0, -1]),
                $queryBuilder->expr()->eq(
                    'mm.tablenames',
                    $queryBuilder->createNamedParameter('pages')
                ),
                $queryBuilder->expr()->eq(
                    'mm.fieldname',
                    $queryBuilder->createNamedParameter('categories')
                ),
                $queryBuilder->expr()->eq(
                    'pages.uid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
            )
            // The backend orders categories by their manual `sorting` (TCA ctrl `sortby`);
            // without an ORDER BY the collection order belongs to the DBMS and is not the
            // same list twice on PostgreSQL (ACE-491). `uid` settles ties.
            ->orderBy('sys_category.sorting', 'ASC')
            ->addOrderBy('sys_category.uid', 'ASC')
            ->executeQuery();

        $categoryCollection = $this->categoryCollectionFactory->createCategoryCollection($group);

        while ($row = $result->fetchAssociative()) {
            $category = $this->buildCategoryObjectFromArray($group, $row);
            $categoryCollection->attach($category);
        }

        return $categoryCollection;
    }

    /**
     * @param string $group
     * @param GetCategoryCollectionInterface ...$entities
     */
    public function findAllApplicable(string $group, GetCategoryCollectionInterface ...$entities): CategoryCollection
    {
        return $this->findApplicable($group, $entities)[0];
    }

    /**
     * Like {@see findAllApplicable()}, but a category also counts as applicable when an entity
     * carries one of its subcategories, at any depth. A list that matches a selected category
     * by its whole subtree uses it, so a parent no record carries itself is still offered.
     *
     * The tree is read from the default-language parents of the categories the query
     * returns, every visible category of the group. A hidden category and one of a type
     * outside the group are not among them, so they end the walk exactly where
     * {@see findDescendantUids()} ends it.
     *
     * @param string $group
     * @param GetCategoryCollectionInterface ...$entities
     */
    public function findAllApplicableWithSubcategories(string $group, GetCategoryCollectionInterface ...$entities): CategoryCollection
    {
        [$categoryCollection, $parentUids] = $this->findApplicable($group, $entities);

        $categoriesByUid = [];
        foreach ($categoryCollection as $category) {
            $categoriesByUid[$category->getUid()] = $category;
        }
        foreach ($categoriesByUid as $uid => $category) {
            if ($category->isDisabled()) {
                continue;
            }
            // A category that names one of its own descendants as parent would otherwise be
            // walked forever.
            $seen = [$uid => true];
            $parentUid = $parentUids[$uid] ?? 0;
            while (isset($categoriesByUid[$parentUid]) && !isset($seen[$parentUid])) {
                $seen[$parentUid] = true;
                $categoriesByUid[$parentUid]->setDisabled(false);
                $parentUid = $parentUids[$parentUid] ?? 0;
            }
        }

        return $categoryCollection;
    }

    /**
     * Every visible category of the group, the ones no entity carries disabled, and the parent
     * of each category as the default language stores it. The parent of a category object is
     * the one of its translation in a translated frontend, and an editor can give a
     * translation another parent. The tree of a list filter is the one of the default
     * language, as {@see findDescendantUids()} reads it.
     *
     * @param GetCategoryCollectionInterface[] $entities
     * @return array{0: CategoryCollection, 1: array<int, int>}
     */
    private function findApplicable(string $group, array $entities): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->in(
                    'sys_category.type',
                    $queryBuilder->quoteArrayBasedValueListToStringList(
                        $this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group),
                    ),
                ),
                $queryBuilder->expr()->in('sys_category.sys_language_uid', [0, -1]),
            )
            // Manual backend order, deterministic on every DBMS - see findByGroupAndPageId() (ACE-491).
            ->orderBy('sys_category.sorting', 'ASC')
            ->addOrderBy('sys_category.uid', 'ASC')
            ->executeQuery();

        $categoryCollection = $this->categoryCollectionFactory->createCategoryCollection($group);

        // Generate a list of all categories which are assigned to the given projects
        $applicableCategories = [];
        foreach ($entities as $entity) {
            foreach ($entity->getAttributes() as $category) {
                $applicableCategories[] = $category->getUid();
            }
        }
        $applicableCategories = array_unique($applicableCategories);
        $parentUids = [];
        // Disable all categories which are not assigned to any of the given entities
        while ($row = $result->fetchAssociative()) {
            $parentUids[(int)$row['uid']] = (int)$row['parent'];
            $category = $this->buildCategoryObjectFromArray($group, $row);
            if (!in_array($category->getUid(), $applicableCategories, true)) {
                $category->setDisabled(true);
            }
            $categoryCollection->attach($category);
        }

        return [$categoryCollection, $parentUids];
    }

    /**
     * Returns the uids of the subcategories of each given category, at any depth, ordered by
     * uid and keyed by the given uid. Only visible categories of the types of the group in the
     * default language take part, so a hidden category or one of another type ends the walk
     * below it. A category without subcategories, an unknown uid and a uid of 0 or less map
     * to an empty list. The given categories themselves are not checked: callers hand over
     * categories they already resolved for the frontend.
     *
     * The tree is read one level per statement, however many categories are given. A uid
     * that was reached before is not read again, which ends a loop in the tree.
     *
     * @return array<int, list<int>>
     */
    public function findDescendantUids(string $group, int ...$uids): array
    {
        $typeIdentifiers = $this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group);

        $childrenByParent = [];
        // A uid of 0 or less is not a category. Walked, 0 would find every root category of the
        // group as its children.
        $seen = array_fill_keys(array_filter($uids, static fn(int $uid): bool => $uid > 0), true);
        $level = array_keys($seen);
        while ($level !== []) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
            $result = $queryBuilder
                ->select('uid', 'parent')
                ->from('sys_category')
                ->where(
                    $queryBuilder->expr()->in(
                        'parent',
                        $queryBuilder->quoteArrayBasedValueListToIntegerList($level),
                    ),
                    $queryBuilder->expr()->in(
                        'type',
                        $queryBuilder->quoteArrayBasedValueListToStringList($typeIdentifiers),
                    ),
                    $queryBuilder->expr()->in(
                        'sys_language_uid',
                        $queryBuilder->quoteArrayBasedValueListToIntegerList([0, -1]),
                    ),
                )
                ->orderBy('uid', 'ASC')
                ->executeQuery();

            $level = [];
            while ($row = $result->fetchAssociative()) {
                $uid = (int)$row['uid'];
                $childrenByParent[(int)$row['parent']][] = $uid;
                if (!isset($seen[$uid])) {
                    $seen[$uid] = true;
                    $level[] = $uid;
                }
            }
        }

        $descendants = [];
        foreach ($uids as $uid) {
            $collected = [];
            $pending = $childrenByParent[$uid] ?? [];
            while ($pending !== []) {
                $child = array_pop($pending);
                if ($child === $uid || isset($collected[$child])) {
                    continue;
                }
                $collected[$child] = true;
                array_push($pending, ...($childrenByParent[$child] ?? []));
            }
            $collected = array_keys($collected);
            sort($collected);
            $descendants[$uid] = $collected;
        }

        return $descendants;
    }

    /**
     * @param string $group
     * @param array<int> $idList A positive selection of categories, an empty list selects none
     */
    public function findByGroupAndUidList(
        string $group,
        array $idList
    ): CategoryCollection {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->in(
                    'sys_category.type',
                    $queryBuilder->quoteArrayBasedValueListToStringList(
                        $this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group),
                    ),
                ),
                $queryBuilder->expr()->in('sys_category.sys_language_uid', [0, -1]),
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList($idList),
                ),
            )
            // Manual backend order, deterministic on every DBMS - see findByGroupAndPageId() (ACE-491).
            ->orderBy('sys_category.sorting', 'ASC')
            ->addOrderBy('sys_category.uid', 'ASC')
            ->executeQuery();

        $categoryCollection = $this->categoryCollectionFactory->createCategoryCollection($group);

        while ($row = $result->fetchAssociative()) {
            $category = $this->buildCategoryObjectFromArray($group, $row);
            $categoryCollection->attach($category);
        }
        return $categoryCollection;
    }

    /**
     * @param string $group
     * @param int $uid
     * @param string $table
     * @param string $field
     */
    public function getByDatabaseFields(
        string $group,
        int $uid,
        string $table = 'tt_content',
        string $field = 'pi_flexform'
    ): CategoryCollection {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
        $result = $queryBuilder
            ->select('sys_category.*')
            ->distinct()
            ->from('sys_category')
            ->join(
                'sys_category',
                'sys_category_record_mm',
                'sys_category_record_mm',
                'sys_category.uid=sys_category_record_mm.uid_local'
            )
            ->join(
                'sys_category_record_mm',
                $table,
                $table,
                sprintf('sys_category_record_mm.uid_foreign=%s.uid', $table)
            )
            ->where(
                $queryBuilder->expr()->in(
                    'sys_category.type',
                    $queryBuilder->quoteArrayBasedValueListToStringList(
                        $this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group),
                    ),
                ),
                $queryBuilder->expr()->in('sys_category.sys_language_uid', [0, -1]),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.tablenames',
                    $queryBuilder->createNamedParameter($table)
                ),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.fieldname',
                    $queryBuilder->createNamedParameter($field)
                ),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.uid_foreign',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )
            // Manual backend order, deterministic on every DBMS - see findByGroupAndPageId()
            // (ACE-491). Both ORDER BY columns are part of the DISTINCT select list
            // (`sys_category.*`), which PostgreSQL insists on.
            ->orderBy('sys_category.sorting', 'ASC')
            ->addOrderBy('sys_category.uid', 'ASC')
            ->executeQuery();

        $categoryCollection = $this->categoryCollectionFactory->createCategoryCollection($group);

        // No early return on `rowCount()`: for a SELECT statement that value is
        // driver dependent, and SQLite reports 0 for a result that does carry rows.
        // Iterating the result is the only portable way to tell it is empty.
        foreach ($result->fetchAllAssociative() as $row) {
            $category = $this->buildCategoryObjectFromArray($group, $row);
            $categoryCollection->attach($category);
        }

        return $categoryCollection;
    }

    /**
     * Find the parent category of a given category
     *
     * @param string $group
     * @param int $parent
     *
     * @todo Generalize this method to be able to find a category just by UID
     */
    public function findParent(string $group, int $parent): ?Category
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->in('sys_category.sys_language_uid', [0, -1]),
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($parent, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery();
        $row = $result->fetchAssociative();
        if ($row === false) {
            return null;
        }

        return $this->buildCategoryObjectFromArray($group, $row);
    }

    /**
     * Returns the raw database rows of a category and all its ancestors, root first.
     *
     * A category that cannot be resolved - unknown, deleted, or removed in the current
     * workspace - ends the walk instead of failing. For the requested uid that means an
     * empty rootline; for an ancestor it means the part that could be resolved, because
     * deleting a category leaves its children in place.
     *
     * @param array<int, array<string, mixed>> $rootline
     * @return array<int, array<string, mixed>>
     */
    public function getCategoryRootline(int $uid, array $rootline = []): array
    {
        // A category referencing itself or one of its own descendants would recurse until
        // the memory limit is reached, which is a fatal error no caller can catch.
        if (in_array($uid, array_map(intval(...), array_column($rootline, 'uid')), true)) {
            return array_reverse($rootline);
        }
        $category = $this->getCategoryArray($uid);
        if ($category === null) {
            return array_reverse($rootline);
        }
        $rootline[] = $category;

        // The parent is cast because a string '0' would recurse into uid 0 rather than
        // end the walk at the root.
        if ((int)$category['parent'] !== 0) {
            return $this->getCategoryRootline((int)$category['parent'], $rootline);
        }

        return array_reverse($rootline);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCategoryArray(int $uid): ?array
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $workspaceUid = (int)$context->getPropertyFromAspect('workspace', 'id', 0);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $category = $queryBuilder->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter([0, $workspaceUid], Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        if ($category === false) {
            return null;
        }
        // Sets the record to `false` when the workspace deleted or moved it away.
        $pageRepository->versionOL('sys_category', $category, false, true);
        if (!is_array($category)) {
            return null;
        }
        if ($category['l10n_parent'] > 0) {
            $category = $pageRepository->getLanguageOverlay('sys_category', $category);
        }

        return $category;
    }

    /**
     * @param string $group
     * @param array<string, mixed> $row
     */
    private function buildCategoryObjectFromArray(string $group, array $row): Category
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $row = $pageRepository->getLanguageOverlay('sys_category', $row) ?? $row;
        return new Category(
            uid: (int)($row['uid'] ?? 0),
            parentId: (int)($row['parent'] ?? 0),
            title: (string)($row['title'] ?? ''),
            type: (string)($row['type'] ?? 'default'),
            typeGroup: $group,
            hidden: (bool)($row['hidden'] ?? false),
        );
    }
}
