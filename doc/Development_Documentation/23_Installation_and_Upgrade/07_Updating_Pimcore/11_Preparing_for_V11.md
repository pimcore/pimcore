# Preparing Pimcore for Version 11

## Preparatory Work
- Replace plugins with [event listener](../../20_Extending_Pimcore/13_Bundle_Developers_Guide/06_Event_Listener_UI.md) as follows:
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
- [Security] Enable New Security Authenticator and adapt your security.yaml as per changes [here](https://github.com/pimcore/demo/blob/11.x/config/packages/security.yaml) :
```
security:
    enable_authenticator_manager: true
```
 - Points to consider when moving to new Authenticator:
   - New authentication system works with password hasher factory instead of encoder factory.
   - BruteforceProtectionHandler will be replaced with Login Throttling.
   - Custom Guard Authenticator will be replaced with Http\Authenticator.


- Replace deprecated JS functions
  - Use t() instead of ts()
  - Don't use pimcore.helpers.addCsrfTokenToUrl()
- `JsonListing` class is deprecated. 
  - Please use `CallableFilterListingInterface`, `FilterListingTrait` and `CallableOrderListingInterface`, `OrderListingTrait` instead.
  - For examples please see existing classes, e.g. `Pimcore\Model\Document\DocType\Listing`.
- Calling the methods `Asset::getById()`, `Document::getById()` and `DataObject::getById()` with second boolean parameter `$force` is deprecated and will throw exception in Pimcore 11. 
  - Instead pass the second parameter as associative array with `$force` value.
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
- [Navigation Builder] Calling the method `Pimcore\Navigation\Builder::getNavigation()` using extra arguments is deprecated and will be removed in Pimcore 11. 
  - Instead of using the extra arguments, it is recommended to call the method using the params array. 
   e.g. Before: 
  ```php 
     getNavigation($activeDocument, $navigationRootDocument, $htmlMenuIdPrefix, $pageCallback, $cache,$maxDepth, $cacheLifetime)
  ``` 
  After
  ```php 
     getNavigation(['active' => $activeDocument, 
                    'root' => $navigationRootDocument, 
                    'htmlMenuPrefix' => $htmlMenuIdPrefix, 
                    'pageCallback' => $pageCallback, 
                    'cache' => $cache, 
                    'maxDepth' => $maxDepth, 
                    'cacheLifetime' => $cacheLifetime])
  ``` 
- The trait `\Pimcore\Cache\RuntimeCacheTrait` has been deprecated because of its ambiguous naming and usage of persisted cache along with the runtime object cache.
  It is recommended to use `\Pimcore\Cache\RuntimeCache` instead of this trait. For persisted cache, please use `\Pimcore\Cache` instead.
- Pimcore is now also supporting Presta/Sitemap `^3.2` (which supports Symfony 6 and uses max level of PHPStan).
  Please note, if the routing import config is in use, it is recommended to correct the config path (by removing `/Resources`) to follow the [new folder tree structure](https://github.com/prestaconcept/PrestaSitemapBundle/releases/tag/v3.0.0),
  eg. "@PrestaSitemapBundle/~~Resources/~~config/routing.yaml".
- Implementing Session Configurator with tag `pimcore.session.configurator` to register session bags, is deprecated and will be removed in Pimcore 11.
  Implement an [EventListener](https://github.com/pimcore/pimcore/blob/10.x/bundles/EcommerceFrameworkBundle/EventListener/SessionBagListener.php) to register a session bag before the session is started.
- [Ecommerce][PricingManager] Token condition is deprecated and will be removed in Pimcore 11.
- Parameter `pimcore.admin.session.attribute_bags` is deprecated and will be removed in Pimcore 11.
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
- [Image Optimizer] Image Optimizer services (e.g. PngCrushOptimizer, JpegoptimOptimizer etc.) are deprecated and will be
removed in Pimcore 11. Use Pimcore\Image\Optimizer\SpatieImageOptimizer service instead.
- [Documents] Introduced additional interfaces for editable methods `getDataEditmode()`, `rewriteIds()` & `load()`. Existing `method_exists` calls are deprecated and will be removed in Pimcore 11.
- [Core] Additional interfaces for data-types introduced. Existing `method_exists` calls are deprecated and will
  be removed in Pimcore 11.
- [Glossary] `pimcoreglossary()` tag has been deprecated in favor of `pimcore_glossary` Twig filter and will be removed in Pimcore 11.
