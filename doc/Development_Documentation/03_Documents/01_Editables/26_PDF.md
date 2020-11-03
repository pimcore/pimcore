# PDF Editable

> This editable requires Ghostscript installed on your server. 
> See [System Requirements](../../23_Installation_and_Upgrade/01_System_Requirements.md)

## General

The PDF editable allows you to embed asset documents (pdf, doc, xls, ...) into documents.

## Configuration

| Name                | Type         | Description                                                                             |
|---------------------|--------------|-----------------------------------------------------------------------------------------|
| `thumbnail`         | string/array | Thumbnail config (name or array) for the preview image                                  |
| `uploadPath`        | string       | Target path for (inline) uploaded images                                                |

## Methods

| Name            | Return   | Description                                 |
|-----------------|----------|---------------------------------------------|
| `getData()`     | array    | Returns all stored data for this editable   |
| `isEmpty()`     | boolean  | Whether the editable is empty or not        |
| `getElement()`  | Asset    | Returns the assigned Asset Document         |

## Examples

### Basic usage

<div class="code-section">

PHP:
```php
<div class="code-section">
    <div class="pdf">
        <?= $this->pdf("myPdf", ["width" => 640]); ?>
    </div>
</div>    
```

Twig:
```twig
<div class="code-section">
    <div class="pdf">
        {{ pimcore_pdf("myPdf", {"width": 640}) }}        
    </div>
</div>    
```

</div>

This looks like the following in editmode: 

![PDF editable - the empty area](../../img/editables_pdf_empty_container.png)

A user can now drag documents there from the *Assets* tree:

![PDF editable - drag a document](../../img/editables_pdf_filled.png)

