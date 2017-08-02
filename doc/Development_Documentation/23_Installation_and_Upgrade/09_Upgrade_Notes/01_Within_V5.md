# Upgrade Notes for Upgrades within Pimcore 5

## Build 86 (2017-08-02)

E-Commerce Framework configuration was moved to a Symfony Config. For details see 
[Config Signature changes](./03_Ecommerce_Framework/02_Ecommerce_Framework_Config_Signature_Changes.md)


## Build 85 (2017-08-01)

This build changed how the admin session is handled and introduced a regression breaking the maintenance page checking. The
result of this regression is that subsequent updates can't be installed as the updater activates the maintenance mode and
the update can't be processed as the maintenance check does not recognize the admin session as being excluded from the maintenance.
You won't be able to interact with the admin as the system will present the maintenance page for every request.

If you updated up to build 85 and experience this issue, you can solve it with the following steps:

* remove `var/config/maintenance.php` - this allows you to open the admin interface again as it disables maintenance mode
* if you want to use the web updater please apply [these changes](https://github.com/pimcore/pimcore/commit/e4b2d2952d5e16cbea2d59b78629ab5d733d779b)
  manually before continuing the update
* alternatively you can use the CLI updater to circumvent the maintenance page. the following command would update to build 86:

    PIMCORE_ENVIRONMENT=dev bin/console pimcore:update --ignore-maintenance-mode -u 86


## Build 60 (2017-05-31)

The navigation view helper signature has changed and now uses a different syntax to render navigations. In short,
building the navigation container and rendering the navigation is now split up into 2 distinct calls and needs to be adapted
in templates. This applies to all navigation types (menu, breadcrumbs, ...).

```php
<?php
// previously
echo $this->navigation($this->document, $navStartNode)->menu()->renderMenu(null, ['maxDepth' => 1]);

// now
$nav = $this->navigation()->buildNavigation($this->document, $navStartNode);
echo $this->navigation()->menu()->renderMenu($nav, ['maxDepth' => 1]);
```

See the [navigation documentation](./../../03_Documents/03_Navigation.md) for details.

## Build 54 (2017-05-16)

Added new `nested` naming scheme for document editables, which allows reliable copy/paste in nested block elements. Pimcore
defaults to the new naming scheme for fresh installations, but configures updated installations to use the `legacy` scheme.

To configure Pimcore to use the `legacy` naming scheme strategy, set the following config:

```yaml
pimcore:
    documents:
        editables:
            naming_strategy: legacy
```

See [Editable Naming Strategies](../../03_Documents/13_Editable_Naming_Strategies.md) for information how to migrate to
the `nested` naming strategy.
