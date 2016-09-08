# WYSIWYG

## General

The WYSIWYG editable comes with abilities to adding the styled content.
Similar to Textarea and Input you can put the WYSIWYG editable in the templates. 
Except the basic usage, due to the configuration options you can specify the toolbar, paragraph tags behaviour and set a default value.
 
## Configuration

| Name          | Type    | Description                                             |
|---------------|---------|---------------------------------------------------------|
| customConfig  | string  | Path to Javascript file with configuration for CKEditor |
| enterMode     | integer | Set it to 2 if you don't want to add the P-tag          |
| height        | integer | min-height of the field in pixels                       |
| toolbarGroups | string  | A toolbar config array (see below)                      |
| width         | integer | Width of the field in pixels                            |

## Accessible properties

| Name            | Type      | Description                                                            |
|-----------------|-----------|------------------------------------------------------------------------|
| text            | string    | Value of the WYSIWYG, this is useful to get the value even in editmode |

## Examples

### Basic usage

```wysiwyg``` helper doesn't require any attributes except ```name```. 
The following code specifies also height for the rendered WYSIWYG editable.

```php
<section id="marked-content">
    <?php echo $this->wysiwyg("specialContent", [
        "height" => 200
    ]); ?>
</section>
```

If you have a look at the edit mode, you will see that our WYSIWYG is rendered with the full toolbar.

![complete WYSIWYG - editmode](../../img/editables_wysiwyg_basic_editmode.png)


### Custom configuration for CKeditor

The complete list of configuration options you can find on [CKeditor toolbar documentation](http://docs.ckeditor.com/#!/guide/dev_toolbar).

The WYSIWYG editable allows us to specify the toolbar. 
If you have to limit styling options (for example only basic styles like ```<b>``` tag and lists would be allowed), just use ```toolbarGroups``` option.

```php
<section id="marked-content">
    <?php echo $this->wysiwyg("specialContent", [
        "height" => 200,
        "toolbarGroups" => [
            [
                "name" => 'basicstyles',
                "groups" => [ 'basicstyles', 'list', "links"]
            ]
        ]
    ]); ?>
</section>
```

Now the user can uses only the limited toolbar.

![Wysiwyg with limited toolbar - editmode](../../img/editables_wysiwyg_toolbar_editmode.png)


There is also additional way to specify the configuration. You can just add customConfig as a path in file system.

```php
<section id="marked-content">
    <?php echo $this->wysiwyg("specialContent", [
        "height" => 200,
        "customConfig" => "/custom/ckeditor_config.js"
    ]); ?>
</section>
```

### Text output in editmode

With the following code you can get the text even in editmode:

```php
<?php echo $this->wysiwyg("specialContent"); ?>
<?php if($this->editmode): ?>
<h4>Preview</h4>
<div style="border: 1px solid #000;" class="preview">
    <?php echo $this->wysiwyg("specialContent")->text; ?>
</div>
<?php endif; ?>
```

![WYSIWYG with preview - editmode](../../img/editables_wysiwyg_with_preview_editmode.png)