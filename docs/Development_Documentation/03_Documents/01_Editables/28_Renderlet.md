# Renderlet

## General

The renderlet is a special container which is able to receive every object in Pimcore (Documents, Assets, Objects).
You can decide in your controller/action what to do with the object which is linked to the renderlet.
So it's possible to make a multifunctional dropbox in editmode where the editor can drop anything on it.

## Configuration

| Name         | Type      | Description                                                                 | Mandatory   |
|--------------|-----------|-----------------------------------------------------------------------------|-------------|
| action       | string    | Specify action                                                              | X           |
| className    | string    | Specify class name (if type **object** chosen)                              |             |
| controller   | string    | Specify controller                                                          | X           |
| height       | integer   | Height of the renderlet in pixel                                            |             |
| module       | string    | Specify module (default: website)                                           |             |
| reload       | bool      | Reload document on change                                                   |             |
| template     | string    | Specify template                                                            |             |
| title        | string    | Add a title to the box in editmode                                          |             |
| type         | string    | The type of the element assigned to the renderlet (document,asset,object)   |             |
| width        | integer   | Width of the renderlet in pixel                                             |             |

Optionally you can pass every parameter (with a simple data type) you like to the renderlet which can be accessed by 
the configured controller with ```$this->getParam("yourKey")```.

## In the configured Controller Action

In the target controller action you get the follwing parameters which can be accessed by ```$this->getParam("key")```.

| Name     | Type                   | Description                                                                                      |
|----------|------------------------|--------------------------------------------------------------------------------------------------|
| document | Pimcore\Model\Document | If the element which is dropped on the renderlet is a document this parameter is defined.        |
| id       | integer                | The id of the element assigned to the renderlet                                                  |
| object   | Pimcore\Model\Object   | If the element which is dropped on the renderlet is an object this parameter is defined.         |
| subtype  | string                 | The subtype of the element assigned to the renderlet (folder, image, link, page, classname, ...) |
| type     | string                 | The type of the element assigned to the renderlet (document,asset,object)                        |

If you have defined custom parameters to the renderlet configuration you can access them also with ```$this->getParam```.

## Example

The code below, shows how to use renderlet to create gallery based on it. 

### Specify the renderlet editable in a template

* controller - a controller which contains action responsible for renderlet
* action - that action could for example put assets models to the view

```php
<section id="renderlet-gallery">
    <?php echo $this->renderlet("myGallery", [
        "controller" => "content",
        "action" => "my-gallery",
        "title" => "Drag an asset folder here to get a gallery",
        "height" => 400
    ]); ?>
</section>
```

After it, users are able to put elements into the renderlet in the edit mode.

![Renderlet gallery - editmode](../../img/editables_renderlet_gallery_example_editmode.png)

### Specify the controller action

```php
public function myGalleryAction()
{
    if($this->getParam('type') == 'asset') {
        $asset = Asset::getById($this->getParam('id'));
        if($asset->getType("folder")) {
            $this->view->assets = $asset->getChilds();
        }
    }
}
```

The action is responsible for validation and transfering assets to the view.
Of course, to limit access to the renderlet, you can use the **type** configuration option as well. 

### Create view

Now you should create the template file in: ```website/views/scripts/content/my-gallery.php``` path. 

```php
<?php
/** @var \Pimcore\View $this */
?>
<?php if($this->assets): ?>
    <div class="my-gallery">
        <?php
        foreach($this->assets as $asset):
            if($asset instanceof Pimcore\Model\Asset\Image):
                /** @var Pimcore\Model\Asset\Image $asset */
            ?>
            <div class="gallery-row">
                <?php echo $asset->getThumbnail('galleryThumbnail')->getHTML(); ?>
            </div>
        <?php
            endif;
        endforeach; ?>
    </div>
<?php endif; ?>
```

And the final view is like, below:

![Rendered renderlet - frontend](../../img/editables_renderlet_rendered_view.png)


## Editmode

<div class="notice-box">

Please be aware, that the renderlet itself is not editmode-aware. If you need to determine within the renderlet whether in editmode or not, you need to pass that parameter to the renderlet.

</div>

```php
$this->renderlet("myRenderlet", [
....
'editmode' => $this->editmode
]);
```

Within the renderlet, you can access the editmode parameter as follows:

```php
$this->getParam("editmode")
```