<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Repository;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

class CategoryRepository
{
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected CategoryTypeRegistry $categoryTypeRegistry
    ) {
    }

    /**
     * Find all categories for a given page and group
     * @param string $group
     * @param int $pageId
     */
    public function findByGroupAndPageId(string $group, int $pageId): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();
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
                $this->categoryTypeCondition($group),
                $this->siteDefaultLanguageCondition(),
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

        $categoryCollection = new CategoryCollection();
        $categoryCollection->setTypeIdentifiers($this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group));

        if ($result->rowCount() === 0) {
            return $categoryCollection;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $category = $this->buildCategoryObjectFromArray($group, $row);
            $categoryCollection->attach($category);
        }

        return $categoryCollection;
    }

    /*
    public function findByType(
        int $pageId,
        CategoryType $type
    ): CategoryCollection {
        $result = $this->queryBuilder
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
                $this->categoryTypeCondition(),
                $this->siteDefaultLanguageCondition(),
                $queryBuilder->expr()->eq(
                    'sys_category.type',
                    $queryBuilder->createNamedParameter((string)$type)
                ),
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

        $categories = new CategoryCollection();

        if ($result->rowCount() === 0) {
            return $categories;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $category = $this->buildCategoryObjectFromArray($group, $row);
            $categories->attach($category);
        }

        return $categories;
    }
    */

    /*
    public function findAll(): CategoryCollection
    {
        $result = $this->queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition(),
                $this->siteDefaultLanguageCondition(),
            )->executeQuery();

        $categories = new CategoryCollection();

        if ($result->rowCount() === 0) {
            return $categories;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $category = $this->buildCategoryObjectFromArray($group, $row);
            $categories->attach($category);
        }

        return $categories;
    }
    */

    /**
     * @param string $group
     * @param QueryResult<Project> $projects
     */
    public function findAllApplicable(string $group, QueryResult $projects): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();
        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition($group),
                $this->siteDefaultLanguageCondition(),
            )->executeQuery();

        $categoryCollection = new CategoryCollection();
        $categoryCollection->setTypeIdentifiers($this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group));

        if ($result->rowCount() === 0) {
            return $categoryCollection;
        }

        // Generate a list of all categories which are assigned to the given projects
        $applicableCategories = [];
        foreach ($projects as $project) {
            foreach ($project->getAttributes() as $attribute) {
                $applicableCategories[] = $attribute->getUid();
            }
        }

        // Disable all categories which are not assigned to any of the given projects
        foreach ($result->fetchAllAssociative() as $row) {
            $category = $this->buildCategoryObjectFromArray($group, $row);
            if (!in_array($row['uid'], $applicableCategories)) {
                $category->setDisabled(true);
            }
            $categoryCollection->attach($category);
        }

        return $categoryCollection;
    }

    /**
     * @param string $group
     * @param string $type
     * @param array<int> $idList
     */
    public function findByTypeAndUidList(
        string $group,
        string $type,
        array $idList
    ): CategoryCollection {
        $queryBuilder = $this->buildQueryBuilder();
        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition($group),
                $this->siteDefaultLanguageCondition(),
                $queryBuilder->expr()->in('uid', $idList),
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter((string)$type))
            )->executeQuery();

        $categoryCollection = new CategoryCollection();
        $categoryCollection->setTypeIdentifiers($this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group));

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
        $queryBuilder = $this->buildQueryBuilder();
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
                $this->categoryTypeCondition(),
                $this->siteDefaultLanguageCondition(),
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

        $categoryCollection = new CategoryCollection();
        $categoryCollection->setTypeIdentifiers($this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group));

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
     * TODO: Generelize this method to be able to find a category just by UID
     * @param string $group
     * @param int $parent
     */
    public function findParent(string $group, int $parent): ?Category
    {
        $queryBuilder = $this->buildQueryBuilder();
        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $this->siteDefaultLanguageCondition(),
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($parent, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery();

        if ($result->rowCount() === 0) {
            return null;
        }

        $row = $result->fetchAssociative();
        if ($row === false) {
            return null;
        }

        return $this->buildCategoryObjectFromArray($group, $row);
    }

    /*
    public function findChildren(int $uid): ?CategoryCollection
    {
        $result = $this->queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition(),
                $this->siteDefaultLanguageCondition(),
                $queryBuilder->expr()->eq(
                    'parent',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )->executeQuery();

        $childCategories = new CategoryCollection();

        if ($result->rowCount() === 0) {
            return $childCategories;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $category = $this->buildCategoryObjectFromArray($group, $row);
            $childCategories->attach($category);
        }

        return $childCategories;
    }
    */

    /**
     * @param string $group
     * @param array<string, mixed> $row
     */
    private function buildCategoryObjectFromArray(string $group, array $row): Category
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $row = $pageRepository->getLanguageOverlay('sys_category', $row) ?? $row;

        return new Category(
            $row['uid'],
            $row['parent'],
            $row['title'],
            $row['type'],
            $group
        );
    }

    /**
     * General check to exclude all category records, which are not related to projects
     */
    private function categoryTypeCondition($group): string
    {
        $queryBuilder = $this->buildQueryBuilder();
        $categoryTypes = $this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group);

        return $queryBuilder->expr()->in(
            'sys_category.type',
            array_map(function (string $value) {
                return '\'' . $value . '\'';
            }, $categoryTypes)
        );
    }

    /**
     * General check to exclude all translated category records
     */
    private function siteDefaultLanguageCondition(): string
    {
        $queryBuilder = $this->buildQueryBuilder();

        $defaultLanguageUid = $GLOBALS['TYPO3_REQUEST']
            ->getAttribute('site')
            ->getDefaultLanguage()
            ->getLanguageId();

        return $queryBuilder->expr()->in(
            'sys_category.sys_language_uid',
            [$defaultLanguageUid, -1]
        );
    }

    private function buildQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable('sys_category');
    }
}
