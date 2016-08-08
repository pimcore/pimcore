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

| Method                | Reference                                             | Description                                                          |
|-----------------------|-------------------------------------------------------|----------------------------------------------------------------------|
| [```inc```](#ink)             | ```\\Pimcore\\View::inc```                            | Use this function to directly include a document.                    |
| ```template```        | ```\\Pimcore\\View::template```                       | Use this method to include a template                                |
| ```getParam```        | ```\\Pimcore\\View::getParam```                       | Get's a parameter (get, post, .... ), it's an equivalent to $this->getParam() in the controller action.                               |
| ```cache```           | ```\\Pimcore\\View\\Helper\\Cache::cache```           | Cache implementation in temaplates.                                  |
| ```device```           | ```\\Pimcore\\View\\Helper\\Device::device```           | Helps implementing adaptive designs.                                  |
| ```glossary```        | ```\\Pimcore\\View\\Helper\\Glossary::glossary```     | [Glossary documentation](../../08_Tools_and_Features/21_Glossary.md) |
| ```translate```       | ```\\Pimcore\\View::t```                              | i18n / shared translations                                                  |
| ```translateAdmin```       | ```\\Pimcore\\View::ts```                              | i18n / admin translations                                                  |
| ```headLink```        | ```\\Pimcore\\View\\Helper\\HeadLink```               | Should be used to add stylesheets in your templates.                 |


You also can create some [new custom helpers](https://framework.zend.com/manual/1.10/en/zend.view.helpers.html#zend.view.helpers.custom) to make your life easier.

### Using helper method in a template

#### inc 
**inc** helper usage:

```php
...
<?php //including footer to the template ?>
<div id="footer">
    echo $this->inc("/snippets/includes/footer");
</div>
...
```

**template** helper usage:

```php
...

<?php includes language.php template from: /website/views/scripts/includes/language.php ?>
<div id="lang-switcher">
<?= $this->template("/includes/language.php"); ?>
</div>

...
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
