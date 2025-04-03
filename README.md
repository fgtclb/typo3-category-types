[![Latest Stable Version](https://poser.pugx.org/fgtclb/category-types/v/stable.svg?style=for-the-badge)](https://packagist.org/packages/fgtclb/category-types)
[![TYPO3 11.5](https://img.shields.io/badge/TYPO3-11.5-green.svg?style=for-the-badge)](https://get.typo3.org/version/11)
[![License](http://poser.pugx.org/fgtclb/category-types/license?style=for-the-badge)](https://packagist.org/packages/fgtclb/category-types)
[![Total Downloads](https://poser.pugx.org/fgtclb/category-types/downloads.svg?style=for-the-badge)](https://packagist.org/packages/fgtclb/category-types)
[![Monthly Downloads](https://poser.pugx.org/fgtclb/category-types/d/monthly?style=for-the-badge)](https://packagist.org/packages/fgtclb/category-types)

# TYPO3 extension `category_types` (READ-ONLY)

|                  | URL                                                        |
|------------------|------------------------------------------------------------|
| **Repository:**  | https://github.com/fgtclb/typo3-category-types             |
| **Read online:** | https://docs.typo3.org/p/fgtclb/category-types/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/category_types/     |

## Description

This extension provides the basic configuration for typed categories.
It is not recommended to use this extension alone, as it only provides a basic
framework. In order to perform typification, an addition in a separate extension
is required.

> [!NOTE]
> This extension is currently in beta state - please notice that there might be changes to the structure

## Compatibility

| Branch | Version   | TYPO3       | PHP                                     |
|--------|-----------|-------------|-----------------------------------------|
| main   | 2.0.x-dev | ~v12 + ~v13 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |
| 1      | 1.2.x-dev | v11 + ~v12  | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |

> [!IMPORTANT]
> The 2.x TYPO3 v12 and v13 support is not guaranteed over all extensions
> yet and will most likely not get it. It has only been allowed to install
> all of them with 1.x also in a TYPO3 v12 to combining them in the mono
> repository.
> Support in work and at least planned to be archived when releasing `2.0.0`.

## Installation

Install with your flavour:

* [TER](https://extensions.typo3.org/extension/category_types/)
* Extension Manager
* composer

We prefer composer installation:

```bash
composer require 'fgtclb/category-types'
```

> [!IMPORTANT]
> `2.x.x` is still in development and not all academics extension are fully tested in v12 and v13,
> but can be installed in composer instances to use, test them. Testing and reporting are welcome.

**Testing 2.x.x extension version in projects (composer mode)**

It is already possible to use and test the `2.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

```shell
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and installed with:

```shell
composer require \
  'fgtclb/category-types':'2.*.*@dev'
```

## Upgrade from `1.x`

Upgrading from `1.x` to `2.x` includes breaking changes, which needs to be
addressed manualy in case not automatic upgrade path is available. See the
[UPGRADE.md](./UPGRADE.md) file for details.

## Credits

This extension was created by [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).
