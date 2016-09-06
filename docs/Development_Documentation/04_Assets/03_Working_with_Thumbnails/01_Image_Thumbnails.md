# Image Thumbnails

For images, Pimcore offers an advanced thumbnail-service also called 'image-pipeline'. It allows you to transform images
 in unlimited steps to the expected result. You can configure them in ```Settings``` -> ```Thumbnails```.
 
 
With this service every image which is stored as an asset can be transformed. Pimcore doesn't support to modify images 
which are not stored as an asset inside Pimcore.

<div class="notice-box">
IMPORTANT: Use imagick PECL extension for best results, GDlib is just a fallback with limited functionality 
(only PNG, JPG, GIF) and less quality!
Using ImageMagick pimcore supports hundreds of formats including: AI, EPS, TIFF, PNG, JPG, GIF, PSD, ...
</div>

To use the thumbnailing service of Pimcore, you have to create a transformation pipeline first. To do so, open 
```Settings``` -> ```Thumbnails```, and click on ```Add Thumbnail``` to create a new configuration.
The fields name, description, format and quality should be clear, interesting are now the transformations. 
Click on ```+``` to add a new transformation, so that it look like that for example:

![Thumbnails](../img/thumnbails1.png)

Important: The transformations are performed in the order from the top to the bottom. This is for example important 
in the configuration above. If the you first round the corners this would be performed on the original image, 
and then the image will get resized, so the rounded corners are also resized. 

To retrieve a thumbnail from an asses simply call ```$asset->getThumbnail()``` on the asset object, this will return 
you the path to the thumbnail file beginning from the document root, for example: 
```/website/var/tmp/image-thumbnails/0/53/thumb__exampleCover/img_0322.jpeg```

This path can then be directly used to display the image in a ```<img />``` tag. For example:
```php
<?php
    use Pimcore\Model\Asset;
    // get an asset
    $asset = Asset::getById(1234);
?>
 
<?php if ($asset) { ?>
   <img src="<?= $asset->getThumbnail("myThumbnailName") ?>" />

    <!-- preferred alternative - let Pimcore create the whole image tag -->
    <?php echo $asset->getThumbnail("myThumbnail")->getHTML(); ?>

<?php } ?>
```

## Explanation of the transformations

| Transformation | Description | Configuration | Result |
|----------------|-------------|---------------|--------|
| ORIGINAL IMAGE | This is the image which is used in the following transformations | NONE ;-) | ![Sample Original](../../../img/thumbnails-sample-original.png) |
| RESIZE | The image is exactly resized to the given dimensions without respecting the ratio. | ![Config Resize](../../img/thumbnails-config-resize.png) | ![Sample Resize](../../../img/thumbnails-sample-resize.png) |



For Thumbnails in action also have a look at our [live demo](http://demo.pimcore.org/en/basic-examples/thumbnails).



##### For more information about configuring thumbnails in Pimcore backend have a look at [User Documentation]().
[comment]: #(TODO add links)



