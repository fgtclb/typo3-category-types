..  _developers-category-tree:

=========================
Reading the category tree
=========================

A list that matches a selected category by its whole subtree, as the program
list does with its field :guilabel:`Include subcategories`, needs the tree
twice: once downwards, to widen a selection by its subcategories, and once
upwards, to offer a parent in the filter when a listed record carries one of
its subcategories. :php:`\FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository`
has a method for each.

..  note::

    The repository is not listed on the `extension points page of
    academic_base <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Developers/ExtensionPoints/Index.html>`__,
    so these methods are not public API. They serve the list plugins of the
    academic extensions and may change with them.

..  _developers-category-tree-descendants:

The subcategories of a selection
================================

..  code-block:: php

    public function findDescendantUids(string $group, int ...$uids): array

Returns the uids of the subcategories of each given category, at any depth,
keyed by the given uid and ordered by uid. A category without subcategories,
and an unknown uid, map to an empty list:

..  code-block:: php

    $this->categoryRepository->findDescendantUids('programs', 1, 2);
    // [1 => [3, 4, 5], 2 => [8]]

Only categories of the types of the group in the default language take part,
and the default restrictions of the query builder apply. A hidden or deleted
category, and one of a type outside the group, end the walk, so the categories
below them are not subcategories either. The given categories themselves are not
checked, the caller hands over categories it has already resolved. A uid of 0
or less maps to an empty list.

The tree is read one level per statement, however many categories are given,
and a uid that was reached before is not read again. Two categories that name
each other as parent are subcategories of each other, and the walk ends.

..  _developers-category-tree-applicable:

Offering a parent
=================

..  code-block:: php

    public function findAllApplicableWithSubcategories(
        string $group,
        GetCategoryCollectionInterface ...$entities
    ): CategoryCollection

Returns what :php:`findAllApplicable()` returns, every visible category of the
group with the ones no entity carries disabled, and then enables every ancestor
of an enabled category. It reads the parents of the rows the query returned, as
the default language stores them, and runs no further query. A translation
with a parent of its own does not change the tree. A category missing from the
collection, because it is hidden or of a type outside the group, ends the walk
upwards exactly where :php:`findDescendantUids()` ends it downwards.
