# Href Editable

[TOC]

## General

Href provides to create a reference to an other element in Pimcore (document, asset, object).
This can be useful to link a video for example (in editmode show the href to link the video out of the assets, outside embed a object code an make a reference to the video.).

In frontend-mode the href returns the path of the linked element.

## Configuration

| Name       | Type    | Description                                                                                                                                |
|------------|---------|--------------------------------------------------------------------------------------------------------------------------------------------|
| types      | array   | Allowed types (document, asset, object), if empty all types are allowed                                                                    |
| subtypes   | array   | Allowed subtypes grouped by type (folder, page, snippet, image, video, object, ...), if empty all subtypes are allowed (see example below) |
| classes    | array   | Allowed object class names, if empty all classes are allowed                                                                               |
| reload     | boolean | true triggers page reload on each change                                                                                                   |
| width      | int     | Width of the field in pixel.                                                                                                               |
| uploadPath | string  | Target path for (inline) uploaded assets                                                                                                   |


## Properties and methods

| Name          | Type     | Description                      |        |                                                                   |
|---------------|----------|----------------------------------|--------|-------------------------------------------------------------------|
| getElement()  | Document | Asset                            | Object | Object assigned to the href.                                      |
| getFullPath() | string   | Get the of the assigned element. |        |                                                                   |
| element       | Document | Asset                            | Object | The property for getElement() it's a good idea to use the getter. |

## Examples

### Basic usage

You can just create the code line like, below:

```php 
<?= $this->href("myHref"); ?>
```

After, the view in the administration panel changes like in the picture:

![Href editable preview in the administration panel](../../img/href_backend_preview.png)

### Usage with restriction

If you want specify elements which could be used in the href editable, just use **types**, **subtypes** and **classes**
keys in the editable configuration.

Have a look at the example, below.
 
```php
<?= $this->href("myHref", [
    "types" => ["asset","object"],
    "subtypes" => [
        "asset" => ["video","image"],
        "object" => ["object"]
     ],
    "classes" => ["person"],
]); ?>
```

We specified that in to the **myHref** editable user can put only video / image **assets** and **objects** represented by Person (`\Pimcore\Model\Object\Person`) class. 
 
As you see in the picture below, it's impossible to drop any other type to that editable.

![Href restriction](../../img/href_restriction_in_backend.png)

### Video download example

You could use the href editable to make download video feature in your website. 

You could check an element type using `instanceof` on your href element `getElement` method.  

```php
<?php if ($this->editmode): ?>
    <?= $this->href("myHref"); ?>
<?php else: ?>
    <?php if ($this->href("myHref")->getElement() instanceof Asset\Video): ?>
        <a href="<?= $this->href("myHref")->getFullPath() ?>"><?= $this->translate("Video Download") ?></a>
    <?php endif; ?>
<?php endif; ?>
```
