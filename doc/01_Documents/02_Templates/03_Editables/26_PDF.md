---
title: PDF
description: Embed PDF and other document assets into pages with preview image generation via Ghostscript.
---

# PDF Editable

> This editable requires Ghostscript installed on your server.
> See [System Requirements](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/01_System_Requirements.md)

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

```twig
<div class="pdf">
    {{ pimcore_pdf("myPdf", {"width": 640}) }}        
</div>
```

This looks like the following in editmode: 

<div class="image-as-lightbox"></div>

![PDF editable - the empty area](../../../img/editables_pdf_empty_container.png)

A user can now drag documents there from the *Assets* tree:

<div class="image-as-lightbox"></div>

![PDF editable - drag a document](../../../img/editables_pdf_filled.png)
