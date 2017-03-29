# Templating Helpers

## Introduction

Template Helpers are methods that offer special functionality to increase usability of view scripts. 
This is a concept of the [Symfony Templating Component](http://symfony.com/doc/current/components/templating.html) 
and you can of course use all of the built-in functionalities of this component.  

Following an overview of some helpers provided by the Symfony Templating Component:   

| Method         | 
|----------------|
| `escape()`     | 
| `extend()`     | 
| `actions()`    | 
| `assets()`     | 
| `code()`       | 
| `form()`       | 
| `request()`    | 
| `router()`     | 
| `session()`    | 
| `stopwatch()`  | 
| `translator()` | 
| `url()`        | 
| `path()`       | 

For more information please have a look into the docs of the [Symfony PHP Templating Compontent](http://symfony.com/doc/current/templating/PHP.html). 
  
In addition to the Symfony standard templating helpers, Pimcore adds some additional powerful helpers. 
  
## Pimcore Templating Helpers

All helpers are described below in detail, the following tables give just a short overview of all available helpers.

| Method                                   | Description                                             |
|------------------------------------------|---------------------------------------------------------|
| `action()`       | Call an  arbitrary action, this is a shorthand for Symfony's `actions()` helper |
| `cache()`        | Simple in-template caching functionality                                        |
| `device()`       | Helps implementing adaptive designs.                                            |
| `getAllParams()` | Returns all parameters on the request object                                    |
| `getParam()`     | Returns a specific parameter from the request                                   |
| `glossary()`     | Helper to control the glossary engine                                           |
| `placeholder()`  | Adding and embedding custom placeholders, e.g. for special header tags, etc.    |
| `headLink()`     | Embeding / managing referenced stylesheets (alternative to `assets()`)          |
| `headMeta()`     | Managing your <meta> elements in your HTML document                             |
| `headScript()`   | Managing your <scripts> elements                                                |
| `headStyle()`    | Managing inline styles (pendant to `headLink()` for inline styles)              |
| `headTitle()`    | Create and store the HTML document's `<title>` for later retrieval and output   |
| `inc()`          | Use this function to directly include a Pimcore document.                       |
| `inlineScript()` | Managing inline scripts (pendant to `headScript()` for inline scripts)          |
| `navigation()`   | Embed and build navigations based on the document structure                     |
| `pimcoreUrl()`   | An alternative to `url()` and `path()` with the building behavior of Pimcore 4  |
| `template()`     | Directly include a template                                                     |
| `translate()`    | I18n / Shared Translations                                                      |


You can also create your own custom templating helpers to make certain functionalities available to your views.  
Here you can find an example how to [create](https://github.com/pimcore/pimcore/blob/master/install-profiles/demo-cms/src/AppBundle/Templating/Example.php) 
and [register](https://github.com/pimcore/pimcore/blob/master/app/config/services.yml) your own templating helper. 

### `$this->action()`
This helper is a shorthand of Symfony's `actions` helper. 

`$this->action(string $action, string $controller, string $bundle, array $params = [])`
   
| Name                | Description  |
|---------------------|--------------|
| `$action`     | Name of the action (eg. `foo`) |
| `$controller` | Name of the controller (eg. `Bar`) |
| `$bundle`     | Optional name of the bundle where the controller/action lives |
| `$params`     | Optional params added to the request object for the action |

   
##### Example
```php
<section id="foo-bar">
    <?= $this->action("foo", "Bar", null, ["awesome" => "value"]) ?>
</section>
```
   
    
### `$this->cache()`
This is an implementation of an in-template cache. You can use this to cache some parts directly in the template, 
independent of the other global definable caching functionality. This can be useful for templates which need a lot 
of calculation or require a huge amount of objects (like navigations, ...).

`$this->cache(string $name, [int $lifetime = null], [bool $force = false])`

| Name                | Description  |
|---------------------|--------------|
| `$name`         | Name of cache item |
| `$lifetime`     | Lifetime in seconds. If you define no lifetime the behavior is like the output cache, so if you make any change in Pimcore, the cache will be flushed. When specifying a lifetime this is independent from changes in the CMS. |
| `$force`        | Force caching, even when request is done within Pimcore admin interface |

##### Example
```php
<?php if(!$this->cache("test_cache_key", 60)->start()) { ?>
    <h1>This is some cached microtime</h1>
    <?= microtime() ?>
    <?php $this->cache("test_cache_key")->end(); ?>
<?php } ?>
```


### `$this->device()`
This helper makes it easy to implement "Adaptive Design" in Pimcore. 

`$this->device([string $default = null])`

| Name                | Description  |
|---------------------|--------------|
| `$default`         | Default if no device can be detected |

##### Example
```php
<?php
    $device = $this->device("phone"); // first argument is the default setting
?>
 
<?php if($device->isPhone()) { ?>
    This is my phone content
<?php } else if($device->isTablet()) { ?>
    This text is shown on a tablet
<?php } else if($device->isDesktop()) { ?>
    This is for default desktop Browser
<?php } ?>
 
<?php if($this->device()->isDesktop()) { ?>
    Here is some desktop specific content
<?php } ?>
```
For details also see [Adaptive Design](../../09_Development_Tools_and_Details/21_Adaptive_Design_Helper.md).


### `$this->getAllParams()`
Returns all parameters as an array on the request object.   
See also `$this->getParam()`. 


### `$this->getParam()`
Returns a parameter from the request object (get, post, .... ), it's an equivalent to `$request->get()` in the controller action.

`$this->getParam(string $key, [mixed $default = null])`

| Name                | Description  |
|---------------------|--------------|
| `$key`              | Key of param |
| `$default`            | Default value if key not set |

##### Example
```php
<?= $this->getParam("myParam"); ?>
```


### `$this->glossary()`

For details please see [Glossary Documentation](../../08_Tools_and_Features/21_Glossary.md).

##### Example
```php
<section class="area-wysiwyg">

    <?php // start filtering content with Glossary feature ?>
    <?php $this->glossary()->start(); ?>
        <?= $this->wysiwyg("content"); ?>
    <?php $this->glossary()->stop(); ?>

</section>
```

### `$this->placeholder()` 
See [Placeholder Template Helper](00_Placeholder.md)

### `$this->headLink()` 
See [HeadLink Template Helper](01_HeadLink.md)

### `$this->headMeta()` 
See [HeadMeta Template Helper](02_HeadMeta.md)

### `$this->headScript()` 
See [HeadScript Template Helper](03_HeadScript.md)

### `$this->headStyle()` 
See [HeadStyle Template Helper](04_HeadStyle.md)

### `$this->headTitle()` 
See [HeadTitle Template Helper](05_HeadTitle.md)


### `$this->inc()` 
Use `$this->inc()` to include documents (eg. snippets) within views. 
This is especially useful for footers, headers, navigations, sidebars, teasers, ...

`$this->inc(mixed $document, [array $params], [$cacheEnabled = true])`

| Name                | Description  |
|---------------------|--------------|
| `$document`     | Document to include, can be either an ID, a path or even the Document object itself |
| `$params`       | Is optional and should be an array with key value pairs like in `$this->action()` from ZF. |
| `$enabledCache` | Is true by default, set it to false to disable the cache. Hashing is done across source and parameters to ensure a consistent result. |
 
 ##### Example
```php
use Pimcore\Model\Document;
  
<!-- include path -->
<?= $this->inc("/shared/boxes/buttons") ?>
 
<!-- include ID -->
<?= $this->inc(256) ?>
 
<!-- include object -->
<?php
 
$doc = Document::getById(477);
echo $this->inc($doc, [
    "param1" => "value1"
]);
?> 
  
<!-- disable caching -->
<?= $this->inc(123, null, false) ?>
```


### `$this->inlineScript()` 
See [InlineScript Template Helper](06_InlineScript.md)

### `$this->navigation()` 
See [Navigation](../../../03_Documents/03_Navigation.md)

### `$this->pimcoreUrl()` 
An alternative to `url()` and `path()` with the building behavior of Pimcore 4. 


### `$this->template()`
This method is designed to include a different template directly, without calling an action. 
Basically it's just using PHP's `include()` in the background, but you don't have to care about path issues. 

`$this->template(string $path, [array $params = []], [bool $resetPassedParams = false], [bool $capture = false])`

| Name                | Description  |
|---------------------|--------------|
| `$path`              | Path of template to include. Relative to `/website/views/scripts` |
| `$params`            | Additional params to include. |
| `$resetPassedParams` | Resets and removes additional params from view after inclusion of given template is finished. If you use this extensively on the same view object, the parameter $resetPassedParams will come very handy.  |
| `$capture`           | Returns rendered template instead of adding it to output buffer. |

##### Example
```php
<?php $this->template("includes/footer.php") ?>
 
<!-- with parameters -->
<?php $this->template("includes/somthingelse.php", [
    "param1" => "value1"
]) ?>
```

Parameters in the included template are then accessible through `$this->paramName` i.e. from the example above. 

##### Example
```php
<?= $this->param1 ?>
```



### `$this->translate()`
View helper for getting translation from shared translations. For details also see [Shared Translations](../../06_Multi_Language_i18n/04_Shared_Translations.md).

`$this->t(string $key = "")`
`$this->translate(string $key = "")`

| Name                | Description  |
|---------------------|--------------|
| `$key`         | Key of translation |


##### Example
```php
<a href="/"><?= $this->translate("Home"); ?></a>
```

