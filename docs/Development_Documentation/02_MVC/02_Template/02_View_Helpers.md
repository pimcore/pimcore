# View Helpers

## Introduction

View Helpers are methods that offer special functionality to increase usability of views. 
This concept is a Zend Framework concept and you can use all the ```\Zend_View``` helpers which are shipped with ZF. 
There are some really cool helpers which are really useful when used in combination with Pimcore.
For the most important see following table. 

| Method          | Reference                                     | Description                                                                                                                          |
|-----------------|-----------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------|
| ```action```    | ```\\Zend_View_Helper_Action::action```       | [Action helper description](https://framework.zend.com/manual/1.10/en/zend.view.helpers.html#zend.view.helpers.initial.action)       |
| ```headMeta```  | ```\\Zend_View_Helper_HeadMeta::headMeta```   | [HeadMeta helper description](https://framework.zend.com/manual/1.10/en/zend.view.helpers.html#zend.view.helpers.initial.headmeta)   |
| ```headTitle``` | ```\\Zend_View_Helper_HeadTitle::headTitle``` | [HeadTitle helper description](https://framework.zend.com/manual/1.10/en/zend.view.helpers.html#zend.view.helpers.initial.headtitle) |


In addition to the ZF standard view helpers, Pimcore adds powerful additional view helpers. 


## Pimcore View Helpers

The Pimcore implementation of ```\Zend_View``` namely ```Pimcore\View``` offers addtional view helpers to increase the usability even more:

| Method                                   | Reference                                             | Description                                                          |
|------------------------------------------|-------------------------------------------------------|----------------------------------------------------------------------|
| [```inc```](#inc)                        | ```\\Pimcore\\View::inc```                            | Use this function to directly include a document.                    |
| [```template```](#template)              | ```\\Pimcore\\View::template```                       | Use this method to include a template                                |
| [```getParam```](#getParam)              | ```\\Pimcore\\View::getParam```                       | Get's a parameter (get, post, .... ), it's an equivalent to $this->getParam() in the controller action.                               |
| [```cache```](#cache)                    | ```\\Pimcore\\View\\Helper\\Cache::cache```           | Cache implementation in temaplates.                                  |
| [```device```](#device)                  | ```\\Pimcore\\View\\Helper\\Device::device```         | Helps implementing adaptive designs.                                  |
| [```glossary```](#glossary)              | ```\\Pimcore\\View\\Helper\\Glossary::glossary```     | [Glossary documentation](../../08_Tools_and_Features/21_Glossary.md) |
| [```translate```](#translate)            | ```\\Pimcore\\View::t```                              | i18n / shared translations                                                  |
| [```translateAdmin```](#translateAdmin)  | ```\\Pimcore\\View::ts```                             | i18n / admin translations                                                  |
| [```headLink```](#headLink)              | ```\\Pimcore\\View\\Helper\\HeadLink```               | Should be used to add stylesheets in your templates.                 |


You also can create some [new custom helpers](https://framework.zend.com/manual/1.10/en/zend.view.helpers.html#zend.view.helpers.custom) to make your life easier.

### inc 
Use ```$this->inc()``` to include Pimcore Documents inside of 
views, for example a snippet. This is useful for footers, headers, navigations, sidebars, ...

```$this->inc(mixed $document, [array $params], [$cacheEnabled = true])```

| Name                | Description  |
|---------------------|--------------|
| ```$document```     | Document to include, can be either an ID, a path or even the Document object itself |
| ```$params```       | Is optional and should be an array with key value pairs like in ```$this->action()``` from ZF. |
| ```$enabledCache``` | Is true by default, set it to false to disable the cache. Hashing is done across source and parameters to ensure a consistent result. |
 
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
 
  
<!-- without cache -->
<?= $this->inc(123, null, false) ?>
```

### template
This method is designed to include an other template directly, without calling an action. 

```$this->template(string $path, [array $params = []], [bool $resetPassedParams = false], [bool $capture = false])```

| Name                | Description  |
|---------------------|--------------|
| ```$path```              | Path of template to include. |
| ```$params```            | Additional params to include. |
| ```$resetPassedParams``` | Resets and removes additional params from view after inclusion of given template is finished. If you use this extensively on the same view object, the parameter $resetPassedParams will come very handy.  |
| ```$capture```           | Returns rendered template instead of adding it to output buffer. |

```php
<?php $this->template("includes/footer.php") ?>
 
<!-- with parameters -->
<?php $this->template("includes/somthingelse.php", [
    "param1" => "value1"
]) ?>
```

Parameters in the included template are then accessible through `` $this->paramName``` i.e. from the example above. 
```php
<?= $this->param1 ?>
```

### getParam
Get's a parameter (get, post, .... ), it's an equivalent to $this->getParam() in the controller action.

```$this->getParam(string $key, [mixed $default = null])```

| Name                | Description  |
|---------------------|--------------|
| ```$key```              | Key of param |
| ```$default```            | Default value if key not set |

```php

<?= $this->getParam("myParam"); ?>

```


### cache
This is an implementation of a direct in-template cache. You can use this to cache some parts directly in the template, 
independent of the other global definable caching functionality. This can be useful for templates which need a lot 
of calculation or require a huge amount of objects.

```$this->cache(string $name, [int $lifetime = null], [bool $force = false])```

| Name                | Description  |
|---------------------|--------------|
| ```$name```         | Name of cache item |
| ```$lifetime```     | Lifetime is expected in seconds. If you define no lifetime the behavior is like the output cache, so if you make any change in Pimcore, the cache will be flushed. When specifying a lifetime this is independent from changes in the CMS. |
| ```$force```        | Force caching, even when Request is done within Pimcore Admin interface |


```php
... 
<?php if(!$this->cache("test_cache_key", 60)->start()) { ?>
    <h1>This is some cached microtime</h1>
    <?= microtime() ?>
    <?php $this->cache("test_cache_key")->end(); ?>
<?php } ?>
...
```

### device
This helper makes it easy to implement "Adaptive Design" in Pimcore. 

```$this->device([string $default = null])```

| Name                | Description  |
|---------------------|--------------|
| ```$default```         | Default if no device can be detected |


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
For details also see [Adaptive Design](../../09_Development_Tools_and_Details/21_Adaptive_Design.md).


### glossary

For details please see [glossary documentation](../../08_Tools_and_Features/21_Glossary.md).


```php
<section class="area-wysiwyg">

    <?php // start filtering content with Glossary feature ?>
    <?php $this->glossary()->start(); ?>
        <?php echo $this->wysiwyg("content"); ?>
    <?php $this->glossary()->stop(); ?>

</section>
```


### translate
View helper for getting translation from shared translations. For details also see [Shared Translations](../../06_Multi_Language_i18n/04_Shared_Translations.md).

```$this->t(string $key = "")```
```$this->translate(string $key = "")```

| Name                | Description  |
|---------------------|--------------|
| ```$key```         | Key of translation |


```php
<a href="/"><?= $this->translate("Home"); ?></a>
```



### translateAdmin
View helper for getting translation from admin translations. For details also see [Admin Translations](../../06_Multi_Language_i18n/06_Admin_Translations.md).

```$this->ts(string $key = "")```
```$this->translateAdmin(string $key = "")```

| Name                | Description  |
|---------------------|--------------|
| ```$key```         | Key of translation |


```php
<a href="/"><?= $this->translateAdmin("Home"); ?></a>
```
