# Upgrade Notes for Upgrades within Pimcore 5

## Build 54 (2017-05-16)

Added new nested naming scheme for document editables, which allows reliable copy/paste in nested block elements. Pimcore
defaults to the new naming scheme for fresh installations, but configures updated installations to use the legacy scheme.

To configure Pimcore to use the legacy naming scheme manually, set the following config:

```yaml
pimcore:
    documents:
        editables:
            naming_strategy: legacy
```

For details see [issue](https://github.com/pimcore/pimcore/issues/1467) and [PR](https://github.com/pimcore/pimcore/pull/1527)
on GitHub. The following details will be handled in later builds:

* A migration script from old to new naming scheme will be implemented in [#1525](https://github.com/pimcore/pimcore/issues/1525)
* The `getElement()` interface to fetch block data manually will be adapted to the new naming scheme in [#1535](https://github.com/pimcore/pimcore/issues/1535)

