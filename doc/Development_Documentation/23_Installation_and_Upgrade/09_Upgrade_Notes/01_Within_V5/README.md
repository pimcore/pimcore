# Upgrade Notes for Upgrades within Pimcore 5

## Version 5.7.0

- `\Pimcore\Model\WebsiteSetting` and `\Pimcore\Model\Property` properties are now `protected` instead of `public`
- [Ecommerce] MySql Product List in variant mode `VARIANT_MODE_INCLUDE_PARENT_OBJECT` now does not return parent objects 
  if there are no variants that full fill the criteria (but parent objects would). 
- [Ecommerce] FilterTypes now have `RequestStack` as constructor param. If you have custom filter types and no autowire 
  you might need to adapt your service definition. 
- Removed method `Pimcore\Model\DataObject\getItems()`
- Constants `PIMCORE_SYMFONY_DEFAULT_BUNDLE`, `PIMCORE_SYMFONY_DEFAULT_CONTROLLER` and `PIMCORE_SYMFONY_DEFAULT_ACTION` are no longer supported.
  Also the system setting for the default controller & action are removed.
  Please use the following config instead:
    ```yaml
    pimcore:
        routing:
            defaults:
                bundle: AppBundle
                controller: Default
                action: default 
    ```  

## Version 5.6.4

- `Pimcore\Model\DataObject\Localizedfield` properties are now `protected` instead of `public` 

## Version 5.6.0
- Removed method `\Pimcore\Model\DataObject\ClassDefinition\Data::setFieldtype($fieldtype)`
- `\Pimcore\Model\Translation\Website::getByKey()` and `\Pimcore\Model\Translation\Admin::getByKey()` are not throwing an exception anymore if an item doesn't exist, instead they are returning `null`
- If a custom object data-type extends from a core data-type it has to be compatible with the new interfaces (`CustomResourcePersistingInterface`, `QueryResourcePersistenceAwareInterface` and `ResourcePersistenceAwareInterface`)

#### Data Objects: renamed relational data-types
For better understanding we've renamed all relational data-types to a more meaningful name.  
We've not just renamed them in the UI, but for consistency we've decided to rename all the files, classes and 
identifiers as well.  

**The necessary migration is performed automatically after the update, so there's no manual work necessary** 

- If you've checked in files within `var/classes` into your VCS, please update them after the upgrade. 

###### Overview of Renamings
Please note that the following PHP classes are located in the namespace `\Pimcore\Model\DataObject\ClassDefinition\Data` and 
the JS classes in `pimcore.object.tags` and `pimcore.object.classes.data`. 

| Old Name | Old PHP Class Name | Old JS Class Name | New Name | New PHP Class Name | New JS Class Name |
| ---- | ---- | ---- | ---- | ---- | ---- |  
| Href | `Href` | `href` | **Many-To-One Relation** | `ManyToOneRelation` | `manyToOneRelation` | 
| Multihref | `Multihref` | `multihref` | **Many-To-Many Relation** | `ManyToManyRelation` | `manyToManyRelation` | 
| Multihref Advanced | `MultihrefMetadata` | `multihrefMetadata` | **Advanced Many-To-Many Relation** | `AdvancedManyToManyRelation` | `advancedManyToManyRelation` | 
| Objects | `Objects` | `objects` | **Many-To-Many Object Relation** | `ManyToManyObjectRelation` | `manyToManyObjectRelation` | 
| Objects with Metadata | `ObjectsMetadata` | `objectsMetadata` | **Advanced Many-To-Many Object Relation** | `AdvancedManyToManyObjectRelation` | `advancedManyToManyObjectRelation` | 
| Objects (Non Owner) | `Nonownerobjects` | `nonownerobjects` | **Reverse Many-To-Many Object Relation** | `ReverseManyToManyObjectRelation` | `reverseManyToManyObjectRelation` | 


#### Documents: renamed relational editables
In addition to the renaming of all relational object data-types, we've also renamed the two relational editables 
for documents, namely `href` and `multihref`.

**The necessary migration is performed automatically after the update, so there's no manual work necessary** 

###### Overview of Renamings
Please note that the following PHP classes are located in the namespace `\Pimcore\Model\Document\Tag` and 
the JS classes in `pimcore.document.tags`. 

| Old Templating Helper | Old PHP Class Name | Old JS Class Name | New Templating Helper | New PHP Class Name | New JS Class Name |
| ---- | ---- | ---- | ---- | ---- | ---- |  
| `$this->href()` | `Href` | `href` | `$this->relation()` | `Relation` | `relation` | 
| `$this->multihref()` | `Multihref` | `multihref` | `$this->relations()` | `Relations` | `relations` | 

#### E-Commerce Framework - Added methods to interfaces `IOrderAgent`, `ICheckoutManager` and `ICartManager`

This only affects you, when you created custom implementations of the interfaces `IOrderAgent`, `ICheckoutManager` or
`ICartManager` and did not extend the default implementations. Otherwise no action is needed. 
- `IOrderAgent`: method `public function initPayment();` was added. 
- `ICheckoutManager`: method `public function initOrderPayment();` was added. 
- `ICartManager`: method `public function getOrCreateCartByName($name);` was added.  

## Version 5.5.4

The support for ElasticSearch version 6 has been added. 
Currently the `DefaultElasticSearch` worker / product list has a fallback to `DefaultElasticSearch5` 
(which supports ElasticSearch version 2.x to 5.x so your code should continue to work without any changes).  
For version 6 use the `DefaultElasticSearch6` worker / product list.
 
## Version 5.5.1

#### WebP Support for Thumbnails
Pimcore now delivers automatically thumbnails in WebP format when using the `Auto` configuration for the 
target format and when the client does support WebP (checking by evaluating the `Accept` request header).  
In order to ensure that WebP images are served with the right `Content-Type` by your webserver, we recommend 
to check your configuration or just add the following line to your `web/.htaccess` when using Apache.   
```
AddType image/webp .webp
```
  
If you prefer not using WebP, you can disable the support by adding the following config option: 
```yml
    assets:
        image:
            thumbnails:
                webp_auto_support: false
```

## Version 5.5.0

### Major compatibility changes
- `Document`, `Asset` and `DataObject` properties are now `protected` instead of `public`
- PDF document editable doesn't provide a Javascript viewer anymore. Now utilizing native PDF capabilities of browsers.
- Swiftmailer `mail` transport is not supported anymore, see https://github.com/swiftmailer/swiftmailer/issues/866
- Ecommerce: The fallback for old filter view scripts (website/views/scripts/...) has been removed. 
### Minor compatibility changes
- `\Pimcore\Db::set()` was removed. 
- deprecated `\Pimcore::addToGloballyProtectedItems()`
- deprecated `\Pimcore::removeFromGloballyProtectedItems()`
- Image adapter `Pimcore\Image\Adapter\ImageMagick` was removed
- REST Webservice: Inheritance is disabled by default (which was also the case in pimcore 4 not in the previous versions of pimcore 5).
See the [Webservices API](../../../24_Web_Services/README.md) for further details.


### Breaking Changes
- [Pimcore Workflow Management Reloaded](./01_Workflow_Management.md)

## Version 5.4.3
Mime types for Assets are now configured using Symfony Configurations, that means that `Pimcore\Tool\Mime::$extensionMapping` has been removed.
In order for you to still add custom mappings, create new configuration like this:

```yml
pimcore:
    mime:
        extensions:
            dwg: 'application/acad'
```

## Version 5.4.0

#### Composer based updates
Pimcore 5.4 introduces a new update experience based on Composer. 
Therefore there are some manual steps required when updating to 5.4.0 which are described in our
 [step by step guide for updates from 5.x to 5.4 or above](./01_Update_from_5.x_to_5.4_or_above.md).

##### FAQ regarding Pimcore as a Composer Dependency
###### Is there still the concept of build numbers? 
No. 

###### How can I install a non-tagged/unstable version of Pimcore? 
For updating always to the latest sourcecode state of the master branch, use the following in your `composer.json`:  
```json
"pimcore/pimcore": "dev-master"
```

For referencing a specific state of the master branch, you can append the Git commit hash to the branch, eg.: 
```json
"pimcore/pimcore": "dev-master#2734529c7f287a88fa2961fa7af8e5473da0a2a1"
```


#### Removed PrototypeJS (light) library from the Admin UI
Quite a lot of functions have a native Javascript equivalent or are covered by vanilla JS anyway.
However, there are some functions which need to be adapted or replaced when used in Bundles.
The following list should help you to locate and replace them, if available you can find the replacement in brackets:
- `Prototype.*` functions and properties
- `Enumerable.*` functions and properties
- `$A()` ( `Array.from()` )
- `$w()`
- `Object.extend()` ( `Object.assign()` )
- `Object.toQueryString()` ( `Ext.Object.toQueryString` )
- `Object.clone()` ( `Object.assign({}, object)` )
- `Object.isElement()` ( `Ext.isElement()` )
- `Object.isArray()` ( `Ext.isArray()` )
- `Object.isFunction()` ( `Ext.isFunction()` )
- `Object.isString()` ( `Ext.isString()` )
- `Object.isNumber()` ( `Ext.isNumber()` )
- `Object.isUndefined()` ( `!Ext.isDefined()` )
- `Object.toHTML()`
- `Object.isHash()`
- `Array.prototype.clear` ( `array.filter(() => false)` )
- `Array.prototype.first` ( `array[0]` )
- `Array.prototype.last` ( `array[array.length-1]` )
- `Array.prototype.compact`
- `Array.prototype.flatten` ( `array.flat()` )
- `Array.prototype.without`
- `Array.prototype.reverse` ( `array.reverse()` )
- `Array.prototype.uniq` ( `array.filter((d, i, a) => a.indexOf(d) === i)` )
- `Array.prototype.intersect`
- `Array.prototype.clone` ( `[...array]` )
- `Array.prototype.toArray`
- `Array.prototype.size` ( `array.length` )
- `Array.prototype.inspect`
- `Function.prototype.[update|merge|bindAsEventListener|curry|delay|defer|wrap|methodize]`



## Version 5.3.0

#### Build 289 (2018-07-20)
The context of the button layout component in objects changed to the edit tab.
See [Layout Components](../../../05_Objects/01_Object_Classes/03_Layout_Elements/README.md).

#### Build 286 (2018-07-19)
To the interface `Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ITracker` the two methods `getAssortmentTenants` and
`getCheckoutTenants` where added. No action is required, except you have have implemented your own trackers and did not
extend them from the abstract `Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker` class.

#### Build 285 (2018-07-18)
Asset filenames: introduced new policy, the following disallowed characters were added: `#?*:\<>|"`

#### Build 281 (2018-07-13)
The admin UI translations are now split into 2 parts,
[Essentials](https://poeditor.com/join/project/VWmZyvFVMH) and [Extended](https://poeditor.com/join/project/XliCYYgILb).
Essentials contains all translations which are needed for the most common tasks for editors, while the extended
collection contains mostly admin related translations.
This separation should make it a lot easier to start with the localization of the Pimcore UI.

#### Build 280 (2018-07-12)
Extensions: removed support for `xmlEditorFile` for legacy plugins (compatibility mode).

#### Build 277 (2018-07-09)
Admin Localization: Only languages with a [translation progress](https://poeditor.com/projects/view?order=trans_desc&id=38068) over 70% are included in the standard distribution.
For the current status this means that the following languages are no longer provided: CA, PL, ZH_Hans, ZH_Hant, SV, JA, PT, PT_BR, RU, FA, TR, UK.
Languages with a lower progress need to be installed manually.

#### Build 276 (2018-07-06)
Image Thumbnails: SVGs are no longer automatically rasterized when using only one of the following transformations: Resize, Scale By Width, Scale By Height.
To restore the previous behavior, set the "Rasterize SVGs (Imagick)" option on the relevant thumbnail configuration.

#### Build 273 (2018-07-06)
Webservices API: Support for SQL condition parameters has been removed. Use [Query Filters](../../../24_Web_Services/01_Query_Filters.md) instead.
If you still want to support such conditions, implement your own event listener as described on the same page in the `Legacy Mode`section..

#### Build 270 (2018-06-29)
Data Object Class ID's can now be manually specified (alphanumeric) and are therefore no longer auto-generated (numeric/auto-increment).
This can have unexpected side-effects under certain circumstances, see also: https://github.com/pimcore/pimcore/issues/2916

#### Build 251 (2018-06-05)
- **PHP 7.1 is required**
- CKEditor update from 4.6.2 to 4.9.2, for details see: https://ckeditor.com/cke4/release-notes

#### Build 247 (2018-06-04)
The dependency `google/apiclient` was updated from `~1` to `^2.0`. The format of the private key file has changed from P12 format to JSON.
You can generate a new key in the JSON format in the credentials section of your Google Developer Console.

If you have used this library in your custom code, please update it accordingly.

#### Build 242 (2018-05-29)
The look & feel of the areablock toolbar and inline controls have changed.
The config option `areablock_toolbar` on areablocks has now [less flags](https://github.com/pimcore/pimcore/blob/0e5d8de0c3ac0829d4e85b6360b9dc409b45d108/pimcore/models/Document/Tag/Areablock.php#L264) to customize the toolbar and
a the new option `controlsAlign` was introduced.

#### Build 267 (2018-06-22)
To the PaymentProvider* object bricks the (optional) input field `configurationKey` was added. Since it is optional, this is
not added to existing object bricks. If issue [#2908](https://github.com/pimcore/pimcore/issues/2908) is a problem for you,
you need to add `configurationKey` manually to your PaymentProvider object bricks.




## Version 5.2.3
#### Build 236 (2018-05-22)

Method signature of [ICart](https://github.com/pimcore/pimcore/commit/d84d3cf94223a8cf55861a0d68956df126e1b6c5#diff-3ef1dc16016857cdc833662102181630) changed:
Added `modified()` as a public method. If you have implemented your own cart and not extended `AbstractCart` or have
overwritten the `modified()` method, please check your implementation.

In Cart Items the method `setCount()` now also fires the `modified()` method of the cart. If you have custom implementations
please check if this has any effect on them.

## Version 5.2.0
#### Build 206 (2018-02-19)

The pricing manager in the Ecommerce Framework is now tenant aware, using the checkout tenant if set. To make this possible,
BC breaking changes were necessary which probably affect you if you either consume the pricing manager service directly
or define a custom price system.

##### `@pimcore_ecommerce.pricing_manager` service (`PimcoreEcommerceFrameworkExtension::SERVICE_ID_PRICING_MANAGER`)

The `PimcoreEcommerceFrameworkExtension::SERVICE_ID_PRICING_MANAGER`constant does not exist anymore, nor the pricing
manager service `@pimcore_ecommerce.pricing_manager` it referenced. If you need a pricing manager as dependency in one of
your services, please inject `@pimcore_ecommerce.locator.pricing_manager` instead, which is an instance of `IPricingManagerLocator`
and allows you to get the pricing manager for a specific or the current tenant.

This affects mainly price systems, as the default price system depends on the pricing manager. Please use autowiring
or explicitely reference the `@pimcore_ecommerce.locator.pricing_manager` service. If you implemented a custom price system
which extends `AbstractPriceSystem` please note that the constructor signature changed. Example price system definition
using autowiring:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    app.default_price_system:
        class: Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem
        arguments:
            $options:
                attribute_name: price
                price_class: Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price

    # if you don' use autowiring, make sure to inject the locator instead of the pricing manager service
    app.another_price_system:
        class: Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem
        autowire: false
        arguments:
            - '@pimcore_ecommerce.locator.pricing_manager' # <-- this argument needs to change from @pimcore_ecommerce.pricing_manager
            - '@pimcore_ecommerce.environment'
            - { attribute_name: price, price_class: Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price }
```

##### Configuration tree

The configuration is now tenant aware, following the same structure as other components (e.g. the order manager). Conditions
and actions are still global, but `enabled`, `pricing_manager_id` and `pricing_manager_options` now need to be configured
on a tenant level. New structure:

```yaml
pimcore_ecommerce_framework:
    pricing_manager:
        tenants:
            # the default tenant is mandatory and will be automatically be configured - below are default values
            default:
                enabled: true
                pricing_manager_id: Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManager
                pricing_manager_options:
                    rule_class: Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule
                    price_info_class: Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PriceInfo
                    environment_class: Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Environment

            # define another tenant
            otherPricingManager:
                pricing_manager_id: AppBundle\Ecommerce\PricingManager
                pricing_manager_options:
                    price_info_class: AppBundle\Ecommerce\PricingManager\PriceInfo

        conditions:
            Bracket: \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\Bracket
            # ...
        actions:
            ProductDiscount: \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\ProductDiscount
            # ...
```

**Note:** the configuration entries which were moved inside the tenant tree are still configurable on a global level as
it was before for backwards compatibility reasons. If a value is set globally it will be merged into every tenant and
**overwrite** the tenant value. This behaviour triggers a deprecation warning and will be removed with Pimcore 6.

#### Build 205 (2018-02-19)

The debug mode was changed from being a boolean setting to a more granular feature flag setting. If you query the debug
mode in your code, you might update the call to specify which kind of debug setting you want to query. See
[Feature Flags and Debug Mode](../../../19_Development_Tools_and_Details/03_Feature_Flags_And_Debug_Mode.md) for details.


## Version < 5.2.0
#### Build 195 (2018-02-01)

New MySQL/MariaDB requirements are introduced, ensure the following system variables are set accordingly.
```
innodb_file_format = Barracuda
innodb_large_prefix = 1
```

#### Build 188 (2018-01-26)

In a highly concurrent setup, the [**Redis Cache**](../../../19_Development_Tools_and_Details/09_Cache/README.md)
adapter can lead to inconsistencies resulting in items losing cache tags and not being cleared anymore on save. This was
fixed in the Lua version of the cache adapter and the `use_lua` option now defaults to true. Please note that Lua scripting
is not available in Redis versions prior to 2.6.0.

#### Build 183 (2018-01-23)

The `pimcore:cache:clear` command semantics for the `-o` and `-a` option changed to follow option semantics as in other
commands. Instead of `-a=1`, `-o=1`, now just pass `-a` and `-o`. The tags option now accepts multiple options, so you can
use `-t foo -t bar` instead of `-t foo,bar` (old syntax still works).

#### Build 181 (2018-01-22)

The signature of `Pimcore\Model\DataObject\AbstractObject` changed. It received an `$params = []` parameter to make saving notes for supported objects easier. This may lead to problems if you extend/overwrite this function though. Note that the issue of saving notes for supported objects is solved by a different approach (using func_get_arg(0) instead of changing the signature) on build >= 185. Due to that the parameter `$params = []` is removed in build 185

#### Build 173 (2018-01-09)

The Google Analytics and Google Tag Manager code generation was refactored to use the same extendable block logic as the
Matomo integration. If you have any custom code logic involving the `Pimcore\Google\Analytics` class or use a custom tracker
with the Ecommerce Framework tracking implementation, please note the following:

* The static calls to `Pimcore\Google\Analytics` still work, but are discouraged. Please use `Pimcore\Analytics\Google\Tracker`
  directly instead.
* The Google trackers in the Ecommerce Framework now have a dependency on the new Tracker service. To make the change more
  backwards compatible, the dependency is injected via a dedicated setter method which is marked as `@required` to support
  autowiring. If you have a custom tracker implementation inheriting from the core Google ones and don't use autowiring
  please update your service definitions to add a call to `setTracker()`.


#### Build 169 (2018-01-05)

The install SQL dump shipped with the Pimcore 5.1 release was missing one column change in the `documents_page` table. The
update script changes this as expected, but if you did a fresh install of Pimcore 5.1, please run the following SQL query:

```sql
ALTER TABLE `documents_page` CHANGE `personas` `targetGroupIds` VARCHAR(255);
```

## Pimcore 5.1

**Symfony 3.4**: Pimcore 5.1 uses Symfony 3.4 as dependency. Please have a look at [Symfony's release notes](https://symfony.com/blog/symfony-3-4-0-released)
for 3.4 and fix any potential Symfony related deprecations in your code before upgrading. After upgrading please make sure
to fix any new deprecations marked by Symfony 3.4 to be ready for future Symfony versions.

If you installed Pimcore 5 before the final 5.0.0 release you still might have the following config section in your `composer.json`:


```json
{
    "config": {
        "platform": {
            "php": "7.0"
        }
    }
}
```

This section needs to be removed before upgrading as otherwise composer will be unable to install Symfony 3.4.

**Admin Controllers**: As preparation for Symfony 4 we had to refactor some of our implementations to make sure they will
be compatible with Symfony 4. Unfortunately this also concerns 3 methods which the `AdminController` overwrites from the
standard symfony controller: `json()`, `getUser()` on the `AdminController` and `createNotFoundException` on the
`AbstractRestController`.

If you implement any controller which inherits from Pimcore's `AdminController` please make sure to update the following
method calls to ensure the same functionality:

Controllers inheriting from `Pimcore\Bundle\AdminBundle\Controller\AdminController`:

| Old call           | New call                | Note                                                                                                                                                                                                                   |
|--------------------|-------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$this->json()`    | `$this->adminJson()`    | You can still use `json()` as it is a standard Symfony controller method, but please be aware that it uses the Symfony Serializer instead of Pimcore's admin serializer and the results may differ from `adminJson()`. |
| `$this->getUser()` | `$this->getAdminUser()` | You can still use `getUser()`, but please be aware that the returned user is a `Pimcore\Bundle\AdminBundle\Security\User\User` and not the `Pimcore\Model\User` which is returned in `getAdminUser()`.                 |

Controllers inheriting from `Pimcore\Bundle\AdminBundle\Controller\Rest\AbstractRestController`:

| Old call                           | New call                                    | Note |
|------------------------------------|---------------------------------------------|------|
| `$this->createNotFoundException()` | `$this-> createNotFoundResponseException()` |      |

**Extensions:** The default priority of bundles enabled via extension manager was changed from `0` to `10` to make sure
those bundles are loaded before the `AppBundle` is loaded. Please make sure this works for your application
and set a manual priority otherwise. See https://github.com/pimcore/pimcore/pull/2328 for details.

**E-Commerce:** Due to performance reasons, we needed to change the way how index service attributes are handled. They are
now built at runtime instead of handling each attribute as service. To achieve this, config service definition now relies
on the method `setAttributeFactory` being called creating a service instance. If your config definition uses `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\AbstractConfig`
as parent definition you should be set, otherwise you'll need to make sure your service definition includes the method
call:

```yaml
services:
    AppBundle\IndexService\Config\CustomConfig:
        # [...]
        calls:
            - [setAttributeFactory, ['@Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\AttributeFactory']]
```

**Targeting/Personalization**: The targeting/personalization engine was completely revamped and now uses server side targeting
instead of the frontend targeting which was used in earlier versions. The new targeting integration is **incompatible**
with the previous one and will break existing targeting setups. **NOTE**: the targeting feature is **experimental**
and may be subject to change in later versions.

If you are already using targeting, be aware that you'll need to re-create all of your targeting rules from scratch based
on the new engine.

You can find updated documentation in [Targeting and Personalization](../../../18_Tools_and_Features/37_Targeting_and_Personalization)
and in [Migrating from the existing Targeting Engine](../../../18_Tools_and_Features/37_Targeting_and_Personalization/30_Migrating_from_the_existing_Targeting_Engine.md).

<div class="alert alert-danger">
Make sure to delete any old rules <strong>BEFORE</strong> running the update as otherwise your site may break.
</div>


#### Build 156 (2017-12-13)

The experimental `GridColumnConfig` feature was revamped to register and build its operators via DI instead of predefined
namespaces (see [PR#2333](https://github.com/pimcore/pimcore/pull/2333)). If you already implemented custom operators
please make sure you update them to the new structure.

#### Build 149 (2017-11-14)

The Matomo integration which was recently added was refactored to always use a full URI including the protocol for the Matomo
URL configuration setting. Please update your settings to include the protocol as otherwise the Matomo tracking will be
disabled.

Before:

* `matomo.example.com`
* `analytics.example.com/matomo`

Now:

* `https://matomo.example.com`
* `https://analytics.example.com/matomo`

#### Build 148 (2017-11-13)

The ecommerce order manager now has a dependency on the `Pimcore\Model\Factory`. To make the class backwards compatible,
the dependency is injected via a dedicated setter which is marked as `@required` to support autowiring. If you don't extend
the default order manager you don't need to do anything as the core order manager is already properly configured. If you
have custom order manager service definitions, please make sure the definition is autowired or has an explicit setter
call (see examples below).

```yaml
services:
    # enable autowiring either on a service level or as _defaults
    # for the whole file
    AppBundle\Ecommerce\Order\OrderManager:
        autowire: true
        arguments:
            - '@pimcore_ecommerce.environment'
            - '@?'
            - '@pimcore_ecommerce.voucher_service'
            - []


    # or add a dedicated setter call
    AppBundle\Ecommerce\Order\OrderManager:
        arguments:
            - '@pimcore_ecommerce.environment'
            - '@?'
            - '@pimcore_ecommerce.voucher_service'
            - []
        calls:
            - [setModelFactory, ['@Pimcore\Model\Factory']]
```
#### Build 143
The constructor signature of ApplicationLoggerDb changed from 
`public function __construct($level = 'debug', $bubble = true)` to `public function __construct(Db\Connection $db, $level = 'debug', $bubble = true)`

Adopt all calls of `new ApplicationLoggerDb('INFO')` to `new ApplicationLoggerDb(\Pimcore\Db::get(), 'INFO')` or get the logger directly from the container: `\Pimcore::getContainer()->get('pimcore.app_logger')`

#### Build 134 (2017-10-03)

This build changes the default setting for the legacy name mapping in the ecommerce framework (see [LegacyClassMappingTool](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/Legacy/LegacyClassMappingTool.php))
to false, disabling legacy class mapping for new projects. When you're updating from a previous version and the ecommerce
framework is enabled, the updater will automatically enable the class mapping for you by creating a config file in `app/config/local`.
If you're starting fresh, you'll need to enable the mapping manually if needed by setting the following config value:

```yaml
pimcore_ecommerce_framework:
    use_legacy_class_mapping: true
```

#### Build 100 (2017-08-30)

##### Objects were renamed to Data Objects
The introduction of object type hints in PHP 7.2 forced us to rename several namespaces to be compliant with the
[PHP 7 reserved words](http://php.net/manual/de/reserved.other-reserved-words.php).
- Namespace `Pimcore\Model\Object` was renamed to `Pimcore\Model\DataObject`
    - PHP classes of Data Objects are now also in the format eg. `Pimcore\Model\DataObject\News`
- Several other internal classes were renamed or moved as well
    - `Pimcore\Event\Object*` to `Pimcore\Event\DataObject*`
    - `Pimcore\Model\User\Workspace\Object` to `Pimcore\Model\User\Workspace\DataObject`
- [Object Placeholders](../../../19_Development_Tools_and_Details/23_Placeholders/01_Object_Placeholder.md) syntax changed to `%DataObject()`
- There's a compatibility autoloader which enables you to still use the former namespace (< PHP 7.2), but you should migrate asap. to the new namespaces.
- After the update please consider the following:
    - If you're using custom [class overrides](../../../20_Extending_Pimcore/03_Overriding_Models.md) in your `app/config/config.yml`, please adapt them using the new namespace.
    - If you're using event listeners on object events, please rename them as well to `pimcore.dataobject.*`
    - Your code should continue to work as before, due to the compatibility autoloader, which is creating class aliases on the fly
    - Update your `.gitignore` to exclude `/var/classes/DataObject` instead of `/var/classes/Object`
- If the update fails, please try the following:
    - fix the above configuration changes, mainly the class overrides and potentially other relevant configurations
    - `composer dump-autoload`
    - `./bin/console cache:clear --no-warmup`
    - run the [migration script](https://gist.github.com/brusch/03521a225cffee4baa8f3565342252d4) manually on cli


#### Build 97 (2017-08-24)

This build re-adds support to access website config settings from controllers and views, but in a slightly different way
than in Pimcore 4. See [Website Settings](../../../18_Tools_and_Features/27_Website_Settings.md) for details.

#### Build 96 (2017-08-22)

This build adds support for migrations in bundle installers (see [Installers](../../../20_Extending_Pimcore/13_Bundle_Developers_Guide/05_Pimcore_Bundles/01_Installers.md)).
With this change, extension manager commands can now also be executed as CLI commands and installers use an `OutputWriter`
object to return information to the extension manager or to CLI scripts. As this `OutputWriter` is initialized in `AbstractInstaller`s
constructor, please update your custom installers to call the parent constructor.

##### Upgrade errors

If you get an error like the following while upgrading to build 96, please run `composer update` manually on the command
line and continue to upgrade:

```
Class Doctrine\Bundle\MigrationsBundle\Command\MigrationsExecuteDoctrineCommand not found in ExecuteCommand.php (line 8)
```

You can avoid this problem by installing the `doctrine/doctrine-migrations-bundle` package *BEFORE* running the upgrade:

```bash
$ composer require doctrine/doctrine-migrations-bundle "^1.2"
```

#### Build 86 (2017-08-02)

E-Commerce Framework configuration was moved to a Symfony Config. For details see
[Config Signature changes](../03_Ecommerce_Framework/02_Ecommerce_Framework_Config_Signature_Changes.md)


#### Build 85 (2017-08-01)

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

##### Session related BC breaks

The admin session ID can't be injected via GET parameter anymore. This was possbile in previous Pimcore versions to support
Flash based file uploaders but was obsolete.


#### Build 60 (2017-05-31)

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

See the [navigation documentation](../../../03_Documents/03_Navigation.md) for details.

#### Build 54 (2017-05-16)

Added new `nested` naming scheme for document editables, which allows reliable copy/paste in nested block elements. Pimcore
defaults to the new naming scheme for fresh installations, but configures updated installations to use the `legacy` scheme.

To configure Pimcore to use the `legacy` naming scheme strategy, set the following config:

```yaml
pimcore:
    documents:
        editables:
            naming_strategy: legacy
```

See [Editable Naming Strategies](../../../03_Documents/13_Editable_Naming_Strategies.md) for information how to migrate to
the `nested` naming strategy.
