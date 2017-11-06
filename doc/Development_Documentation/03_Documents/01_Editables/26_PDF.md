# PDF Editable

> This editable requires Ghostscript installed on your server. 
> See [System Requirements](../../23_Installation_and_Upgrade/01_System_Requirements.md)

## General

The PDF editable allows you to embed asset documents (pdf, doc, xls, ...) into documents.

## Configuration

| Name                | Type      | Description                                                                             |
|---------------------|-----------|-----------------------------------------------------------------------------------------|
| `width`             | integer   | Width of the viewer (default 100%)                                                      |
| `height`            | integer   | Height of the viewerin pixel                                                            |
| `fullscreen`        | bool      | Allow fullscreen or not                                                                 |
| `hotspotCallback`   | closure   | Possibility to add custom attributes on hotspot `<div>` tags, ... see example below     |
| `class`             | string    | A CSS class that is added to the surrounding container of this element in editmode      |

## Methods

| Name            | Return   | Description                                 |
|-----------------|----------|---------------------------------------------|
| `getData()`     | array    | Returns all stored data for this editable   |
| `isEmpty()`     | boolean  | Whether the editable is empty or not        |
| `getElement()`  | Asset    | Returns the assigned Asset Document         |

## Examples

### Basic usage
<div class="code-section">

```php
<div class="pdf">
    <?= $this->pdf("myPdf", ["width" => 640]); ?>
</div>
```

```twig
<div class="pdf">
    {{ pimcore_pdf("myPdf", {"width": 640}) }}
</div>
```
</div>

This looks like the following in editmode: 

![PDF editable - the empty area](../../img/editables_pdf_empty_container.png)

A user can now drag documents there from the *Assets* tree:

![PDF editable - drag a document](../../img/editables_pdf_filled.png)

### Processing Metadata

You're also able to add some meta information (for example hotspots) on every page of the assigned PDF. 
![Add metada to the PDF editable](../../img/editables_pdf_add_metadata.png)

The example below shows how you can retrieve this information:
```php
<div class="pdf">
    <?= $this->pdf("myPdf", [
        "hotspotCallback" => function($data) {

            \Zend_Debug::dump($data);

            return $data;
        }
    ]); ?>
</div>
```

The output:

```
array(5) {
  ["top"] => int(0)
  ["left"] => int(0)
  ["width"] => float(16.181229773463)
  ["height"] => float(12.5)
  ["data"] => array(2) {
    [0] => array(3) {
      ["name"] => string(4) "note"
      ["value"] => string(26) "This page isn't up-to-date"
      ["type"] => string(8) "textarea"
    }
    [1] => array(3) {
      ["name"] => string(7) "updated"
      ["value"] => bool(false)
      ["type"] => string(8) "checkbox"
    }
  }
}
```

As you can see, you're able to get information about every metadata added to specified page. 
In that case, on the first page of the pdf document you can find the textarea note and the unchecked checkbox.

### Pimcore PDF - Possible JavaScript Methods

The PDF editable also, allows you to use javascript actions.
There is always created a javascript object named: `pimcore_pdf`, which contains an object for every pdf editable on this page. 

The list of available actions:

| Function Name                     | Description             |
|-----------------------------------|-------------------------|
| `pimcore_pdf["myPdf"].toPage(3)`  | go to the page 3        |
| `pimcore_pdf["myPdf"].nextPage()` | go to the next page     |
| `pimcore_pdf["myPdf"].prevPage()` | go to the previous page |




