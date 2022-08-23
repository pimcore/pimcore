# Preparing Pimcore for Version 11

## Preparatory Work
- Upgrade to version 10.5.x, if you are using a lower version.
- [Security] Enable New Security Authenticator and adapt your security.yaml as per changes [here](https://github.com/pimcore/demo/blob/11.x/config/packages/security.yaml) :
    ```
    security:
        enable_authenticator_manager: true
    ```
    Points to consider when moving to new Authenticator:
  - New authentication system works with password hasher factory instead of encoder factory.
  - BruteforceProtectionHandler will be replaced with Login Throttling.
  - Custom Guard Authenticator will be replaced with Http\Authenticator.
- [Type hints] Check and add **return type hints** for classes extending Pimcore classes or implementing interfaces provided by Pimcore, based on the source phpdoc or comments on the methods.
  The return types will be added to Pimcore classes, so you `must` add return types to your classes extending Pimcore.
  You could use the patch-type-declarations tool, provided by symfony, to check for affected methods. For details please have a look [here](https://symfony.com/doc/5.4/setup/upgrade_major.html#4-update-your-code-to-work-with-the-new-version).


- [Javascript] Replace plugins with [event listener](../../20_Extending_Pimcore/13_Bundle_Developers_Guide/06_Event_Listener_UI.md) as follows:
    ```javascript
    pimcore.registerNS("pimcore.plugin.MyTestBundle");

    pimcore.plugin.MyTestBundle = Class.create(pimcore.plugin.admin, {
        getClassName: function () {
            return "pimcore.plugin.MyTestBundle";
        },
    
        initialize: function () {
            pimcore.plugin.broker.registerPlugin(this);
        },
    
        preSaveObject: function (object, type) {
            var userAnswer = confirm("Are you sure you want to save " + object.data.general.o_className + "?");
            if (!userAnswer) {
                throw new pimcore.error.ActionCancelledException('Cancelled by user');
            }
        }
    });
    
    var MyTestBundlePlugin = new pimcore.plugin.MyTestBundle();
    ```
    
    ```javascript
    document.addEventListener(pimcore.events.preSaveObject, (e) => {
        let userAnswer = confirm(`Are you sure you want to save ${e.detail.object.data.general.o_className}?`);
        if (!userAnswer) {
            throw new pimcore.error.ActionCancelledException('Cancelled by user');
        }
    });
    ```
- [Javascript] Replace deprecated JS functions:
   - Use t() instead of ts() for translations.
   - Stop using `pimcore.helpers.addCsrfTokenToUrl`
 
- [Deprecations] Fix deprecations defined in the [upgrade notes](../09_Upgrade_Notes/README.md), which is to be removed in Pimcore 11.
  Tip: you can search for deprecations in Symfony Profiler(Debug mode) or can run linux command `tail -f var/log/dev.log | grep 'User Deprecated'` for checking deprecations on runtime.

- [Extensions] Stop using `var/config/extensions.php` for registering bundles, use `config/bundle.php` instead.

- Don't use deprecated `Pimcore\Db\ConnectionInterface` interface, `Pimcore\Db\Connection` class and `Pimcore\Db\PimcoreExtensionsTrait` trait
  Use `Doctrine\DBAL\Driver\Connection` interface and `Doctrine\DBAL\Connection` class instead.
  Some methods must be replaced:
  - Use `executeQuery()` instead of `query()`
  - Use `executeStatement()` instead of `executeUpdate()`, `deleteWhere()`, `updateWhere()`
  - Use `fetchAssociative()` instead of `fetchRow()`
  - Use `fetchFirstColumn()` instead of `fetchCol()`
  - Use `Pimcore\Db\Helper::fetchPairs()` instead of `fetchPairs()`
  - Use `Pimcore\Db\Helper::insertOrUpdate()` instead of `insertOrUpdate()`
  - Use `Pimcore\Db\Helper::quoteInto()` instead of `quoteInto()`
  - Use `quoteIdentifier()` instead of `quoteColumnAs()`
  - Don't use `quoteTableAs()`
  - Don't use `limit()`
  - Use `Pimcore\Db\Helper::queryIgnoreError()` instead of `queryIgnoreError()`
  - Use `Pimcore\Db\Helper::selectAndDeleteWhere()` instead of `selectAndDeleteWhere()`
  - Use `Pimcore\Db\Helper::escapeLike()` instead of `escapeLike()`
