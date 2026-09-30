<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Routing\Aspect;

use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Routing\Aspect\PersistedMappableAspectInterface;
use TYPO3\CMS\Core\Routing\Aspect\SiteLanguageAccessorTrait;
use TYPO3\CMS\Core\Routing\Aspect\UnresolvedValueInterface;
use TYPO3\CMS\Core\Routing\Aspect\UnresolvedValueTrait;
use TYPO3\CMS\Core\Site\SiteLanguageAwareInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Maps the category filter of a list, one category uid or a comma separated list of them,
 * to a readable URL segment and back: `12,31` becomes `europe-12,university-31`.
 *
 * Every part is the slug of the category title in the language of the URL, followed by the
 * uid. Only the uid is read when a URL is resolved, so a URL keeps working after the
 * category was renamed, and two categories with the same title stay apart. A part resolves
 * only for a visible category of the configured group, and one part that does not resolve
 * makes the whole segment unresolvable. An empty filter is a token of its own, `all` unless
 * configured otherwise, which can differ per language like the values of core's
 * `LocaleModifier`.
 *
 * The aspect is deliberately not static mappable. Static route arguments are part of the
 * page cache identifier, so a static filter would create one page cache entry per
 * combination of categories. As a dynamic argument, the filter is left out of the cache
 * hash and of the page cache identifier whenever the plugin excludes its demand from the
 * cache hash, as the partner, project and program lists do.
 *
 * Aspects are created by core's `AspectFactory` with `GeneralUtility::makeInstance()` and
 * never by the container, so the collaborators are fetched the same way, and the class is
 * kept out of the container.
 *
 * ```yaml
 * aspects:
 *   categories:
 *     type: CategoryFilterMapper
 *     group: partners
 *     emptyValue: all
 *     localeMap:
 *       - locale: 'de_DE.*'
 *         value: alle
 * ```
 *
 * @internal The aspect type `CategoryFilterMapper` and its settings are public API, this
 *           class is not.
 */
#[Exclude]
final class CategoryFilterMapper implements PersistedMappableAspectInterface, SiteLanguageAwareInterface, UnresolvedValueInterface
{
    use SiteLanguageAccessorTrait;
    use UnresolvedValueTrait;

    /**
     * The slug is what a title sanitises to, the uid is the part the URL is resolved by.
     */
    private const PART_PATTERN = '/^[\p{L}\p{M}0-9-]+-([1-9][0-9]*)$/uD';

    /**
     * The largest uid the `uid` column holds. A larger number in a URL matches no category,
     * and is not handed to the database, where PostgreSQL rejects it for an integer column.
     */
    private const MAXIMUM_UID = 2147483647;

    private const TABLE_NAME = 'sys_category';

    private const TITLE_FALLBACK = 'category';

    /**
     * @var list<string>
     */
    private readonly array $categoryTypeIdentifiers;

    private readonly string $emptyValue;

    /**
     * @var list<array{locale: string, value: string}>
     */
    private readonly array $localeMap;

    /**
     * @param array<string, mixed> $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $group = $settings['group'] ?? null;
        if (!is_string($group) || $group === '') {
            throw new \InvalidArgumentException('group must be a non-empty string', 1790760601);
        }
        $emptyValue = $settings['emptyValue'] ?? 'all';
        if (!is_string($emptyValue) || !$this->isUsableToken($emptyValue)) {
            throw new \InvalidArgumentException(
                'emptyValue must be a non-empty string without slash or comma that does not look like a category',
                1790760602
            );
        }
        $localeMap = $settings['localeMap'] ?? [];
        if (!is_array($localeMap)) {
            throw new \InvalidArgumentException('localeMap must be an array', 1790760603);
        }
        $validatedLocaleMap = [];
        foreach ($localeMap as $item) {
            if (!is_array($item)
                || !is_string($item['locale'] ?? null)
                || !is_string($item['value'] ?? null)
                || !$this->isUsableToken($item['value'])
            ) {
                throw new \InvalidArgumentException(
                    'every item of localeMap must have a string locale and a value usable as emptyValue',
                    1790760604
                );
            }
            $validatedLocaleMap[] = ['locale' => $item['locale'], 'value' => $item['value']];
        }
        try {
            $categoryTypeIdentifiers = GeneralUtility::makeInstance(CategoryTypeRegistry::class)
                ->getCategoryTypeIdentifierByGroup($group);
        } catch (\InvalidArgumentException $exception) {
            throw new \InvalidArgumentException(
                sprintf('group "%s" has no category types', $group),
                1790760605,
                $exception
            );
        }

        $this->settings = $settings;
        $this->categoryTypeIdentifiers = array_values($categoryTypeIdentifiers);
        $this->emptyValue = $emptyValue;
        $this->localeMap = $validatedLocaleMap;
    }

    public function generate(string $value): ?string
    {
        if ($value === '') {
            return $this->getEmptyToken();
        }
        $uids = [];
        foreach (explode(',', $value) as $part) {
            $uid = $this->toUid($part);
            if ($uid === null) {
                return null;
            }
            $uids[] = $uid;
        }

        $titles = $this->findTitles($uids);
        $slugHelper = GeneralUtility::makeInstance(SlugHelper::class, self::TABLE_NAME, 'title', ['fallbackCharacter' => '-']);
        $parts = [];
        foreach ($uids as $uid) {
            if (!isset($titles[$uid])) {
                return null;
            }
            $parts[] = $this->createSlug($slugHelper, $titles[$uid]) . '-' . $uid;
        }

        return implode(',', $parts);
    }

    public function resolve(string $value): ?string
    {
        if ($value === $this->getEmptyToken()) {
            return '';
        }
        $uids = [];
        foreach (explode(',', $value) as $part) {
            if (!preg_match(self::PART_PATTERN, $part, $matches)) {
                return null;
            }
            $uid = $this->toUid($matches[1]);
            if ($uid === null) {
                return null;
            }
            $uids[] = $uid;
        }

        $existingUids = array_keys($this->findDefaultTitles($uids));
        foreach ($uids as $uid) {
            if (!in_array($uid, $existingUids, true)) {
                return null;
            }
        }

        return implode(',', $uids);
    }

    /**
     * A token has to fit into one path segment and must never be read as a category part,
     * which `all-5` would be.
     */
    private function isUsableToken(string $token): bool
    {
        return $token !== ''
            && strpbrk($token, '/,') === false
            && !preg_match(self::PART_PATTERN, $token);
    }

    private function getEmptyToken(): string
    {
        // The same matching as core's LocaleModifier, so both are configured alike.
        $locale = (string)$this->siteLanguage->getLocale();
        foreach ($this->localeMap as $item) {
            $pattern = '#^' . str_replace('_', '-', $item['locale']) . '#i';
            if (preg_match($pattern, $locale)) {
                return $item['value'];
            }
        }
        return $this->emptyValue;
    }

    private function toUid(string $value): ?int
    {
        if (!preg_match('/^[1-9][0-9]*$/D', $value) || strlen($value) > 10) {
            return null;
        }
        $uid = (int)$value;
        return $uid <= self::MAXIMUM_UID ? $uid : null;
    }

    /**
     * The titles of those categories of the list that are visible and belong to the group,
     * keyed by uid, each in the language of the URL where a translation exists.
     *
     * @param list<int> $uids
     * @return array<int, string>
     */
    private function findTitles(array $uids): array
    {
        $titles = $this->findDefaultTitles($uids);

        return array_replace($titles, $this->findTranslatedTitles(array_keys($titles)));
    }

    /**
     * The default-language titles of those categories of the list that are visible and
     * belong to the group, keyed by uid.
     *
     * @param list<int> $uids
     * @return array<int, string>
     */
    private function findDefaultTitles(array $uids): array
    {
        $queryBuilder = $this->createQueryBuilder();
        $result = $queryBuilder
            ->select('uid', 'title')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList(array_values(array_unique($uids))),
                ),
                $queryBuilder->expr()->in(
                    'type',
                    $queryBuilder->quoteArrayBasedValueListToStringList($this->categoryTypeIdentifiers),
                ),
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter([0, -1], Connection::PARAM_INT_ARRAY),
                ),
            )
            ->orderBy('uid')
            ->executeQuery();

        $titles = [];
        while ($row = $result->fetchAssociative()) {
            $titles[(int)$row['uid']] = (string)$row['title'];
        }

        return $titles;
    }

    /**
     * The most specific translation of each category, following the fallback chain of the
     * site language. A category without one keeps its default-language title.
     *
     * @param list<int> $uids
     * @return array<int, string>
     */
    private function findTranslatedTitles(array $uids): array
    {
        $languageIds = array_values(array_filter(
            $this->resolveAllRelevantLanguageIds(),
            static fn(int $languageId): bool => $languageId > 0,
        ));
        if ($uids === [] || $languageIds === []) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder();
        $result = $queryBuilder
            ->select('l10n_parent', 'sys_language_uid', 'title')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->in(
                    'l10n_parent',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList($uids),
                ),
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList($languageIds),
                ),
            )
            ->orderBy('uid')
            ->executeQuery();

        $titles = [];
        $ranks = [];
        while ($row = $result->fetchAssociative()) {
            $parent = (int)$row['l10n_parent'];
            $rank = (int)array_search((int)$row['sys_language_uid'], $languageIds, true);
            if (!isset($ranks[$parent]) || $rank < $ranks[$parent]) {
                $ranks[$parent] = $rank;
                $titles[$parent] = (string)$row['title'];
            }
        }

        return $titles;
    }

    private function createSlug(SlugHelper $slugHelper, string $title): string
    {
        // The slug helper keeps slashes, which would split the path segment.
        $slug = $slugHelper->sanitize(str_replace('/', '-', $title));

        return $slug !== '' ? $slug : self::TITLE_FALLBACK;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->setRestrictions(
            GeneralUtility::makeInstance(FrontendRestrictionContainer::class, GeneralUtility::makeInstance(Context::class))
        );
        // As in core's persisted mappers: the frontend user groups are not known while
        // routing, so a category restricted to a group has to stay reachable.
        $queryBuilder->getRestrictions()->removeByType(FrontendGroupRestriction::class);

        return $queryBuilder;
    }
}
