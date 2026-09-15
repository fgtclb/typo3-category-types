..  _developers-icons:

Category type icons
===================

A category type does not register its icon in :file:`Configuration/Icons.php`.
It names an icon **file** in :file:`Configuration/CategoryTypes.yaml`, and this
extension registers it on :php:`BootCompletedEvent`:

..  code-block:: yaml
    :caption: EXT:example/Configuration/CategoryTypes.yaml

    types:
      - identifier: degree
        title: 'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.example.degree'
        group: example
        icon: 'EXT:example/Resources/Public/Icons/CategoryTypes/Degree.svg'

The registration happens in
:php:`\FGTCLB\CategoryTypes\ServiceProvider::addIcons()`. The icon identifier is
derived, never written by hand -
:php:`\FGTCLB\CategoryTypes\Domain\Model\CategoryType::getIconIdentifier()`
builds it from the group and the type:

..  code-block:: text

    category_types.<group>.<type>

For the example above that is :php:`category_types.example.degree`, and that is
the identifier the :php:`sys_category` :php:`typeicon_classes` entry uses and
the identifier a template addresses:

..  code-block:: html

    <core:icon identifier="category_types.example.degree" />

..  _developers-icons-provider:

Which provider the icon gets
----------------------------

The one :php:`\TYPO3\CMS\Core\Imaging\IconRegistry::detectIconProvider()`
answers for the file: :php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`
for an SVG, :php:`\TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider` for
a bitmap.
