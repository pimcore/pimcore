## General

Pimcore uses ```\Zend_View``` as its template engine, and the standard template language is PHP.

You can find your templates in: ```/website/views/scripts```

### Layouts:

Layouts in Pimcore you should put to the ```/website/views/layouts``` directory.
Enable layout in your controller action:

```php
$this->enableLayout();
```

If you wanted to change default layout, you would add line presented below to your template file:

```php
<?php 
//change layout to catalog.php
$this->layout()->setLayout('catalog'); 
?>

<div id="product">

...

```

## Helpers (Available view methods)

The Pimcore implementation of ```\Zend_View``` namely ```Pimcore\View``` offers special methods to increase the usability:

| Method          | Reference                                       | Description                                                       |
|-----------------|-------------------------------------------------|-------------------------------------------------------------------|
| inc             | \\Pimcore\\View::inc                            | Use this function to directly include a document.                 |
| template        | \\Pimcore\\View::template                       | Use this method to include a template                             |
| cache           | \\Pimcore\\View\\Helper\\Cache::cache           | Cache implementation in temaplates.                               |
| translate       | \\Pimcore\\View::t                              | i18n / translations                                               |
| glossary        | \\Pimcore\\View\\Helper\\Glossary::glossary     | [Glossary documentation](../08_Tools_and_Features/21_Glossary.md) |
| headLink        | \\Pimcore\\View\\Helper\\HeadLink               | Should be used to add stylesheets in your templates.              |

Additionally you can use the ```\Zend_View``` helpers which are shipped with ZF. There are some really cool helpers which are really useful when used in combination with Pimcore.

| Method    | Reference                               | Description                                                                                                                          |
|-----------|-----------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------|
| action    | \\Zend_View_Helper_Action::action       | [Action helper description](https://framework.zend.com/manual/1.10/en/zend.view.helpers.html#zend.view.helpers.initial.action)       |
| headMeta  | \\Zend_View_Helper_HeadMeta::headMeta   | [HeadMeta helper description](https://framework.zend.com/manual/1.10/en/zend.view.helpers.html#zend.view.helpers.initial.headmeta)   |
| headTitle | \\Zend_View_Helper_HeadTitle::headTitle | [HeadTitle helper description](https://framework.zend.com/manual/1.10/en/zend.view.helpers.html#zend.view.helpers.initial.headtitle) |

You can create some [new helpers](https://framework.zend.com/manual/1.10/en/zend.view.helpers.html#zend.view.helpers.custom) to make your life easier.

### Using helper method in a template

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

## Thumbnails

Pimcore offers an advanced thumbnail-service also called **image-pipeline**. 
It allows you to transform images in unlimited steps to the expected result. 

[comment]: #TODOinlineimgs

<div class="inline-imgs">

You can configure them in ![Settings](../img/Icon_settings.png) **Settings -> Thumbnails**.

</div>

To get the complete information about Thumbnails, please visit a dedicated part in the documentation [Working with thumbnails](../04_Assets/03_Working_with_Thumbnails.md)

### Usage example

```php
<?php // Use with the image tag in documents ?>
<div>
    <p>
        <?= $this->image("image", ["thumbnail" => "myThumbnail"]) ?>
    </p>
</div>
 
 
<?php // Use directly on the asset object ?>
<?php
    $asset = Asset::getByPath("/path/to/image.jpg");
    echo $asset->getThumbnail("myThumbnail")->getHTML();
?>
 
<?php // Use without preconfigured thumbnail ?>
<?= $this->image("image", [
    "thumbnail" => [
        "width" => 500,
        "height" => 0,
        "aspectratio" => true,
        "interlace" => true,
        "quality" => 95,
        "format" => "PNG"
    ]
]) ?>
 
<?php // Use from an object-field ?>
<?php if ($this->myObject->getMyImage() instanceof Asset\Image) { ?>
    <img src="<?= $this->myObject->getMyImage()->getThumbnail("myThumbnail"); ?>" />
<?php } ?>
 
// where "myThumbnail" is the name of the thumbnail configuration in settings -> thumbnails
 
 
 
<?php // Use from an object-field with dynamic configuration ?><?php if ($this->myObject->getMyImage() instanceof Asset\Image) { ?>
    <img src="<?= $this->myObject->getMyImage()->getThumbnail(["width" => 220, "format" => "jpeg"]); ?>" />
<?php } ?>
 
 
 
<?php // Use directly on the asset object using dynamic configuration ?>
<?php
 
$asset = Asset::getByPath("/path/to/image.jpg");
echo $asset->getThumbnail(["width" => 500, "format" => "png"])->getHTML();
 
?>
```

