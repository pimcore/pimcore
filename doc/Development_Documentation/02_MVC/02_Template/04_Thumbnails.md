# Pimcore Thumbnails

## Introduction
When displaying images in templates, they should be optimized (e.g. size) for the actual use case and device. 
When source images are stored as Pimcore Assets (as they should be), Pimcore can do all the optimizing work for you. 
Just use the Thumbnail functionality and let Pimcore transform the images the way you need them. 
  
To get all the information about Thumbnails, which possibilities exist and how to configure them, 
please have a look at [Working with Thumbnails](../../04_Assets/03_Working_with_Thumbnails.md/README.md). 

## Use Thumbnails in Templates

<div class="code-section">

```php
<?php 
    use Pimcore\Model\Asset;

    // Use directly on the asset object - myThumbnail is the name of the thumbnail configured in thumbnail configuration
    $asset = Asset::getByPath("/path/to/image.jpg");
    echo $asset->getThumbnail("myThumbnail")->getHtml();
    
    // Use directly on the asset object using dynamic configuration 
    $asset = Asset::getByPath("/path/to/image.jpg");
    echo $asset->getThumbnail(["width" => 500, "format" => "png"])->getHtml();
?>
 
 

<?php // Use with the image editable in documents ?>
<div>
    <p>
        <?= $this->image("image", ["thumbnail" => "myThumbnail"]) ?>
    </p>
</div>
 
<?php // Use with the image editable in documents using dynamic configuration ?>
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
 
<?php // Use from an object-field using dynamic configuration ?>
<?php if ($this->myObject->getMyImage() instanceof Asset\Image) { ?>
    <img src="<?= $this->myObject->getMyImage()->getThumbnail(["width" => 220, "format" => "jpeg"]); ?>" />
<?php } ?>
```

```twig
{# Use directly on the asset object - myThumbnail is the name of the thumbnail configured in thumbnail configuration #}
{% set asset = asset('/path/to/image.jpg') %}
{{ asset.getThumbnail('myThumbnail').getHtml() | raw }}

{# Use directly on the asset object using dynamic configuration #}
{% set asset = asset('/path/to/image.jpg') %}
{{ asset.getThumbnail({
    width: 500,
    format: 'png'
}).getHtml() | raw }}

{# Use with the image editable in documents #}
<div>
    <p> 
    {{ pimcore_image('image', {
        thumbnail: 'myThumbnail',
       })
    }}
    </p>
</div>

{# Use with the image editable in documents using dynamic configuration #}
{{ pimcore_image('image', {
    thumbnail: {
        width: 500,
        height: 0,
        aspectratio: true,
        interlace: true,
        quality: 95,
        format: 'PNG'
    }
}) }}

{# Use from an object-field #}
{% if myObject.myImage is instanceof('Asset\\Image') %}
    <img src="{{ myObject.myImage.getThumbnail('myThumbnail').getHref() }}" />
{% endif %}

{# Use from an object-field using dynamic configuration #}
{% if myObject.myImage is instanceof('Asset\\Image') %}
    <img src="{{ myObject.myImage.getThumbnail({width: 220, format: 'jpeg'}).getHref() }}" />
{% endif %}
```

</div>
