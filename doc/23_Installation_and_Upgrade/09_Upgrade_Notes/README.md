# Upgrade Notes

## Pimcore 11.6.0
### Elements
#### [Documents]
- Video Editable: Passing an invalid allowedTypes config will throw an exception.

## Pimcore 11.5.0
### General
#### [Database]
- Adding index to `users_workspaces_asset`, `users_workspaces_document` and `users_workspaces_object` tables on `userId`, `cpath` and `list` to improve permission checks.  
  Make sure to run the migration `bin/console doctrine:migrations:execute Pimcore\\Bundle\\CoreBundle\\Migrations\\Version20241114142759`.
- Added an index on `versionCount` columns
#### [Events]
- `context` property of `ResolveUploadTargetEvent` is deprecated. Use `setArgument()` method instead.
- `pimcore_block` Twig extension is deprecated. Use `pimcoreblock` or `pimcoremanualblock` instead.
#### [Controllers]
- Replaced all `$request->get()` with their explicit input source.
#### [Dependencies]
- Added support to `doctrine/orm` `v3` and `symfony/webpack-encore-bundle` `v2`.
#### [WYSIWYG-Editor]
- `TinyMCE` is deprecated. Use `Quill` (`pimcore/quill-bundle`) instead.
### Elements
#### [Assets]
- Introduced `pimcore.assets.metadata.alt`, `pimcore.assets.metadata.copyright`, `pimcore.assets.metadata.title` configuration to allow defining which metadata should be used when rending the image tag.
#### [DataObjects]
- Passing an non-existing or invalid unit when programmatically setting QuantityValue related object types will throw an exception.

## Pimcore 11.4.0
### General
#### [Logging]
- Changed log file names. In the `dev` environment, the file names are now `dev-debug.log` and `dev-error.log`. In the `prod` environment, only `prod-error.log` is written.
#### [Twig Deferred Extension]
- Removed `rybakit/twig-deferred-extension` dependency and `Twig\DeferredExtension\DeferredExtension` service.
  If you use deferred twig blocks, please add the dependency to your own `composer.json` and the service to your own `service.yaml`.
#### [Twig Extension Deprecations]
- `pimcore_cache` Twig extension is deprecated. Use `pimcorecache` twig tag instead.
- `pimcore_placeholder`, `pimcore_head_script`, `pimcore_head_style` 
  - `captureStart()` and `captureEnd()` methods are deprecated. Use native twig `set` tag instead. Take a look at the related docs of each twig extension for an example.

#### [Notification]
- Extending notifications for studio adding flag `isStudio` column and a `payload` column with according getters and setters.
  Make sure to run the migration `bin/console doctrine:migrations:execute Pimcore\\Bundle\\CoreBundle\\Migrations\\Version20240813085200`.

## Pimcore 11.3.0
### General
#### [System Settings]
- Unused setting `general.language` has been deprecated.
#### [Listing]
- The methods `setOrder()` and `setOrderKey()` throw an `InvalidArgumentException` if the parameters are invalid now.
#### [Html to Image]
- [Gotenberg] Bumped the lowest requirement of `gotenberg-php` from `^2.0` to `^2.4` to add support of passing screenshot size
#### [Assets]
- MIME type of uploaded assets get determined by `symfony/mime`, before in some cases Flysystem got used which resulted in different MIME types for some rarely used file extensions (e.g. STEP).
#### [Grid]: 
- Moved grid data related function to `admin-classic-ui-bundle` `v1.5`.
- Method `Service::getHelperDefinitions()` is deprecated here and moved to `admin-classic-ui-bundle`.
#### [Simple Backend Search]
- Due to grid data refactoring, please note that in order to run this optional bundle correctly, it is required to install `admin-classic-ui-bundle` `v1.5`
#### [DBAL]
- Bumped minimum requirement of `doctrine/dbal` to `^3.8` and replaced deprecated/unused methods to get closer to support `v4`.
#### [Composer]
- Removed requirement of "phpoffice/phpspreadsheet" due it being not in used, more specifically moved it to the specific bundle who actually use it. Please check and adapt your project's composer requirement accordingly.
#### [Dependency]
- Dependencies are now resolved by messenger queue and can be turned off. By default, it is done synchronously, but it's possible to make it async by add the following config:
```yaml
framework:
    messenger:
        transports:
            pimcore_dependencies: "doctrine://default?queue_name=pimcore_dependencies"
        routing:
            'Pimcore\Messenger\ElementDependenciesMessage': pimcore_dependencies
```
and disable it by: 
```yaml
pimcore:
  dependency:
    enabled: false
```

## Pimcore 11.2.4 / 11.2.3.1 / 11.1.6.5
### Assets Thumbnails
- Thumbnail generation for Assets, Documents and Videos now only support the following formats out of the box: `'avif', 'eps', 'gif', 'jpeg', 'jpg', 'pjpeg', 'png', 'svg', 'tiff', 'webm', 'webp'`.
- You can extend this list by adding your formats on the bottom: 
```yaml
  pimcore:
    assets:
      thumbnails:
        allowed_formats:
          - 'avif'
          - 'eps'
          - 'gif'
          - 'jpeg'
          - 'jpg'
          - 'pjpeg'
          - 'png'
          - 'svg'
          - 'tiff'
          - 'webm'
          - 'webp'
          - 'pdf' # Add your desired format here
```
- High resolution scaling factor for image thumbnails has now been limited to a maximum of `5.0`. If you need to scale an image more than that, you can use the `max_scaling_factor` option in the configuration.
```yaml
  pimcore:
    assets:
      thumbnails:
        max_scaling_factor: 6.0
```

## Pimcore 11.2.0
### Elements
#### [Documents]:
- Using `outputFormat` config for `Pimcore\Model\Document\Editable\Date` editable is deprecated, use `outputIsoFormat` config instead.
- Service `Pimcore\Document\Renderer\DocumentRenderer` is deprecated, use `Pimcore\Document\Renderer\DocumentRendererInterface` instead.
- Page previews and version comparisons can now be rendered using Gotenberg v8.
  To replace Headless Chrome, upgrade to Gotenberg v8 and upgrade the client library: `composer require gotenberg/gotenberg-php:^2`
#### [Data Objects]:
- Methods `getAsIntegerCast()` and `getAsFloatCast()` of the `Pimcore\Model\DataObject\Data` class are deprecated now.
- All algorithms other than`password_hash` used in Password Data Type are now deprecated, please use `password_hash` instead.
- `MultiSelectOptionsProviderInterface` is deprecated, please use `SelectOptionsProviderInterface` instead.

-----------------
### General
#### [Localization]
- Services `Pimcore\Localization\LocaleService` and `pimcore.locale` are deprecated, use `Pimcore\Localization\LocaleServiceInterface` instead.
#### [Navigation]
- Add rootCallback option to `Pimcore\Navigation\Builder::getNavigation()`
#### [Symfony]
- Bumped Symfony packages to "^6.4".
#### [Value Objects]
- Added new self validating Value Objects:
  - `Pimcore\ValueObject\BooleanArray`
  - `Pimcore\ValueObject\IntegerArray`
  - `Pimcore\ValueObject\Path`
  - `Pimcore\ValueObject\PositiveInteger`
  - `Pimcore\ValueObject\PositiveIntegerArray`
  - `Pimcore\ValueObject\StringArray`

> [!WARNING]  
> For [environment variable consistency purposes](https://github.com/pimcore/pimcore/issues/16638) in boostrap, please fix `public/index.php` in project root by moving `Bootstrap::bootstrap();` just above `$kernel = Bootstrap::kernel()` line instead of outside the closure.
> Alternatively can be fixed by appling this [patch](https://patch-diff.githubusercontent.com/raw/pimcore/skeleton/pull/183.patch)
> 
> You may also need to adjust your `bin/console` to the latest version of the skeleton: https://github.com/pimcore/skeleton/blob/11.x/bin/console


## Pimcore 11.1.0
### Elements

#### [All]:
- Properties are now only updated in the database with dirty state (when calling `setProperties` or `setProperty`).
- Added hint for second parameter `array $params = []` to `Element/ElementInterface::getById`
- `Pimcore\Helper\CsvFormulaFormatter` has been deprecated. Use `League\Csv\EscapeFormula` instead.

#### [Assets]:
- Asset Documents background processing (e.g. page count, thumbnails & search text) can be disabled with config:
    ```yaml
    pimcore:
        assets:
            document:
                thumbnails:
                    enabled: false #disable generating thumbnail for Asset Documents
                process_page_count: false #disable processing page count
                process_text: false #disable processing text extraction
                scan_pdf: false #disable scanning PDF documents for unsafe JavaScript.
    ```
- Video Assets spherical metadata is now calculated in the backfground instead of on load.

#### [Data Objects]:
- Property `$fieldtype` of the `Pimcore\Model\DataObject\Data` class is deprecated now. Use the `getFieldType()` method instead.
- Method `getSiblings()` output is now sorted based on the parent sorting parameters (same as `getChildren`) instead of alphabetical.
- Input fields `CheckValidity` checks the column length.

#### [Documents]:
- Removed `allow list` filter from `Pimcore\Model\Document\Editable\Link` to allow passing any valid attributes in the config.
- Property `Pimcore\Navigation\Page::$_defaultPageType` is deprecated.

-----------------
### General

#### [Authentication]:
The tokens for password reset are now stored in the DB and are one time use only (gets expired whenever a new one is generated or when consumed).
- [Static Page Generator]: Static pages can be generated based on sub-sites main domain using below config:
    ```yaml
    pimcore:
        documents:
            static_page_router:
                use_main_domain: true #generates pages in path /public/var/tmp/pages/my-domain.com/en.html 
    ```
    and adapting NGINX config:
    ```nginx
    map $args $static_page_root {
        default                                 /var/tmp/pages/$host;
        "~*(^|&)pimcore_editmode=true(&|$)"     /var/nonexistent;
        "~*(^|&)pimcore_preview=true(&|$)"      /var/nonexistent;
        "~*(^|&)pimcore_version=[^&]+(&|$)"     /var/nonexistent;
    }
    map $uri $static_page_uri {
        default                                 $uri;
        "/"                                     /%home;
    }
  ```

#### [Core Cache Handler]:
- Remove redundant cache item tagging with own key.

#### [Installer]: 
- Passing `--install-bundles` as empty option now installs the required bundles.

#### [Maintenance Mode]:
- Maintenance mode check is handled via `tmp_store` in database. Using maintenance mode files is deprecated.
- Deprecated following maintenance-mode methods in `Pimcore\Tool\Admin`:
    - `activateMaintenanceMode`, use `MaintenanceModeHelperInterface::activate` instead.
    - `deactivateMaintenanceMode`, use `MaintenanceModeHelperInterface::deactivate` instead.
    - `isInMaintenanceMode`, use `MaintenanceModeHelperInterface::isActive instead.
    - `isMaintenanceModeScheduledForLogin`, `scheduleMaintenanceModeOnLogin`, `unscheduleMaintenanceModeOnLogin` will be removed in Pimcore 12.


------------------
## Pimcore 11.0.7
- Putting `null` to the `Pimcore\Model\DataObject\Data::setIndex()` method is deprecated now. Only booleans are allowed.

------------------
## Pimcore 11.0.0
### API
#### [General] :

-  **Attention:** Added native php types for argument types, property types, return types and strict type declaration where possible. Double check your classes which are extending from Pimcore classes and adapt if necessary. 


#### [Bootstrap] :

- Relying on `Pimcore\Bootstrap::bootstrap()` for autoloading classes will not work anymore.
- Removed unused constant `PIMCORE_APP_BUNDLE_CLASS_FILE`


#### [Events] :

-  Event `pimcore.element.note.postAdd` has been removed. Use `pimcore.note.postAdd` instead. Note: The event type changed from `ElementEvent` to `ModelEvent`.
-  Report Event `pimcore.admin.reports.save_settings` has been renamed to `pimcore.reports.save_settings`.
-  Moved `SEARCH_LIST_BEFORE_FILTER_PREPARE`, `SEARCH_LIST_BEFORE_LIST_LOAD`, `SEARCH_LIST_AFTER_LIST_LOAD`, `QUICKSEARCH_LIST_BEFORE_LIST_LOAD` and `QUICKSEARCH_LIST_AFTER_LIST_LOAD` events from `Pimcore\Bundle\AdminBundle\Event\AdminEvents` to `Pimcore\Bundle\SimpleBackendSearchBundle\Event\AdminSearchEvents`.
-  `AdminEvents::ELEMENT_PERMISSION_IS_ALLOWED` has been renamed to `Pimcore\Event\ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED`.


#### [Installer] :

-  Removed `--ignore-existing-config` option from the `pimcore:install` command. The `system.yaml` file is not used anymore and therefore this flag became obsolete. See [preparing guide](../07_Updating_Pimcore/12_V10_to_V11.md)
-  Changed the return type of `Pimcore\Extension\Bundle\Installer\InstallerInterface::getOutput` to `BufferedOutput | NullOutput`.
-  Adding `BundleSetupEvent` Event. Bundles that are available for installation can be customized in the installing process via an Eventlistener or EventSubscriber.
  - Bundles can be added and removed. You can set a flag if you want to recommend the bundle.


#### [Logging] :

-  Removed constant `PIMCORE_PHP_ERROR_LOG`
-  Bumped `monolog/monolog` to [^3.2](https://github.com/Seldaek/monolog/blob/main/UPGRADE.md#300) and `symfony/monolog-bundle` to [^3.8](https://github.com/symfony/monolog-bundle/blob/master/CHANGELOG.md#380-2022-05-10) (which adds support for monolog v3). Please adapt your custom implementation accordingly, eg. log records are now `LogRecord` Objects instead of array.
-  Removed the ability to use the `pimcore_log` GET parameter.

#### [Miscellaneous] :

- Marked `Pimcore\Model\User\AbstractUser` and `Pimcore\Model\User\UserRole` classes as abstract.
- Marked `Pimcore\File` as internal. This class shouldn't be used anymore, use `Symfony\Component\Filesystem` instead.

#### [Further Removed API Features] :

- Removed `getChilds`, `setChilds` and `hasChild` use `getChildren`, `setChildren` and `hasChildren` instead.
- Removed PhpArrayTable class
- Removed deprecated `PhpArrayFileTable`.
- Removed `Pimcore\Db\Helper::insertOrUpdate()` method, please use `Pimcore\Db\Helper::upsert()` instead.
- Removed deprecated `Pimcore\Db\ConnectionInterface` interface, `Pimcore\Db\Connection` class and `Pimcore\Db\PimcoreExtensionsTrait` trait.
- Removed `JsonListing`, please see [#12877](https://github.com/pimcore/pimcore/pull/12877) for details.
- Deprecated MissingDependencyException has been removed.
- Removed deprecated getMasterRequest() in favor of getMainRequest().
- Removed the deprecated method `Kernel::getRootDir()`, use `Kernel::getProjectDir()` instead.
- Removed methods `Pimcore\Tool\Admin::isExtJS6()`, `\Pimcore\Tool\Admin::getLanguageFile()`, `\Pimcore\Tool::exitWithError()`.
- Removed the following methods from `Pimcore\File`: `mkdir`, `put`, `getFileExtension`, `setDefaultMode`, `getDefaultMode`, `setDefaultFlags` and `rename`.


#### [Further relevant Third Party Dependency Upgrades] :

- Bumped `friendsofsymfony/jsrouting-bundle` to version `^3.2.1`
- Bumped Symfony packages to "^6.2".
- Cleanup unused Symfony packages from composer.json, eg. `form`, `web-link`, see also [#13097](https://github.com/pimcore/pimcore/pull/13097)
- Bumped `mjaschen/phpgeo` to "^4.0".
-  Bumped `codeception/codeception` version to ^5.0. Now, Pimcore is using a new directory structure for tests (Codeception 5 directory structure). For details, please see [#13415](https://github.com/pimcore/pimcore/pull/13415)
-  Bumped `matomo/device-detector` to ^6.0.
-  Bumped minimum requirement of `presta\sitemap-bundle` to `^3.3`, dropped support for `v2` and removed related BC Layer.
-  Bumped `league/flysystem-bundle` minimum requirement to ^3.0 (which introduces `directoryExists()`,`has()` methods and fixes support for `directory_visibility` configuration option). Please bump the Flysystem Adapters requirement accordingly to `^3.0` in your project `composer.json`.

-----------------
### Admin UI
#### [General] :

-  Removed `adminer` as built-in database management tool.
-  Removed deprecated Admin Event classes: `Pimcore\Event\Admin\*`, `Pimcore\Event\AdminEvents`, `Pimcore\Event\Model\*`.
-  Changed the navigation building process. It is easier to add main and submenus. For details please see [Adding Custom Main Navigation Items](https://pimcore.com/docs/platform/Pimcore/Extending_Pimcore/Bundle_Developers_Guide/Event_Listener_UI#adding-custom-main-navigation-items)

#### [Authentication] :

- Removed support old authentication system
- Removed BruteforceProtection, use Symfony defaults now
- Removed PreAuthenticatedAdminToken
- Admin Login Events
  - Removed `AdminEvents::LOGIN_CREDENTIALS` (`pimcore.admin.login.credentials`) event. Use `Pimcore\Bundle\AdminBundle\Event\Login\LoginCredentialsEvent` instead.
  - Removed `AdminEvents::LOGIN_FAILED` (`pimcore.admin.login.failed`) event. Use `Symfony\Component\HttpFoundation\Request\LoginFailureEvent` instead.
- Removed Pimcore Password Encoder factory, `pimcore_admin.security.password_encoder_factory` service and `pimcore.security.factory_type` config.
- Removed deprecated method `Pimcore\Bundle\AdminBundle\Security\User::getUsername()`, use `getIdentifier()` instead.
-  Deprecated method `Pimcore\Tool\Authentication::authenticateHttpBasic()` has been removed.
-  Deprecated method `Pimcore\Tool\Authentication::authenticatePlaintext()` has been removed.

#### [JS] :

- Packaged some JS libraries with encore
- Removed deprecated JS functions (`ts()` and `pimcore.helpers.addCsrfTokenToUrl()`)
- Removed Plugin Broker BC layer for JS events

#### [Security] :

-  Enabled Content Security Policy by default.
-  Implemented Symfony HTML sanitizer for WYSIWYG editors. Please make sure to sanitize your persisted data with help of this [script](https://gist.github.com/dvesh3/0e585a16dfbf546bc17a9eef1c5640b3).
Also, when using API to set WYSIWYG data, please pass encoded characters for html entities `<`,`>`, `&` etc.
The data is encoded by the sanitizer before persisting into db and the same encoded data will be returned by the API. For configuration details see also [WYSIWYG config](../../03_Documents/01_Editables/40_WYSIWYG.md#extending-symfony-html-sanitizer-configuration)


-----------------
### Bundles
#### [Bundles General] :

- Removed support for loading bundles through `extensions.php`.
- Removed Extension Manager(`Tools -> Bundles & Bricks` option) from Admin UI.
- Removed commands: `pimcore:bundle:enable`, `pimcore:bundle:disable`.
- Removed `dontCheckEnabled` config support from Areablock editable.
-  The default behaviour of asset install and `Composer::installAssets` is changed, which means that the files (like css, js) will be copied instead of symlinked. So, you have to run the command `bin/console assets:install` for every change. Behavior can be adapted in `composer.json` as follows: 
```json
"extra": {
  "symfony-assets-install": "relative"
}
```


#### [Extracted Core Functionality] :

- Functionality that was moved into its own bundle inside pimcore/pimcore repository and needs to be enabled during Pimcore install or in `config/bundles.php`:  
    - [Application Logger] Application logger has been moved into `PimcoreApplicationLoggerBundle`. Please pay attention to the new namespaces for the classes from this bundle.
    - [CustomReports] have been moved into PimcoreCustomReportsBundle
        - Config `pimcore:custom_reports` has been removed, please use `pimcore_custom_reports:` in the PimcoreCustomReportsBundle insteand.
    - [Glossary] has been moved into PimcoreGlossaryBundle
	    - `pimcoreglossary()` tag has been removed, please use the `pimcore_glossary` twig filter.
        - Config `pimcore:glossary` has been removed, please use `pimcore_glossary:` in the PimcoreGlossaryBundle instead.
    - [Search] has been moved into PimcoreSimpleBackendSearchBundle    
        -  The search functionality has been extracted to its own bundle (`PimcoreSimpleBackendSearchBundle`)
        - The `pimcore:search-backend-reindex` command has been moved to the search bundle
        - Search icons all over Pimcore won't be available without the search bundle
        - The inline search feature for some relations won't be available without the search bundle
        - The "advanced" GDPR search has also been moved. We provide a basic search to cover the fundamental functionality if the search bundle isn't available.
        - The asset, object, document and quick search have been moved to the search bundle
        - All backend-search related files have been moved to the search bundle (please check custom implementations if you rely on any backend-search component!)
        - Added additional messenger transport for backend search (`pimcore_search_backend_message`)
        - Moved `FullTextIndexOptimizeTask` command to SimpleBackendSearchBundle. According to that the namespace changed from `Pimcore\Maintenance\Tasks\FullTextIndexOptimizeTask` to `Pimcore\Bundle\SimpleBackendSearchBundle\Task\Maintenance\FullTextIndexOptimizeTask`.

    - [SEO] Document Editor, Redirects, Sitemaps, robots.txt and HTTP Errors has been moved into PimcoreSeoBundle
	- [Staticroutes] has been moved into PimcoreStaticRoutesBundle
        - Config `pimcore:staticroutes:` has been removed, please use `pimcore_static_routes:` in the PimcoreStaticRoutesBundle instead.		
    - [UUID] has been moved into PimcoreUuidBundle
        - Config `pimcore:general:instance_identifier` has been removed, please use `pimcore_uuid:instance_identifier` in the PimcoreUuidBundle instead. Please run `bin/console config:dump pimcore_uuid` to see more about the instance identifier config after installing the bundle.

    - [WordExport] has been moved into PimcoreWordExportBundle
	- [Xliff Translation] Import/Export and related Events have been moved into PimcoreXliffBundle. Please check and adapt the Events' namespaces.
	- [WYSIWYG-Editor] The default editor changed from `CKEditor` to `TinyMCE` and has been moved into PimcoreTinymceBundle. Please adapt custom configuration and [extend](https://pimcore.com/docs/platform/Pimcore/Documents/Editables/WYSIWYG#extending-symfony-html-sanitizer-configuration) the html sanitizer for supporting the required html elements in wysiwyg editor.



- Functionality that was moved into its own bundle and own repository and needs to installed via composer as well as activated in `config/bundles.php`: 
    - [AdminBundle] Admin Bundle has been moved to `pimcore/admin-ui-classic-bundle` package. 
	    - Please require in your project composer.json file and register the bundle in Kernel:
        ```php
        public function registerBundlesToCollection(BundleCollection $collection): void
        {
            // pimcore bundles
            $collection->addBundle(new \Pimcore\Bundle\AdminBundle\PimcoreAdminBundle\PimcoreAdminBundle(), 60);
        }
        ```
        -  Removed deprecated methods `getTranslator()`, `getBundleManager()` and `getTokenResolver()` from the `Pimcore\Bundle\AdminBundle\Controller\AdminController`
    - [System Info & Tools] Php Info and Opcache Status has been moved into `pimcore/system-info-bundle` package.
    - [File Explorer] System File explorer has been moved to `pimcore/system-file-explorer` package.
    - [Web2Print] has been moved to `pimcore/web-to-print-bundle` package.
        - Config `pimcore:documents:web_to_print` has been removed, please use `pimcore_web_to_print` in the PimcoreWebToPrintBundle instead.
        - Print related Events have been moved into PimcoreWebToPrintBundle. Please check and adapt the Events' namespaces.
        -  Deprecated HeadlessChrome Processor has been removed. Please use Chromium Processor instead.
        -  Deprecated WkHtmlToPdf Processor has been removed.
        -  Introducing Web2print processor `Chromium` that use `chrome-php/chrome` (same as the page previews), as replacement of HeadlessChrome processor which required NodeJS.		
    - [Personalization and targeting] has been moved to `pimcore/personalization` package.
        - Config `pimcore:targeting:` has been removed, please use `pimcore_personalization.targeting` in the PimcorePersonalizationBundle instead.
        - Targeting is now using the opt-in approach and will not be enabled by default. Add following config to enable it:
      ```yaml
      pimcore_personalization:
          targeting:
              enabled: true
      ```
    - [Google Marketing] has been moved to `pimcore/google-marketing-bundle` package.
        - Config `pimcore:services:google` has been removed, please use `pimcore_google_marketing` in the PimcoreGoogleMarketingBundle instead.
        - [Google] Classes Google\Cse and Google\Cse\Item have been removed.
    - [Newsletter] has been moved to `pimcore/newsletter-bundle` package.
        - Config `pimcore:newsletter` has been removed, please use `pimcore_newsletter` in the PimcoreNewsletterBundle instead.
        - Newsletter related Events have been moved into PimcoreNewsletterBundle. Please check and adapt the Events' namespaces.
        - Service ids changed from `pimcore.newsletter` to `pimcore_newsletter` e.g. `pimcore_newsletter.document.newsletter.factory.default`


### Core

#### [Commands] :

- Removed `webmozarts/console-parallelization` dependency to make parallelization optional. If you still want to use parallelization for console commands, please add the dependency to your own `composer.json`.
- Removed the deprecated `Parallelization::configureParallelization()` method.
- Removed the deprecated trait `ConsoleCommandPluginTrait`.


#### [Configuration] :

-  `Pimcore\Config\Config` has been removed, see [#12477](https://github.com/pimcore/pimcore/issues/12477). Please use the returned array instead, e.g.
    ```php
    $web2printConfig = Config::getWeb2PrintConfig();
    $web2printConfig = $web2printConfig['chromiumSettings'];
    ```
-  Removed legacy callback from LocationAwareConfigRepository. Therefore, configurations in the old php file format are not supported anymore.
-  Removed setting write targets and storage directory in the environment file. Instead, use the [symfony config](../07_Updating_Pimcore/12_V10_to_V11.md)
-  Renamed default directories from `image-thumbnails` and `video-thumbnails` to `image_thumbnails` and `video_thumbnails`.
-  Removed deprecated services/aliases: `Pimcore\Templating\Renderer\TagRenderer`, `pimcore.cache.adapter.pdo`, `pimcore.cache.adapter.pdo_tag_aware`
-  Rename config files from `*.yml` to `*.yaml`. Note that we now use `system_settings.yaml` as config file and not `system.yml`
-  System Settings are now implementing the LocationAwareConfigRepository. See [preparing guide](../07_Updating_Pimcore/11_Preparing_for_V11.md)
-  The config node `pimcore.admin` and related parameters are moved to AdminBundle directly under `pimcore_admin` node. Please adapt your parameter usage accordingly eg. instead of `pimcore.admin.unauthenticated_routes`, it should be `pimcore_admin.unauthenticated_routes`
-  The deprecated config node `pimcore.error_handling` and the related parameter `pimcore.response_exception_listener.render_error_document` was removed.
-  Moved `hide_edit_image` & `disable_tree_preview` configs from `pimcore` to `pimcore_admin` section.
-  Recommended and default format for storing the valid languages in `system_settings.yaml` is now an array, for example:
   - en
   - de
```yaml
pimcore:
    general:
        valid_languages:
            - en
            - de
```

#### [CoreBundle] :

-  Please update CoreBundle config resource path from `@PimcoreCoreBundle/Resources/config/...` to `@PimcoreCoreBundle/config/..` in your project configurations.
-  Priority of `PimcoreCoreBundle` has been changed to `-10` to make sure that it is loaded after default bundles.

#### [Environment] :

- Removed `symfony/dotenv` dependency to make loading of `.env` files optional. please add the requirement to your composer.json, if you still want to use `.env` files.
- Removed `PIMCORE_SKIP_DOTENV_FILE` environment var support. You still can use environment specific file like `.env.test` or `.env.prod` for environment specific environment variables.

#### [Gotenberg] :

-  Introducing support for [Gotenberg](https://gotenberg.dev/) as PDF generation, conversion, merge etc.. tool
- [Asset] Added adapter (as alternative to LibreOffice) for preview generation of supported document type assets and set it as default adapter.
- [Web2Print] Added settings option, configuration and processor for PDF preview and generation

#### [Maintenance] :

-  Removed `--async` & `--force` option from `pimcore:maintenance` command. Please make sure to setup to `messenger:consume pimcore_maintenance` independent

#### [Migrations] :

-  Removed `executeMigrationsUp` from `Pimcore\Composer`.
-  Pimcore does not run core migrations after `composer` update automatically anymore.
   Make sure that migrations are executed by running the command `bin/console doctrine:migrations:migrate --prefix=Pimcore\\Bundle\\CoreBundle`.

#### [Naming] :

-  Renamed master, blacklist and whitelist to main, blocklist, allowlist


#### [Permissions] :

-  Permission for DataObjects Classes has been structured in a more granular way to have more control. Field collections, objects bricks, classification stores and quantity value units now have their own permission.


#### [Sessions] :

- Removed AdminSessionHandler and AdminSessionListener. The session is now handled by Symfony.
- Removed `SessionConfiguratorInterface` & `SessionConfigurator` so services with tag `pimcore.session.configurator` will not register session bags anymore.
- Removed parameter `pimcore.admin.session.attribute_bags`
- TargetingSessionBagListener - changed the signature of `__construct`.
- `AdminSessionHandler` requires session from request stack.
- `EcommerceFrameworkBundle\SessionEnvironment` not loading from or storing into session in cli mode anymore.
- `EcommerceFrameworkBundle\Tracking\TrackingManager` requires session from request stack.

-----------------
### Ecommerce
#### [Ecommerce General] :

- Ecommerce bundle has been moved into a package `pimcore/ecommerce-bundle`. If you wish to continue using the ecommerce framework, then please require the package in your composer.json and install it after enabling in `config/bundles.php`.
- The constructor of the following services has been changed, please adapt your custom implementation accordingly:
    - `IndexService\ProductList\DefaultMysql`, `IndexService\ProductList\DefaultFindologic`
    - `IndexService\Worker\AbstractElasticSearch`, `IndexService\Worker\DefaultFindologic`, `IndexService\Worker\DefaultMysql`, `IndexService\Worker\OptimizedMysql`
    - `IndexService\Config\AbstractConfig` and it's sub-classes.
    - `Tracking\Tracker\Analytics\AbstractAnalyticsTracker` and it's sub-classes.
- Ecommerce related Events have been moved. Please check and adapt the Events' namespaces.
- [ClassDefinition\LinkGeneratorInterface] method signature has changed, instead of `Pimcore\Model\DataObject\Concrete` a `object` is used.
-  Elasticsearch 7 support was removed
-  Config option `es_client_params` in `index_service` was removed
-  Remove deprecated methods `check()` and `exists()` from class `Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation`

#### [IndexService] :

-  Please make sure to rebuild your product index to make sure changes apply accordingly (this is relevant for mysql and elasticsearch indices). As an alternative you could manually rename and remove `o_` from all index columns/fields.


#### [Product Interfaces] :

-  Changed return type-hints of `CheckoutableInterface` methods `getOSPrice`, `getOSPriceInfo`, `getOSAvailabilityInfo`, `getPriceSystemName`, `getAvailabilitySystemName`, `getPriceSystemImplementation`, `getAvailabilitySystemImplementation` to be non-nullable.

-----------------
### Elements

#### [All] :

-  Added `setParentId`, `setType` and `setParent` methods to `Pimcore\Model\Element\ElementInterface`
-  Removed fallback to parent id 1, when an element with a non-existing parent id gets created.
-  Passing $force parameter as boolean is not valid anymore in `getById`, `getByPath`, `getElementById` methods. Instead, please pass it as an associative array ( eg.`['force' => true]`).
-  Changed method signature on `Pimcore\Model\Element\ElementInterface::save()`, this changes the `::save()` method on all classes (e.g. DataObjects and Pages) implementing the interface, including those inheriting from `Concrete`/`AbstractObject`, see [#13207](https://github.com/pimcore/pimcore/issues/13207)
- Removed deprecated `getTotalCount()` method
-  Removed the deprecated `Pimcore\Model\Element\Service::getType()`, use `Pimcore\Model\Element\Service::getElementType()` instead.
- `Element\Service::getValidKey()` strips all control/unassigned, invalid and some more special (e.g. tabs, line-breaks, form-feed & vertical whitespace) characters.
- Removed deprecated `Pimcore\Model\Element\Service::getSaveCopyName()` method, please use the `Pimcore\Model\Element\Service::getSafeCopyName()` method instead.


#### [DataObjects][Assets][Documents] :

-  Datetime values for scheduled tasks, application logger and notifications are now displayed in the local timezone.


#### [DataObjects][Documents] :

- Calling `getChildren/getSiblings` on `AbstractObject`, `Document` and `Asset` now returns unloaded listing. If the list is not traveresed immediately, then it is required to call `load()` explicitily.
- Removed deprecated methods `getObject()` and `setObject()` on the classes `Pimcore\Model\Document\Link` and `Pimcore\Model\DataObject\Data\Link`, please use `getElement()` and `setElement()` instead.


-----------------

### Assets

-  Refactored `Pimcore\Model\Asset::getMetadata` method to allow listing of all metadata entries filtered by a specific language. Prior this version, the language filter was only available when a specific metadata name was defined in the parameters. Added native type hints and related tests.
-  Removed the deprecated `marshal()/unmarshal()` methods for metadata, use `normalize()/denormalize()` methods instead.
-  Removed the deprecated `Import from Server` and `Import from URL` options.
-  Asset/Asset Thumbnail Update messages are now routed to different queue
-  Removed VR Preview. For details please see [#14111](https://github.com/pimcore/pimcore/issues/14111)
-  Image thumbnails: Removed support for using custom callbacks for thumbnail transformations.
-  Removed loading assets via fixed namespace only. Custom Asset Types can be configured.
-  Thumbnails: improved method signature for `$thumbnail->getPath()`. You may now pass options as array `$thumbnail->getPath(["deferredAllowed" => true, "frontend" => false]);`
- Removed deprecated property `Pimcore\Model\Asset::$types`, use `getTypes()` instead


#### [Image Optimizer] :

-  Removed all the Image Optimizer services (e.g. PngCrushOptimizer, JpegoptimOptimizer etc.) as image optimization is done by the new package spatie/image-optimizer.


#### [WebDAV] :

-  WebDAV url has been changed from `https://YOUR-DOMAIN/admin/asset/webdav` to `https://YOUR-DOMAIN/asset/webdav`

   As result of this change, the following changes are required in your nginx configuration:
    ```
    # Assets
    ....
    location ~* ^(?!/admin)(.+?)....
    ```
    New:
    ```
    # Assets
    ....
    location ~* ^(?!/admin|/asset/webdav)(.+?)....
    ```

-----------------

### Data Objects

-  Remove "generate type declarations" in class definitions
-  Removed method_exists bc layer, please use the corresponding interfaces instead. For details please see [#9571](https://github.com/pimcore/pimcore/issues/9571)
-  `isEqual()` for advanced relational field types does not check for type equality of meta fields anymore, see [#12595](https://github.com/pimcore/pimcore/pull/12595)
-  Added return types to setter methods. For details see [#12185](https://github.com/pimcore/pimcore/issues/12185)
-  Alias `ReverseManyToManyObjectRelation` removed, please use `ReverseObjectRelation` instead.
-  Changed default behaviour: getByXXX methods on `Concrete` class now returns objects and variants if nothing else is specified.
-  Changed `$objectTypes` default value to include variants in certain scenarios.
-  Removed deprecated preview url in class editor.
-  Removed sql filter functionality for data object grid
-  Loading non-Concrete objects with the Concrete class is no longer possible
- Removed setter functions for calculated values, since they weren´t used anyway.
-  Removed `o_` prefix for data object properties and database columns.
-  Due to the removal of the `o_` prefix the property names `classTitle`, `hasChildren`, `siblings`, `hasSiblings`, `childrenSortBy`, `childrenSortOrder`, `versionCount`, `dirtyLanguages` and `dirtyFields`
-  Text data types now set their corresponding database columns to `null` instead of `''` (empty string) when empty.
-  Method `Concrete::getClass()` throws NotFoundException if class is not found for an object.
-  Change type hints of `Pimcore\Model\DataObject\QuantityValue\QuantityValueConverterInterface::convert()`:
    ```php
    public function convert(QuantityValue $quantityValue, Unit $toUnit): QuantityValue;
    ```
    ```php
    public function convert(AbstractQuantityValue $quantityValue, Unit $toUnit): AbstractQuantityValue;
    ```
-  Added global language switcher for localized fields
-  Added new helper inheritance helper function `DataObject\Service::useInheritedValues`
-  It's now possible to drop a video asset directly into an video editable in class
-  Removed Button control for DataObjects layout definition.


#### [Class Definitions] :

-  Class Resolver does not catch exceptions anymore.

#### [ClassSavedInterface] :

-  Removed `method_exists` bc layer. Please add the corresponding `ClassSavedInterface` interface to your custom field definitions. For more details check the 10.6.0 patch notes.


#### [CSV Export] :

-  Changed encoding of table data-types to `json_encode` from `base64_encoded`.


#### [CustomLayouts] :

-  Removed command `pimcore:deployment:custom-layouts-rebuild` as CustomLayouts are migrated to LocationAwareConfigRepository.


#### [Relations] :

- Add possibility to inline download asset from relations
- Add confirm dialog to empty button of relations and add possibility to disable clear relations in the class layout.


#### [UrlSlug] :

-  Removed `index` column and `index` index from `object_url_slugs` table as it was not being used anywhere.
-  Allow processing unpublished fallback document is now default behaviour, removed the related configuration options and usages (`allow_processing_unpublished_fallback_document`, `ElementListener::FORCE_ALLOW_PROCESSING_UNPUBLISHED_ELEMENTS`). For details, please see [#10005](https://github.com/pimcore/pimcore/issues/10005#issuecomment-907007745)

-----------------

### Documents

- Removed the functionality to input `metadata` html tags in Settings section of the document.
- Removed `$types` property from `Pimcore\Model\Document`. Use `getTypes` method instead.
- Removed `pimcore:document:types` from config. The types will be represented by the keys of the `type_definitions:map`
- Removed deprecated `Pimcore\Routing\Dynamic\DocumentRouteHandler::addDirectRouteDocumentType()` method, please use the `pimcore.documents.type_definitions.map.%document_type%.direct_route` config instead.
- Added `pimcore:documents:cleanup` command to remove documents with specified types and drop the related document type tables, useful in the cases like the removal of headless documents or web2print page/containers after uninstallation, see [Documents](../../03_Documents/README.md#cleanup-documents-types)
-  Removed the `attributes` field from the link editable.
-  Deprecated WkHtmlToImage has been removed.
-  Added a second boolean parameter `$validate` to the setContentMainDocumentId() method. This will restrict the option to set pages as content main documents to each other. For details, please see [#12891](https://github.com/pimcore/pimcore/issues/12891)
- Refactored `pimcore.documents.valid_tables` to be an option under each document type definition (eg. `pimcore.documents.type_definitions.map.%document_type%.valid_table`) instead of an own array node
- Moving a document in the tree no longer opens the redirect prompt asking to create redirects. Creating a redirect is now configurable with `pimcore:redirects:auto_create_redirects`. This config includes URLSlugs and Pretty URLs.
```yaml
pimcore_seo:
    redirects:
        auto_create_redirects: true
```
-  Configuration of document types has changed. Now it is possible to change the navigational behavior and more of each document type in the configuration.
```yaml
    documents:
        type_definitions:
            map:
                page:
                    class: \Pimcore\Model\Document\Page
                    translatable: true
                    valid_table: 'page'
                    direct_route: true
                    translatable_inheritance: true
                    children_supported: true
                    only_printable_childrens: false
                    predefined_document_types: true
```

#### [Areabricks] :

-  The default template location of `AbstractTemplateAreabrick` is now `TEMPLATE_LOCATION_GLOBAL`.

#### [Controllers] :

- Removed deprecated `SensioFrameworkExtraBundle` which affects the following:
    - `@Template` annotation must be replaced with `#[Template]` attribute. Template guessing based on controller::action is not supported anymore.
    - `@ResponseHeader` annotation must be replaced with `#[ResponseHeader]` attribute. Removed deprecated `Pimcore\Controller\Configuration\ResponseHeader`.
    - `@ParamConverter` annotation must be replaced with `#[DataObjectParam]` attribute.
- `FrontendController::renderTemplate()`: Changed the visibility to `protected`.


#### [Chromium] :

-  Added support to run chromium in Docker container and work via websocket for web2print and page previews, along of running it by a local binary.


#### [DocType] :

-  staticGeneratorEnabled is now a boolean instead of an integer


#### [Document Editables] :

-  Removed method_exists bc layer for `getDataEditmode()`, `rewriteIds()` & `load()`, please use the corresponding interfaces `EditmodeDataInterface`, `IdRewriterInterface` & `LazyLoadingInterface` instead.


#### [Navigation] :

-  Calling the method `Pimcore\Navigation\Builder::getNavigation()` using extra arguments is
- Methods `Pimcore\Navigation::setDefaultPageType`, `Pimcore\Navigation::getDefaultPageType`, `Pimcore\Navigation\Container::_sort() and `Pimcore\Navigation\Page::_normalizePropertyName()` have been marked as internal.


#### [Sites] :

-  Calling absolute path from a site is not possible anymore. If the absolute path is called, a 404 error will be returned instead.
-  Default Site Id has been updated from `default` to `0`. Please update configs using default site id accordingly.
#### [Video Editable] :

-  Removed [deprecated and legacy `<iframe>` attributes](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe): `frameborder`, `webkitAllowFullScreen`, `mozallowfullscreen`, and `allowfullscreen` for YouTube, Vimeo, and DailyMotion embeds.

-----------------
### Infrastructure
#### [PHP Options] :

-  Removed setting following options: `memory_limit`, `max_execution_time`, `max_input_time` and `display_errors`


#### [PHP] :

-  The minimum supported PHP version is now 8.1 and added support for 8.2

#### [Symfony] :

-  Replace deprecated `Symfony\Component\HttpFoundation\RequestMatcher` with `Symfony\Component\HttpFoundation\ChainRequestMatcher`

-----------------
### Tools
#### [Application Logger] :

-  Removed deprecated `PIMCORE_LOG_FILEOBJECT_DIRECTORY` constant, since flysystem is used to save/get fileobjects. Please make sure to adapt your code and migrate your fileobjects manually.
-  Table names of archive tables are now named with year-month rather than month-year see [#8237](https://github.com/pimcore/pimcore/issues/8237).


#### [Cache] :

-  Removed `psr/simple-cache` dependency, due to the lack of usage in the Core.
-  Responses containing a header `Cache-Control: no-store` will no longer be cached by the full page cache.
-  Removed the `Pimcore\Cache\Runtime` cache helper and `Pimcore\Cache\RuntimeCacheTrait`. The runtime cache is now handled by `Pimcore\Cache\RuntimeCache`.

#### [Console] :

-  Methods `execInBackgroundUnix` & `execInBackgroundWindows` visibility changed from `protected` to `private` and for `getSystemEnvironment` from `public` to `private`.


#### [Email] :

-  Removed the deprecated methods setBodyHtml(), setBodyText(), createAttachment() and setSubject(). Use html(),
-  Bumped `league/html-to-markdown` to ^5.1.


#### [Translations] :

-  Translations Domains needs to be registered in order to be considered as valid. If you are using custom domains
   - site_1
   - site_2
```yaml
pimcore:
    translations:
        domains:
            - site_1
            - site_2
```
-  Added Symfony's html sanitizer to `\Pimcore\Model\Translation\Dao::save` method.


#### [Workflows] :

-  Removed classes Pimcore\Model\Workflow, Pimcore\Model\Workflow\Dao, Pimcore\Model\Workflow\Listing\Dao and Pimcore\Model\Workflow\Listing.

-----------------
