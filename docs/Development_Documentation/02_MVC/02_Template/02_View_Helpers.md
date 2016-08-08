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
This method is designed to include an other template directly, without calling an action. If you use this extensively 
on the same view object, the parameter $resetPassedParams will come very handy.

```$this->template(string $path, [array $params = []], [bool $resetPassedParams = false], [bool $capture = false])```

| Name                | Description  |
|---------------------|--------------|
| ```$path```              | Path of template to include. |
| ```$params```            | Additional params to include. |
| ```$resetPassedParams``` | Resets and removes additional params from view after inclusion of file is finished.  |
| ```$capture```           | ?!  |
 

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

**cache** helper usage:

```php
... 
<div id="product-container">
    <?php
    /** @var \Pimcore\View\Helper\CacheController $cache */
    $cache = $this->cache('product_content');
    if(! $cache->start()):
        //if content is not loaded from cache
    ?>
    <p>
        Product name: <?php echo $product->name; ?>
        SKU: <?php echo $product->sku; ?>
    </p>
    <?php
        $cache->stop();
    endif;
    ?>
</div>
...
```

**translate** helper usage:

```php
<a href="/"><?= $this->translate("Home"); ?></a>
```

**glossary** helper usage:

```php
<section class="area-wysiwyg">

    <?php // start filtering content with Glossary feature ?>
    <?php $this->glossary()->start(); ?>
        <?php echo $this->wysiwyg("content"); ?>
    <?php $this->glossary()->stop(); ?>

</section>
```

**headLink** helper usage:

```php
<head>
    ...

    <?php $this->headLink()->appendStylesheet('/website/static/css/global.css'); ?>

    ...
</head>
```
