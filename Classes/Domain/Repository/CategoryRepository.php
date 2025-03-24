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
     */
    public function findByGroupAndPageId(string $group, int $pageId): CategoryCollection
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
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
            )->executeQuery();

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
            )->executeQuery();

        $categoryCollection = $this->categoryCollectionFactory->createCategoryCollection($group);

        // Generate a list of all categories which are assigned to the given projects
        $applicableCategories = [];
        foreach ($entities as $entity) {
            foreach ($entity->getAttributes() as $category) {
                $applicableCategories[] = $category->getUid();
            }
        }
        $applicableCategories = array_unique($applicableCategories);
        // Disable all categories which are not assigned to any of the given entities
        while ($row = $result->fetchAssociative()) {
            $category = $this->buildCategoryObjectFromArray($group, $row);
            if (!in_array($category->getUid(), $applicableCategories, true)) {
                $category->setDisabled(true);
            }
            $categoryCollection->attach($category);
        }

        return $categoryCollection;
    }

    /**
     * @param string $group
     * @param array<int> $idList
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
                $queryBuilder->expr()->in('uid', $idList),
            )->executeQuery();

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
            )->executeQuery();

        $categoryCollection = $this->categoryCollectionFactory->createCategoryCollection($group);

        if ($result->rowCount() === 0) {
            return $categoryCollection;
        }

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
     * @param array<int, mixed> $rootline
     * @return array<int, mixed>
     */
    public function getCategoryRootline(int $uid, array $rootline = []): array
    {
        $category = $this->getCategoryArray($uid);
        $rootline[] = $category;

        if ($category['parent'] !== 0) {
            $rootline = $this->getCategoryRootline($category['parent'], $rootline);
        } else {
            $rootline = array_reverse($rootline);
        }

        return $rootline;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCategoryArray(int $uid): array
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
        $pageRepository->versionOL('sys_category', $category, false, true);
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
        );
    }
}
