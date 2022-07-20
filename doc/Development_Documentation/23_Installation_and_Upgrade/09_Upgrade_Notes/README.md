# Upgrade Notes

## 10.5.0
- [Sessions] Changed default value for `symfony.session.cookie_secure` to `auto`
- [Listings] `JsonListing` class is deprecated. Please use `CallableFilterListingInterface`, `FilterListingTrait` and `CallableOrderListingInterface`, `OrderListingTrait` instead.
  For examples please see existing classes, e.g. `Pimcore\Model\Document\DocType\Listing`.

- [Security] It is recommended to use new [Authenticator based system](https://pimcore.com/docs/pimcore/current/Development_Documentation/Development_Tools_and_Details/Security_Authentication/Authenticator_Based_Security.html) for authentication/authorization.
  To use new authenticator, add following symfony config in `security.yaml`:
    ```yaml
    security:
        enable_authenticator_manager: true
    ```
- [Elements] Calling the methods `Asset::getById()`, `Document::getById()` and `DataObject::getById()` with second boolean parameter `$force` is deprecated and will throw exception in Pimcore 11. Instead pass the second parameter as associative array with `$force` value.
  e.g. Before 
   ```php
    Asset::getById($id, true);
    Document::getById($id, true);
    DataObject::getById($id, true);
   ```
    After
   ```php
    Asset::getById($id, ['force' => true]);
    Document::getById($id, ['force' => true]);
    DataObject::getById($id, ['force' => true]);
   ```
  
- [Navigation Builder] Calling the method `Pimcore\Navigation\Builder::getNavigation()` using extra arguments is deprecated and will be removed in Pimcore 11. Instead of using the extra arguments, it is recommended to call the method using the params array. eg: Currently, the `getNavigation()` method can be called by passing the config params `activeDocument`, `navigationRootDocument`, `htmlMenuIdPrefix`, `pageCallback`, `cache`, `maxDepth` and `cacheLifetime` as the arguments i.e `getNavigation($activeDocument, $navigationRootDocument, $htmlMenuIdPrefix, $pageCallback, $cache,$maxDepth, $cacheLifetime)`. According to the new implementation you should call the method like `getNavigation($params)` where `$params` should be an associative array with the keys `active`, `root`, `htmlMenuPrefix`, `pageCallback`, `cache`, `maxDepth` and `cacheLifetime`.
  
- [Runtime Cache] The trait `\Pimcore\Cache\RuntimeCacheTrait` has been deprecated because of its ambiguous naming and usage of persisted cache along with the runtime object cache.
  It is recommended to use `\Pimcore\Cache\RuntimeCache` instead of this trait. For persisted cache, please use `\Pimcore\Cache` instead.
- [Sitemap] Pimcore is now also supporting Presta/Sitemap `^3.2` (which supports Symfony 6 and uses max level of PHPStan).
  Please note, if the routing import config is in use, it is recommended to correct the config path (by removing `/Resources`) to follow the [new folder tree structure](https://github.com/prestaconcept/PrestaSitemapBundle/releases/tag/v3.0.0),
  eg. "@PrestaSitemapBundle/~~Resources/~~config/routing.yaml", to ensure a smoother upgrade to upcoming major release.
- [Backend search] `key` and `index` columns have been added to the search index. Run `./bin/console pimcore:search-backend-reindex` to reindex.
- [Cache] Pimcore use DoctrineDbalAdapter instead of PdoAdapter by default now.
- Removed `ocramius/package-versions` dependency. If you rely on it, please add it to your own `composer.json`.
- [Permissions] Added an extra check about [system permission](https://pimcore.com/docs/pimcore/current/Development_Documentation/Administration_of_Pimcore/Users_and_Roles.html#page_System-Permissions) in element `isAllowed()` method, please make sure your custom implementations are not affected by this change. 
  Listing, grid, tree view are not severely affected as the main permission is checked on a Kernel event level that prevents the page to be shown and prevents any process that iterate isAllowed() calls. 
  The only cases could be affected are those where the workspace are set but master permissions are disallowed, before this change, it could lead to (not intended) false positive.
- [Security/User] `UsernameNotFoundException` (deprecated since Symfony 5.3) occurences have been replaced with `UserNotFoundException`.
- [Deprecated] Generate type declarations option in class definition is deprecated, because type declarations will always be added with Pimcore 11
- [Application Logger] File Objects are now stored in the flysystem. Due some incompatibilities of checking files by modification date (cloud storages) and for perfomance issues (scan folders/file), the cleanup task now do not run in time range from [midnight and 4 a.m.](https://github.com/pimcore/pimcore/pull/7164) anymore, but it deletes the file matching the column in the database as soon as the database entries are archived.
- [Session] Implementing Session Configurator with tag `pimcore.session.configurator` to register session bags, is deprecated and will be removed in Pimcore 11.
  Implement an [EventListener](https://github.com/pimcore/pimcore/blob/10.x/bundles/EcommerceFrameworkBundle/EventListener/SessionBagListener.php) to register a session bag before the session is started.
- [Ecommerce][PricingManager] Token condition is deprecated and will be removed in Pimcore 11.
- Parameter `pimcore.admin.session.attribute_bags` is deprecated and will be removed in Pimcore 11.
- [JS Events/Disabled] The plugin broker for fire events has been disabled. 
The plugins are still supported, but they are deprecated and will be removed in Pimcore 11   
Please use [event listener](../../20_Extending_Pimcore/13_Bundle_Developers_Guide/06_Event_Listener_UI.md) instead of plugins for JS events.
- Extension Manager - Registering or managing bundles/areabricks through `Tools -> Bundles & Bricks` or manually through `var/config/extensions.php` is deprecated and will not work on Pimcore 11.
  Please use `config/bundles.php` to register/manage bundles instead.
- [Web2Print] Wkhtmltopdf Processor has been deprecated and will be removed in Pimcore 11. Please use HeadlessChrome or PDFreactor instead.
- [Config] `Pimcore\Config\Config` has been deprecated and will be removed in Pimcore 11.
- The recommended nginx config for static pages has been updated (the old one still works!) from
  ```nginx
  server {
      ...

      location @staticpage{
          try_files /var/tmp/pages$uri.html $uri /index.php$is_args$args;
      }

      location / {
          error_page 404 /meta/404;
          error_page 418 = @staticpage;
          if ($args ~* ^(?!pimcore_editmode=true|pimcore_preview|pimcore_version)(.*)$){
              return 418;
          }
          try_files $uri /index.php$is_args$args;
      }

      ...
  }
  ```
  to
  ```nginx
  map $args $static_page_root {
      default                                 /var/tmp/pages;
      "~*(^|&)pimcore_editmode=true(&|$)"     /var/nonexistent;
      "~*(^|&)pimcore_preview=true(&|$)"      /var/nonexistent;
      "~*(^|&)pimcore_version=[^&]+(&|$)"     /var/nonexistent;
  }

  server {
      ... 

      location / {
          error_page 404 /meta/404;
          try_files $static_page_root$uri.html $uri /index.php$is_args$args;
      }

      ...
  }
  ```

## 10.4.2
- When maintenance mode is active, all commands are prevented from starting (not just commands inheriting from `AbstractCommand`).
  Until now, some commands (e.g. `messenger:consume`) could be executed even if the system was in maintenance mode.
  To circumvent, use `--ignore-maintenance-mode` option, which is available to all commands.

## 10.4.0
- **Important**: The folder structure for storing thumbnails changed, please run `bin/console pimcore:migrate:thumbnails-folder-structure` after the update to copy existing thumbnails to new folder structure. If you're dealing with a huge amount of thumbnails you should consider that this change might increase the load on your system as well as page-loading times during the migration command is executed, as non-existing thumbnails are then generated on demand. 
- [Image Optimizer] Optimize Image messages are now routed to different queue
  instead of `pimcore_core`. If you want to handle image optimize messages, then it is required to add specific option `pimcore_image_optimize` to the command `bin/console messenger:consume pimcore_core pimcore_maintenance pimcore_image_optimize`. Also run command `bin/console messenger:consume pimcore_core` before the upgrade, so that ImageOptimize messages on the queue gets consumed.
- **Important**: [Object bricks] A call to the object brick´s getter method no longer returns object bricks marked for deletion. 
  To restore the original behavior pass "true" to the getter method´s `$includeDeletedBricks` argument. 
- [Image Optimizer] Image Optimizer services (e.g. PngCrushOptimizer, JpegoptimOptimizer etc.) are deprecated and will be
  removed in Pimcore 11. Use Pimcore\Image\Optimizer\SpatieImageOptimizer service instead.
  Currently, the existing optimizers are disabled. If you still want to use them, please re-enable them by tagging the services accordingly (in your `services.yaml`):
```yaml
    Pimcore\Image\Optimizer\CjpegOptimizer:
        tags:
            - { name: pimcore.image.optimizer }

    Pimcore\Image\Optimizer\JpegoptimOptimizer:
        tags:
            - { name: pimcore.image.optimizer }

    Pimcore\Image\Optimizer\PngCrushOptimizer:
        tags:
            - { name: pimcore.image.optimizer }
```

- [Elements] Fixed the behavior of `setId()` method, so not to cast null Id to 0 as explained below:
```php
$object = new \Pimcore\Model\DataObject();
$object->setId(null);

//before:
$oldId = $object->getId(); //returns 0

//after:
$newId = $object->getId(); //returns null
```

## 10.3.0
- **Important**: [Symfony Messenger] Pimcore Core & Maintenance messages are now routed to different queues instead of default. It is
  required to run command `bin/console messenger:consume pimcore_core pimcore_maintenance` before the upgrade, so that
  the messages on default queue gets consumed.
- **Important**: In preparation of upgrade to Symfony 6, return types must be added to methods which are extended from Symfony. In Pimcore, following changes are applied:
    - Return type added to safe methods, which are `final` & marked as `internal`.
    - Return type hints are added as comments on the methods which are extendable and phpdocs are updated. These return types will be introduced in Pimcore 11. You must add return types, if you're extending any of these Pimcore classes. Please check changes [here](https://github.com/pimcore/pimcore/pull/10846/files)
- [Documents] Introduced additional interfaces for editable methods `getDataEditmode()`, `rewriteIds()` & `load()`. Existing `method_exists` calls are deprecated and will be removed in Pimcore 11.
- [Data objects] Default values now get saved to versions -> Restoring a version also restores the default values (before those fields were null after restoring)
- Method `create()` from `Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation` and `Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation` no longer saves the new object, but just returns the instance. Related changes can be found here: https://github.com/pimcore/pimcore/pull/11326/files 

## 10.2.0
- [Maintenance] Maintenance tasks are now handled with Symfony Messenger. The `pimcore:maintenance` command will add the maintenance messages to the bus and runs them afterwards immediately from the queue. However it's recommended to setup independent workers that process the queues, by running `bin/console messenger:consume pimcore_core pimcore_maintenance` (using e.g. Supervisor) and adding `--async` option to the `pimcore:maintenance` command that stops the maintenance command to process the queue directly. Details about setting it up for production environments, please check [Symfony Messenger Component docs](https://symfony.com/doc/current/messenger.html#deploying-to-production).
- [Maintenance] Image Optimizer maintenance task moved to Messages which are consumed by Symfony Messenger. If you want to disable the image optimization, please add config to avoid dispatching the messages on messenger bus:
```yaml
pimcore:
    assets:
        image:
            thumbnails:
                image_optimizers:
                    enabled: false
```
- [Configs] The default storage for configurations is now `yaml` files in the `var/config` directory and are loaded as Symfony Config. The old `php` config-files continue to work, however, changes on existing configurations as well as new configurations are written to `yaml`.  
**Important notice**: writing configs to `yaml` is only supported if the kernel is in debug mode, because changes of the config need a rebuild of the container configuration.  
If you require to change the config on production environments we recommend to change the storage to `settings-store` as described [here](../../21_Deployment/03_Configuration_Environments.md). 
- [Asset] Pimcore now automatically supports AVIF image format for thumbnails using `auto` format (only `Imagick`). To disable AVIF please [follow this instructions](../../04_Assets/03_Working_with_Thumbnails/01_Image_Thumbnails.md).  
- [DataObject API] There is change in behavior when validating the inherited dataobjects & variants. As before, the inherited object gets saved with invalid attribute value, if the parent object has a valid value for the same attribute. Now, the API will throw validation exception, if the inherited object has an invalid value. please see https://github.com/pimcore/pimcore/pull/10529 

## 10.1.0
- [Core] Additional interfaces for data-types introduced. Existing `method_exists` calls are deprecated and will
  be removed in Pimcore 11.
- [InstallBundle] Installer preconfiguration path changed from `app/config/installer.yml` to `config/installer.yaml`.
- [Core] composer.json: `symfony/symfony` package requirement has been replaced by `symfony/*` individual bundles. **Note for Bundles**: if you are using `symfony/symfony` dependency, it will now conflict with package `pimcore/pimcore`. Please move your bundle requirements to Symfony individual component packages.
- [[Ecommerce][TrackingManager] event name in method `trackCheckoutComplete()` changed from `checkout` to `purchase` for `GoogleTagManager` implementation](https://github.com/pimcore/pimcore/pull/9366/files).
- [Glossary] `pimcoreglossary()` tag has been deprecated in favor of `pimcore_glossary` Twig filter and will be removed in Pimcore 11.
- Bumped `google/apiclient` to 2.10 version - Use proper namespaces for API references.
- Bumped `endroid/qr-code` to version 4.
- [[Ecommerce][TrackingManager] event name in method `trackCheckoutComplete()` changed from `checkout` to `purchase` for `GoogleTagManager` implementation](https://github.com/pimcore/pimcore/pull/9366/files)
- [Password encoding] Pimcore Password Encoder factory has been deprecated in favor of new Password Hasher factory, to align with Symfony authentication system. The default factory is used as default and to switch to new Password hasher factory, please enable through config `factory_type` as follows:
```yaml
pimcore:
    security:
        factory_type: password_hasher

        # the password hasher factory as defined in services.yml
        password_hasher_factories:
            App\Model\DataObject\User: website_demo.security.password_hasher_factory
```
and use new service handler:
```yaml
services:
    website_demo.security.password_hasher_factory:
        class: Pimcore\Security\Hasher\Factory\UserAwarePasswordHasherFactory
        arguments:
            - Pimcore\Security\Hasher\PasswordFieldHasher
            - ['password']
```
- [Session] Default setting for `framework.session.cookie_samesite` changed to `strict`. 
  For more information about the possible impact on your project, please have a look at the [docs of set-cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite). 
  If you prefer to stay on the old session cookie behavior, please add the following to your project configuration: 
```yaml
framework:
    session:
        cookie_samesite: 'lax'
```  

## 10.0.0

### System Requirements
     - PHP >= 8.0
     - Apache >= 2.4

### Database
    - MariaDB >= 10.3
    - MySQL >= 8.0
    - Percona Server (supported versions see MySQL)
### Changes
- Bumped `symfony/symfony` to "^5.2.0". Pimcore X will only support Symfony 5.
- ExtJS bumped to version 7.
- Bumped:
    - `guzzlehttp/guzzle` to "^7.2"
    - `sensio/framework-extra-bundle` to "^6.1"
    - `twig/twig` to "^3.0"
    - `egulias/email-validator` to "^3.0.0"
    - `onnov/detect-encoding` to "^2.0"
    - `mjaschen/phpgeo` to "^3.0"
    - `matomo/device-detector` to "^4.0"
    - `ext-imagick` to "^3.4.0" (suggest)
    - `lcobucci/jwt` to "^4.0"

- `Pimcore\Model\DataObject\ClassDefinition\Data::isEqual()` has been removed. For custom data types, implement `\Pimcore\Model\DataObject\ClassDefinition\Data\EqualComparisonInterface` instead.
- `Pimcore\Model\Document\Editable`(former. `Tags`) properties visibility changed from `protected` to `private`.
- [Templating]
    - PHP templating engine (including templating helpers & vars) has been removed to support Symfony 5. Use Twig or Php Templating Engine Bundle(enterprise) instead.
    - Removed ViewModel.
    - Removed Auto view rendering.
    - Removed Placeholder support. Use Twig Parameters instead.

- `Pimcore\Model\Tool\Tracking\Event` has been removed.
- `Pimcore\Tool\Archive` has been removed.
- The object query table will now consider the fallback language. If you want to keep the old behavior set `pimcore.objects.ignore_localized_query_fallback` in your configuration.
- Removed QR Codes.
- Remove Linfo Integration.
- [Ecommerce][IndexService] Removed FactFinder integration.
- Removed `Pimcore\Model\Tool\Lock`.
- Removed HybridAuth integration.
- Removed `Pimcore\Model\Document\Tag\*` classes. Use `Pimcore\Model\Document\Editable\*` classes instead.
- Removed `pimcore_tag_` css classes, use `pimcore_editable_` css instead.
- Removed REST Webservice API.
- Removed Legacy [Service aliases](https://github.com/pimcore/pimcore/pull/7281/files).
- [Document] Removed support for edit.php on Area-Bricks. Use new feature: Editable Dialog Box instead.
- [Glossary] Removed support for `Acronym`. Use `Abbr` instead.
- [Element] Added `setProperties()` and `setProperty()` methods to `Pimcore\Model\Element\ElementInterface`.
- [Element] `setProperty()` method param `$inheritable` defaults to false. Adding a new property will create a non-inheritable property for documents.
- [Document] Removed Editable Naming Strategy Support.
- Removed Cookie Policy Info Bar Integration.
- Removed `\Pimcore\Browser` class. Use `\Browser` instead.
- Method signature `PageSnippet::setEditable(string $name, Editable $data)` has been changed to `PageSnippet::setEditable(Editable $editable)`.
- Removed Tag & Snippet Management.
- Removed `Pimcore\Controller\EventedControllerInterface`. Use `Pimcore\Controller\KernelControllerEventInterface` and `Pimcore\Controller\KernelResponseEventInterface` instead.
- Doctrine dependencies bumped to latest major version:
    - "doctrine/common": "^3.0.0"
    - "doctrine/inflector": "^2.0.0"
- Removed service `pimcore.implementation_loader.document.tag`. Use `Pimcore\Model\Document\Editable\Loader\EditableLoader` instead.
- Removed Pimcore Bundles generator and command `pimcore:generate:bundle`.
- `Pimcore\Controller\Controller` abstract class now extends `Symfony\Bundle\FrameworkBundle\Controller\AbstractController` instead of `Symfony\Bundle\FrameworkBundle\Controller\Controller`.
- `Pimcore\Translation\Translator::transChoice()` & `Pimcore\Bundle\AdminBundle\Translation\AdminUserTranslator::transChoice()` methods have been removed. Use `trans()` method with `%count%` parameter.
- Removed `pimcore.documents.create_redirect_when_moved` config. Please remove from System.yml.
- Removed `pimcore.workflows.initial_place` config. Use `pimcore.workflows.initial_markings` instead.
- `WebDebugToolbarListenerPass` has been removed and `WebDebugToolbarListener` has been marked as final & internal.
- Bumped `sabre/dav` to ^4.1.1
- Removed `Pimcore\Model\Element\Reference\Placeholder` class.
- Removed `pimcore.routing.defaults`. Use `pimcore.documents.default_controller` instead.
- Removed `\Pimcore\Tool::getRoutingDefaults()`, `PageSnippet::$module|$action|get/setAction()|get/setModule()`, `DocType::$module|$action|get/setAction()|get/setModule()`, `Staticroute::$module|$action|get/setAction()|get/setModule()`.
- Removed `\Pimcore\Tool::getValidCacheKey/()`, use `preg_replace('/[^a-zA-Z0-9]/', '_', $key)` instead.
- Removed `\Pimcore\Tool::isValidPath/()`, use `\Pimcore\Model\Element\Service::isValidPath()` instead.
- Deprecated `\Pimcore\Model\Element\Service::getSaveCopyName()`, use `getSafeCopyName()` instead.
- Using dynamic modules, controllers and actions in static routes (e.g. `%controller`) does not work anymore.
- Removed `\Pimcore\Controller\Config\ConfigNormalizer`.
- Removed `pimcore_action()` Twig extension. Use Twig `render()` instead.
- Removed `\Pimcore\Console\Log\Formatter\ConsoleColorFormatter`
- Removed `\Pimcore\Console\CliTrait`, use `php_sapi_name() === 'cli'` instead.
- Removed `\Pimcore\Console\Dumper`, use Symfony's `VarDumper` instead.
- Removed `\Pimcore\Google\Webmastertools`, use `\Pimcore\Config::getReportConfig()->get('webmastertools'')` instead.
- Removed `\Pimcore\Helper\JsonFormatter`, use `json_encode($data, JSON_PRETTY_PRINT)` instead.
- Removed `\Pimcore\Log\Handler\Mail`, there's no replacement for this internal class.
- Removed `\Pimcore\File::isIncludeable()` method, there's no replacement.
- Removed `\Pimcore\DataObject\GridColumnConfig\AbstractConfigElement` just implement `\Pimcore\DataObject\GridColumnConfig\ConfigElementInterface` instead.
- [Documents] Renderlet Editable: removed `action` & `bundle` config. Specify controller reference, e.g. `App\Controller\FooController::myAction`
- Bumped `codeception/codeception` to "^4.1.12".
- Pimcore Bundle Migrations: Extending the functionality of `DoctrineMigrationsBundle` is not any longer possible the way we did it in the past. Therefore we're switching to standard Doctrine migrations, this means also that migration sets are not supported anymore and that the available migrations have to be configured manually or by using flex.
    ```yaml
      doctrine_migrations:
          migrations_paths:
              'Pimcore\Bundle\DataHubBundle\Migrations': '@PimcoreDataHubBundle/Migrations'
              'CustomerManagementFrameworkBundle\Migrations': '@PimcoreCustomerManagementFrameworkBundle/Migrations'
    ```
  However, we've extended the doctrine commands to accept an optional `--prefix` option, which let's you filter configured migration classes. This is in a way an adequate replacement for the `-s` (sets) option.

  `./bin/console doctrine:migrations:list --prefix=Pimcore\\Bundle\\CoreBundle`

- [Ecommerce] Added `setItems($items)` method `CartInterface`, `getRule()` to `ModificatedPriceInterface` & `getId()` method to `ProductInterface`.
- [Data Objects] Relation Data-Types: throws exception if object without an ID was assigned. e.g.
    ```php
    $newObject = new MyDataObject();
    $existingObject->setMyRelations([$newObject]);
    $existingObject->save(); //validation error
    ```
- [Data Objects] ManyToMany Relation Types: throws exception if multiple assignments passed without enabling Multiple assignments on class definition.
- [Data Objects] ReverseManyToManyObjectRelation - now supports reverse relations from ManyToOneRelation field and has been renamed to ReverseObjectRelation with BC layer.
- [Data Object] Table Data-Type always return an array.
- [Data Object] `Model::getById()` & `Model::getByPath()` do not catch exceptions anymore.
- Added methods `getArgument($key)`, `setArgument($key, $value)`, `getArguments()`, `setArguments(array $args = [])`, `hasArgument($key)` to `Pimcore\Model\Element\ElementInterface\ElementEventInterface`.
- [Ecommerce] Changed name of interfaces from I* to *Interface .e.g. `Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable` => `Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface`
- Removed `cache/tag-interop`dependency.
- [Cache] `Pimcore\Cache` is directly based on Symfony/Cache. If you have custom cache pool configured under `pimcore.cache.pools` then change it to Symfony Config `framework.cache.pools`. [Read more](https://pimcore.com/docs/pimcore/master/Development_Documentation/Development_Tools_and_Details/Cache/index.html#page_Configuring-the-cache)
- Methods `checkCsrfToken()`, `getCsrfToken()`, `regenerateCsrfToken()` methdos have been removed from `Pimcore\Bundle\AdminBundle\EventListener\CsrfProtectionListener`. Use `Pimcore\Bundle\AdminBundle\Security\CsrfProtectionHandler` instead.
- Bumped `phpunit/phpunit` & `codeception/phpunit-wrapper` to "^9"
- Replaced `html2text/html2text` with `soundasleep/html2text`. Removed methods from `Pimcore\Mail`: `determineHtml2TextIsInstalled()`, `setHtml2TextOptions($options = [])`, `getHtml2TextBinaryEnabled()`, `enableHtml2textBinary()`, `getHtml2textInstalled()`.
- Replaced `doctrine/common` with `doctrine/persistence`.
- [Asset] Image thumbnails: Using getHtml() will return `<picture>` tag instead of `<img>` tag.
- [Asset] Config option `pimcore.assets.image.thumbnails.webp_auto_support` was removed, since the browser detection is not needed anymore using the `<picture>` tags for thumbnails. 
- [Ecommerce] Marked `AbstractOrder` & `AbstractOrderItem` classes as abstract.
    Changes on `AbstractOrder` class:
    - Added: `getCartHash()`, `getComment()`, `setComment()`
    - Removed: `getDeliveryEMail()`, `setDeliveryEMail()`, `getCartModificationTimestamp()`
- [Data Objects] Data-Types: Removed getPhpdocType() BC layer
- [Ecommerce][FilterService] Added method `getFilterValues()` to `Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType`
- [Data Objects] OwnerAwareFieldInterface: added methods `_setOwner($owner)`, `_setOwnerFieldname(?string $fieldname)`, `_setOwnerLanguage(?string $language)`, `_getOwner()`, `_getOwnerFieldname()`, _getOwnerLanguage() and removed method `setOwner($owner, string $fieldname, $language = null)`.
- [Translations] Remove `pimcore.translations.case_insensitive` support.
- [Core] Folder structure updated to support Symfony Flex. Changes as per [Symfony Docs](https://symfony.com/doc/5.2/setup/flex.html)
- [Translations] `Pimcore\Model\Translation\AbstractTranslation`, `Pimcore\Model\Translation\Admin` and `Pimcore\Model\Translation\Website` with corresponding listing classes have been removed. Use new class `Pimcore\Model\Translation` with domain support (`Translation::DOMAIN_DEFAULT` or `Translation::DOMAIN_ADMIN`).
- Replaced `scheb/two-factor-bundle` with `scheb/2fa-bundle`, `scheb/2fa-google-authenticator` & `scheb/2fa-qr-code`.
- Removed Laminas Packages.
- Removed Zend Compatibility Query Builder.
- [Ecommerce] Payment Providers: Removed `WirecardSeamless`, `Qpay`, `Paypal` integration and moved to a separate bundle:
    - `Datatrans` => https://github.com/pimcore/payment-provider-datatrans
    - `Heidelpay` => https://github.com/pimcore/payment-provider-unzer
    - `Hobex` => https://github.com/pimcore/payment-provider-hobex
    - `Klarna` => https://github.com/pimcore/payment-provider-klarna
    - `Mpay24Seamless` => https://github.com/pimcore/payment-provider-mpay24-seamless
    - `OGone` => https://github.com/pimcore/payment-provider-ogone
    - `PayPalSmartPaymentButton` => https://github.com/pimcore/payment-provider-paypal-smart-payment-button
    - `PayU` => https://github.com/pimcore/payment-provider-payu

- [Core] Security configurations not merged anymore from custom bundles.
- `twig/extensions` dependency has been removed.
- Removed legacy transliterator (changes related to `\Pimcore\Tool\Transliteration::_transliterationProcess`).
- Config: Invalid pimcore configurations will result in compile error:
    ```yaml
      pimcore:
         xyz:
            xyz:
    ```
- [Data Objects] Removed `getFromCsvImport()` method from data-types.
- Replaced `Ramsey/Uuid` with `Symfony/Uuid`.
- Matomo Integration has been removed.
- `Pimcore\Tool\Console::exec()` method has been removed. Use Symfony\Component\Process\Process instead.
- `\Pimcore\Tool\Console::getOptions()` method has been removed.
- `\Pimcore\Tool\Console::getOptionString()` method has been removed.
- `\Pimcore\Tool\Console::checkCliExecution()` method has been removed.
- `Pimcore\Twig\Extension\Templating\Navigation::buildNavigation()` method has been removed.
- `Pimcore\Tool\Mime` class has been removed. Use `Symfony\Component\Mime\MimeTypes` instead.
- [Documents] Areabricks: location changed from `Areas` to `areas` with BC layer.
- [Documents] Areablocks: Adding a brick to areablocks will not trigger reload by default anymore and should be configured per Brick.
- SQIP support has been removed.
- `Thumbnail::getHtml()` doesn't accept direct pass of HTML attributes such as `class` or `title` anymore, use `imageAttributes` or `pictureAttributes` instead.
- Removed methods `getForcePictureTag()` and `setForcePictureTag()` from `\Pimcore\Model\Asset\Image\Thumbnail\Config`
- `\Pimcore\Model\Document\Editable\Block\AbstractBlockItem::getElement()` has been removed, use `getEditable()` instead.
- `\Pimcore\Model\DataObject\Service::removeObjectFromSession()` has been removed, use `removeElementFromSession()` instead.
- `\Pimcore\Model\DataObject\Service::getObjectFromSession()` has been removed, use `getElementFromSession()` instead.
- `\Pimcore\Model\Asset\Image\Thumbnail\Config::setColorspace()` has been removed
- `\Pimcore\Model\Asset\Image\Thumbnail\Config::getColorspace()` has been removed
- `\Pimcore\Model\DataObject\ClassDefinition\Data\DataInterface` has been removed
- `\Pimcore\Model\Asset\Listing::getPaginatorAdapter` has been removed, use `knplabs/knp-paginator-bundle` instead.
- `\Pimcore\Model\Document\Listing::getPaginatorAdapter` has been removed, use `knplabs/knp-paginator-bundle` instead.
- `\Pimcore\Model\DataObject\Listing::getPaginatorAdapter` has been removed, use `knplabs/knp-paginator-bundle` instead.
- `\Pimcore\Google\Cse::getPaginatorAdapter` has been removed, use `knplabs/knp-paginator-bundle` instead.
- `\Pimcore\Helper\RobotsTxt` has been removed
- `\Pimcore\Tool\Frontend::getSiteKey()` method has been removed.
- `\Pimcore\Model\User::getUsername()` has been removed, use `User::getName()` instead.
- `\Pimcore\Cache\Runtime::get('pimcore_editmode')` isn't supported anymore, use `EditmodeResolver` service instead.
- [Documents] `Editable::factory()` was removed, use `EditableLoader` service instead.
- [Data Objects] Removed CSV import feature. Use https://github.com/pimcore/data-importer or https://github.com/w-vision/DataDefinitions instead.
- [DataObjects] marked `Pimcore\DataObject\GridColumnConfig\Operator` operator classes as final and internal
- [DataObjects] Calculator classes of Calculated Values must implement the `Pimcore\Model\DataObject\ClassDefinition\CalculatorClassInterface` now.
- [DataObjects] PHP Class `Pimcore\Model\DataObject\Data\Geopoint` has been replaced with `GeoCoordinates`. Changed the signature of `__construct`.
- Added `Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::getFilterValues()` with the same signature as `getFilterFrontend()`. To upgrade, rename `getFilterFrontend()` to `getFilterValues()` and remove the rendering stuff to just return the data array.

    Before:
    ```php
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter)
    {
        // ...
        return $this->render($this->getTemplate($filterDefinition), [
            //...
        ]);
    }
    ```
    After:
    ```php
    public function getFilterValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter): array
    {
        // ...
        return [
            //...
        ];
    }
    ```
- Added Validation for Geo datatypes
    - for Geopolyline and Geopolygon invalid data doesn't get serialized 1:1 anymore
    - for Geobounds and Geopoint invalid data doesn't get dropped silently anymore
- Calling `$imageAsset->getThumbnail('non-existing-thumbnail-definition)` with a non-existing thumbnail definition will now throw an exception. Same goes for video assets and video image thumbnails.
- Removed grid column operator `ObjectBrickGetter` since it is obsolete
- Grid operator `AnyGetter` available only for admin users from now on
- [Ecommerce] Added `getAttributeConfig` method to `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ConfigInterface` interface
- [Ecommerce] Added `getClientConfig` method to `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearchConfigInterface`
- [Ecommerce] Added abstract method `setSuccessorOrder` to `Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder`
- [Ecommerce] Indexing doesn't catch any exceptions that occur during preprocessing of attributes in BatchProcessing workers (e.g. elasticsearch).
  You can change that behavior with event listeners.
- [Ecommerce] Added abstract method `setCartHash` to `Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder`
- [Ecommerce] Added `getFieldNameMapped` to ` Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearchConfigInterface`
- [Ecommerce] Added `getReverseMappedFieldName` to ` Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearchConfigInterface`
- [Ecommerce] Changed tenant config type hint to `FindologicConfigInterface` in `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultFindologic::__construct`
- [Ecommerce] Changed price fields `totalNetPrice` and `totalPrice` of `OnlineShopOrderItem` to decimal.
- [Ecommerce] Removed deprecated configuration options `enabled`, `pricing_manager_id` and `pricing_manager_options` for pricing_manager.
  Use tenant specific options.
- [Ecommerce] Removed deprecated functions `get/setCurrentTenant` and `get/setCurrentSubTenant`
  of `EnvironmentInterface`
- [Ecommerce] Removed deprecated service alias for `Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment`
- [Ecommerce] Removed deprecated functions `getGeneralSearchColumns`, `createOrUpdateTable`, `getIndexColumns` and `getIndexColumnsByFilterGroup`
  of `IndexService`
- [Ecommerce] Removed deprecated function `getPaginatorAdapter` from
  `ProductList\MySql`, `ProductList\DefaultFindologic`, `ProductList\ElasticSearch\AbstractElasticSearch`, `Token\Listing` and `AbstractOrderList`
- [Ecommerce] Removed deprecated functions `getCalculatedPrice` and `getCalculatedPriceInfo` from `AbstractSetProduct`
- [Ecommerce] Removed deprecated protected function `getAvailableFilterValues` from `Order\Listing`
- [Ecommerce] Activated `generateTypeDeclarations` for all generated data object classes and field collections. For migration
  activate `generateTypeDeclarations` to all Ecommerce Framework data object classes and update your source code accordingly.
- [Ecommerce] Made methods abstract instead of throwing `UnsupportedException` where easily possible for model classes (`AbstractProduct`, `AbstractSetProduct`, `AbstractOfferToolProduct`, `AbstractOfferItem`, `AbstractOffer`).
- [Ecommerce] Added type declarations to Ecommerce Framework product interfaces (`ProductInterface`, `IndexableInterface`, `CheckoutableInterface`).
- [Ecommerce] Removed Elasticsearch 5 and 6 support
- [Ecommerce] `getItemAmount` and `getItemCount` of `Carts` now require string parameter (instead of boolean). Use one of
`CartInterface::COUNT_MAIN_ITEMS_ONLY`, `CartInterface::COUNT_MAIN_AND_SUB_ITEMS`, `CartInterface::COUNT_MAIN_OR_SUB_ITEMS`.
- [Ecommerce] Removed legacy CheckoutManager architecture, migrate your project to V7 if not already
  - `CancelPaymentOrRecreateOrderStrategy` is now default strategy for handling active payments
  - Removed method `isCartReadOnly` from cart and `cart_readonly_mode` configuration option as readonly mode
    does not exist anymore.
  - Removed deprecated method `initPayment` from `PaymentInterface`
- [Ecommerce] Removed deprecated `ecommerce:indexservice:process-queue` command,
  use `ecommerce:indexservice:process-preparation-queue` or `ecommerce:indexservice:process-update-queue` instead
- [Ecommerce] Removed deprecated `mapping` option in index attributes configuration (never worked properly anyway)
- [Ecommerce] Removed deprecated `IndexUpdater` tool
- [Ecommerce] Removed legacy BatchProcessing worker mode, product centric batch processing is now standard
  - Removed abstract class `AbstractBatchProcessingWorker`, use `ProductCentricBatchProcessing` instead
  - Removed methods from interface `BatchProcessingWorkerInterface` and its implementations:
     - `BatchProcessingWorkerInterface::processPreparationQueue`
     - `BatchProcessingWorkerInterface::processUpdateIndexQueue`
  - Added methods to interface `BatchProcessingWorkerInterface`
    - `BatchProcessingWorkerInterface::prepareDataForIndex`
    - `BatchProcessingWorkerInterface::resetPreparationQueue`
    - `BatchProcessingWorkerInterface::resetIndexingQueue`
  - Removed constants
     - `ProductCentricBatchProcessingWorker::WORKER_MODE_LEGACY`
     - `ProductCentricBatchProcessingWorker::WORKER_MODE_PRODUCT_CENTRIC`
  - Removed configuration node `worker_mode` in `index_service` configuration
- [Ecommerce] Moved method `getIdColumnType` from `MysqlConfigInterface` to `ConfigInterface`. Since it was and still is
  implemented in `AbstractConfig` this should not have any consequences.
- [Ecommerce] Timestamp of CartItems is now in mirco seconds (existing data will be migrated).
- [Ecommerce][PricingManager] Added two new interfaces `ProductActionInterface` and `CartActionInterface`. All actions
  need to implement either of it - otherwise they will not be considered anymore in price calculation.
- [Web2Print]
   - Removed `PdfReactor8`, use `PdfReactor` instead.
   - Removed PDFreactor version selection in web2print settings, since most current PDFreactor client lib
     should be backwards compatible to older versions.
- [Email & Newsletter] Swiftmailer has been replaced with Symfony Mailer. `\Pimcore\Mail` class now extends from `Symfony\Component\Mime\Email` and new mailer service `Pimcore\Mail\Mailer` has been introduced, which decorates `Symfony\Component\Mailer\Mailer`, for sending mails.

    Email method and transport setting has been removed from System settings. Cleanup Swiftmailer config and setup mailer transports "main" & "newsletter" in config.yaml:
    ```yaml
    framework:
        mailer:
            transports:
                main: smtp://user:pass@smtp.example.com:port
                pimcore_newsletter: smtp://user:pass@smtp.example.com:port
    ```
    please see [Symfony Transport Setup](https://symfony.com/doc/5.2/mailer.html#transport-setup) for more information.

    API changes:

    Before:
    ```php
        $mail = new \Pimcore\Mail($subject = null, $body = null, $contentType = null, $charset = null);
        $mail->setBodyText("This is just plain text");
        $mail->setBodyHtml("<b>some</b> rich text: {{ myParam }}");
        ...
    ```
    After:
    ```php
        $mail= new \Pimcore\Mail($headers = null, $body = null, $contentType = null);
        $mail->text("This is just plain text");
        $mail->html("<b>some</b> rich text: {{ myParam }}");
        ...
    ```

    Before:
    ```php
      $mail->setFrom($emailAddress, $name);
      $mail->setTo($emailAddress, $name);
      ...
    ```

    After:
    ```php
      $mail->from(new \Symfony\Component\Mime\Address($emailAddress, $name));
      $mail->to(new \Symfony\Component\Mime\Address($emailAddress, $name));
      ...
    ```
- [Email & Newsletter] `\Pimcore\Mail::setEnableLayoutOnRendering/getEnableLayoutOnRendering()` methods have been removed, with Twig they are just not necessary anymore.
- [Email & Newsletter] `\Pimcore\Mail::isValidEmailAddress()` method has been removed, use `EmailValidator` instead.
- [Security] BruteforceProtectionHandler & BruteforceProtectionListener has been made final and marked as internal.
- [JWTCookieSaveHandler] `Pimcore\Targeting\Storage\Cookie\JWT\Decoder` has been removed in favor of `Lcobucci\JWT\Encoding\JoseDecoder`.
- `simple_html_dom` library has been removed. Use `Symfony\Component\DomCrawler\Crawler` instead.
- Removed deprecated Twig extension `pimcore_action()`.
- Removed method `getFlag()` from `Pimcore\Config`.
- Removed `Pimcore\Report` class.
- [Versioning] Default behavior has been changed to following:
    - Empty values for `steps` & `days` => unlimited versions.
    - Value 0 for `steps` or `days` => no version will be saved at all & existing will be cleaned up.

  please update your system settings as per the requirements.
- Removed deprecated `marshal()` and `unmarshal()` methods from object data-types.
- `DynamicTextLabelInterface::renderLayoutText()` must handle nullable object param.
- [AdminBundle] Marked classes and controllers as @internal/final - please see all changes here: https://github.com/pimcore/pimcore/pull/8453/files & https://github.com/pimcore/pimcore/pull/8988/files
