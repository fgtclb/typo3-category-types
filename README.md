[![Latest Stable Version](https://poser.pugx.org/fgtclb/category-types/v/stable.svg?style=for-the-badge)](https://packagist.org/packages/fgtclb/category-types)
[![TYPO3 11.5](https://img.shields.io/badge/TYPO3-11.5-green.svg?style=for-the-badge)](https://get.typo3.org/version/11)
[![License](http://poser.pugx.org/fgtclb/category-types/license?style=for-the-badge)](https://packagist.org/packages/fgtclb/category-types)
[![Total Downloads](https://poser.pugx.org/fgtclb/category-types/downloads.svg?style=for-the-badge)](https://packagist.org/packages/fgtclb/category-types)
[![Monthly Downloads](https://poser.pugx.org/fgtclb/category-types/d/monthly?style=for-the-badge)](https://packagist.org/packages/fgtclb/category-types)

# TYPO3 extension `category_types`

This extension provides the basic configuration for typed categories.
It is not recommended to use this extension alone, as it only provides a basic
framework. In order to perform typification, an addition in a separate extension
is required.

## Installation

Install with your flavour:

* [TER](https://extensions.typo3.org/extension/category_types/)
* Extension Manager
* composer

We prefer composer installation:
```bash
composer req fgtclb/category-types
```

|                  | URL                                                        |
|------------------|------------------------------------------------------------|
| **Repository:**  | https://github.com/fgtclb/typo3-category-types             |
| **Read online:** | https://docs.typo3.org/p/fgtclb/category-types/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/category_types/     |


## Create a release (maintainers only)

Prerequisites:

* git binary
* ssh key allowed to push new branches to the repository
* GitHub command line tool `gh` installed and configured with user having permission to create pull requests.

**Prepare release locally**

> Set `RELEASE_BRANCH` to branch release should happen, for example: 'main'.
> Set `RELEASE_VERSION` to release version working on, for example: '0.1.4'.

```shell
echo '>> Prepare release pull-request' ; \
  RELEASE_BRANCH='main' ; \
  RELEASE_VERSION='0.1.4' ; \
  git checkout main && \
  git fetch --all && \
  git pull --rebase && \
  git checkout ${RELEASE_BRANCH} && \
  git pull --rebase && \
  git checkout -b prepare-release-${RELEASE_VERSION} && \
  composer require --dev "typo3/tailor" && \
  ./.Build/bin/tailor set-version ${RELEASE_VERSION} && \
  composer remove --dev "typo3/tailor" && \
  git add . && \
  git commit -m "[TASK] Prepare release ${RELEASE_VERSION}" && \
  git push --set-upstream origin prepare-release-${RELEASE_VERSION} && \
  gh pr create --fill-verbose --base ${RELEASE_BRANCH} --title "[TASK] Prepare release for ${RELEASE_VERSION} on ${RELEASE_BRANCH}" && \
  git checkout main && \
  git branch -D prepare-release-${RELEASE_VERSION}
```

Check pull-request and the pipeline run.

**Merge approved pull-request and push version tag**

> Set `RELEASE_PR_NUMBER` with the pull-request number of the preparation pull-request.
> Set `RELEASE_BRANCH` to branch release should happen, for example: 'main' (same as in previous step).
> Set `RELEASE_VERSION` to release version working on, for example: `0.1.4` (same as in previous step).

```shell
RELEASE_BRANCH='main' ; \
RELEASE_VERSION='0.1.4' ; \
RELEASE_PR_NUMBER='123' ; \
  git checkout main && \
  git fetch --all && \
  git pull --rebase && \
  gh pr checkout ${RELEASE_PR_NUMBER} && \
  gh pr merge -rd ${RELEASE_PR_NUMBER} && \
  git tag ${RELEASE_VERSION} && \
  git push --tags
```

This triggers the `on push tags` workflow (`publish.yml`) which creates the upload package,
creates the GitHub release and also uploads the release to the TYPO3 Extension Repository.
