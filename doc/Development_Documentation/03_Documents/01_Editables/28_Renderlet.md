# Renderlet Editable

## General

The renderlet is a special container which is able to handle every element in Pimcore (Documents, Assets, Objects).
You can decide in your controller/action what to do with the element which is linked to the renderlet.
So it's possible to make a multifunctional area in editmode where the editor can drop anything on it.
A typical use-case would be to render product objects within a document. 

## Configuration

| Name           | Type      | Description                                                                        | Mandatory   |
|----------------|-----------|------------------------------------------------------------------------------------|-------------|
| `action`       | string    | Specify action                                                                     | X           |
| `className`    | string or string[] | Specify class name (if type **object** chosen) as single string or as string array |    |
| `controller`   | string    | Specify controller                                                                 | X           |
| `height`       | integer   | Height of the renderlet in pixel                                                   |             |
| `bundle`       | string    | Specify bundle (default: `AppBundle`)                                              |             |
| `reload`       | bool      | Reload document on change                                                          |             |
| `template`     | string    | Specify template                                                                   |             |
| `title`        | string    | Add a title to the box in editmode                                                 |             |
| `type`         | string    | The type of the element assigned to the renderlet (document,asset,object)          |             |
| `width`        | integer   | Width of the renderlet in pixel                                                    |             |
| `class`        | string    | A CSS class that is added to the surrounding container of this element in editmode |             |

Optionally you can pass every parameter (with a scalar data type) you like to the renderlet which can be accessed in 
the configured controller with `$request->get('yourKey')`.

## Methods

| Name            | Return    | Description                                                 |
|-----------------|-----------|-------------------------------------------------------------|
| `isEmpty()`     | bool      | Whether the editable is empty or not.                       |

## In the configured Controller Action

In the target controller action, you get the following parameters which can be accessed by `$request->get('key')`.

| Name       | Type                   | Description                                                                                      |
|------------|------------------------|--------------------------------------------------------------------------------------------------|
| `id`       | integer                | The id of the element assigned to the renderlet                                                  |
| `type`     | string                 | The type of the element assigned to the renderlet (document,asset,object)                        |
| `subtype`  | string                 | The subtype of the element assigned to the renderlet (folder, image, link, page, classname, ...) |

If you have defined any custom parameters on the renderlet configuration you can access them also with `$request->get('yourParam')`.

## Example

The code below shows how to use renderlet to create gallery based on it. 

### Specify the Renderlet Editable in a Template

<div class="code-section">

```php
<section id="renderlet-gallery">
    <?= $this->renderlet("myGallery", [
        "controller" => "content",
        "action" => "myGallery",
        "title" => "Drag an asset folder here to get a gallery",
        "height" => 400
    ]); ?>
</section>
```

```twig
<section id="renderlet-gallery">
    {{
        pimcore_renderlet('myGallery', {
            "controller" : "content",
            "action" : "myGallery",
            "title" : "Drag an asset folder here to get a gallery",
            "height" : 400
        })
    }}
</section>
```

</div>

Now editors are able to put elements onto the renderlet in the editmode.

![Renderlet gallery - editmode](../../img/editables_renderlet_gallery_example_editmode.png)

### Specify the Controller Action

```php
public function myGalleryAction(Request $request)
{
    if ('asset' === $request->get('type')) {
        $asset = Asset::getById($request->get('id'));
        if ('folder' === $asset->getType()) {
            $this->view->assets = $asset->getChildren();
        }
    }
}
```

The action is responsible for validation and transferring assets to the view.
Of course, to limit access to the renderlet, you can use the `type` configuration option as well. 

### Create View

Now you have to create the template file at: `website/views/scripts/content/my-gallery.php`

<div class="code-section">

```php
<?php
/** @var \Pimcore\Templating\PhpEngine $this */
?>
<?php if ($this->assets): ?>
    <div class="my-gallery">
        <?php
        foreach ($this->assets as $asset):
            if ($asset instanceof Pimcore\Model\Asset\Image):
                /** @var Pimcore\Model\Asset\Image $asset */
                ?>
                <div class="gallery-row">
                    <?= $asset->getThumbnail('galleryThumbnail')->getHtml(); ?>
                </div>
                <?php
            endif;
        endforeach; ?>
    </div>
<?php endif; ?>
```

```twig
{% if assets %}
	<div class="my-gallery">
		{% for asset in assets %}
			{% if asset is instanceof('\\Pimcore\\Model\\Asset\\Image') %}
				<div class="gallery-row">
				{{ asset.getThumbnail('galleryThumbnail').getHTML() }}
				</div>
			{% endif %}
		{% endfor %}
	</div>
{% endif %}
```

</div>

And the final view is like, below:
![Rendered renderlet - frontend](../../img/editables_renderlet_rendered_view.png)


## Editmode

> Please be aware, that the renderlet itself is not editmode-aware. If you need to determine within the renderlet whether in editmode or not, you need to pass that parameter to the renderlet.

<div class="code-section">

```php
$this->renderlet("myRenderlet", [
....
'editmode' => $this->editmode
]);
```

```twig
{{
	pimcore_renderlet('myRenderlet', {
		....
		"editmode" : editmode
	})
}}
```
</div>

Within the renderlet, you can access the editmode parameter as follows:

```php
$this->getParam("editmode")
```
