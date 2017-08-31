# Upgrade Notes for Upgrades within Pimcore 5

## Build 100

### Objects were renamed to Data Objects
The introduction of object type hints in PHP 7.2 forced us to rename several namespaces to be compliant with the
[PHP 7 reserved words](http://php.net/manual/de/reserved.other-reserved-words.php).  
- Namespace `Pimcore\Model\Object` was renamed to `Pimcore\Model\DataObject`
    - PHP classes of Data Objects are now also in the format eg. `Pimcore\Model\DataObject\News`
- Several other internal classes were renamed or moved as well
    - `Pimcore\Event\Object*` to `Pimcore\Event\DataObject*`
    - `Pimcore\Model\User\Workspace\Object` to `Pimcore\Model\User\Workspace\DataObject`
- [Object Placeholders](../../19_Development_Tools_and_Details/23_Placeholders/01_Object_Placeholder.md) syntax changed to `%DataObject()`
- There's a compatibility autoloader which enables you to still use the former namespace (< PHP 7.2), but you should migrate asap. to the new namespaces.
- After the update please consider the following: 
    - If you're using custom [class overrides](../../20_Extending_Pimcore/03_Overriding_Models.md) in your `app/config/config.yml`, please adapt them using the new namespace.
    - If you're using event listeners on object events, please rename them as well to `pimcore.dataobject.*`
    - Your code should continue to work as before, due to the compatibility autoloader, which is creating class aliases on the fly
    - Update your `.gitignore` to exclude `/var/classes/DataObject` instead of `/var/classes/Object`
- If the update fails, please try the following: 
    - fix the above configuration changes, mainly the class overrides and potentially other relevant configurations
    - `composer dump-autoload`
    - `./bin/console cache:clear --no-warmup`
    - run the [migration script](https://github.com/pimcore/pimcore/blob/master/update-scripts/100/postupdate.php#L19) manually on cli 
  
## Build 96 (2017-09-22)

If you get an error like the following while upgrading to build 96, please run `composer update` manually on the command
line and continue to upgrade:

```
Class Doctrine\Bundle\MigrationsBundle\Command\MigrationsExecuteDoctrineCommand not found in ExecuteCommand.php (line 8)
```

You can avoid this problem by installing the *BEFORE* running the upgrade:

```bash
$ composer require doctrine/doctrine-migrations-bundle "^1.2"
```

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
    
### Session related BC breaks

The admin session ID can't be injected via GET parameter anymore. This was possbile in previous Pimcore versions to support
Flash based file uploaders but was obsolete.


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
