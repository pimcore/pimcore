
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
