---
title: Font Thumbnails
description: Generating preview images for font assets (TTF, OTF, WOFF, ...).
---

# Asset Font Thumbnails (TTF, OTF, WOFF, ...)

Font files are stored as assets of the type `font` (`Pimcore\Model\Asset\Font`). This covers
TrueType (`.ttf`), OpenType (`.otf`), font collections (`.ttc`, `.otc`), WOFF/WOFF2 (`.woff`, `.woff2`)
and Embedded OpenType (`.eot`) files.

For a font asset, Pimcore can create an image thumbnail which shows a sample text set in the font.
The sample text is rendered into an image first, which is then processed with any
[image thumbnail configuration](./01_Image_Thumbnails.md), exactly like the thumbnails of
[asset documents](./03_Asset_Document_Thumbnails.md).

## Required Tools

The sample text is rendered with the FreeType support of the PHP GD extension (`ext-gd`, which is a
requirement of Pimcore anyway). Every format the FreeType library is able to read can be previewed.
Depending on how FreeType was built, WOFF2 support might not be available. Embedded OpenType (`.eot`) files
cannot be read by FreeType - these assets get the "file type not supported" placeholder as preview.

> **Important**
> The preview thumbnail of a font is generated asynchronously as part of the
> [Symfony Messenger queue](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/01_Installation/03_Advanced_Installation_Topics/01_Symfony_Messenger.md),
> under the `pimcore_asset_update` messenger queue.

## Examples

```php
$asset = Asset::getById(123);
if ($asset instanceof Asset\Font) {

   // get a thumbnail of the sample text, resized to the configuration of "myThumbnail"
   echo $asset->getImageThumbnail('myThumbnail');

   // use a dynamic configuration and do not generate the thumbnail immediately (see second parameter),
   // the thumbnail is then generated on request
   echo $asset->getImageThumbnail(['width' => 400, 'contain' => true], true);
}
```

In Twig templates, the `pimcore_asset_font` test can be used to check whether an asset is a font:

```twig
{% if asset is pimcore_asset_font %}
    <img src="{{ asset.imageThumbnail('myThumbnail') }}" alt="{{ asset.filename }}">
{% endif %}
```

## Configuration

```yaml
pimcore:
    assets:
        font:
            thumbnails:
                enabled: true # process thumbnails for asset fonts (default: true)
                preview_text: "The quick brown fox jumps over the lazy dog\n0123456789" # sample text, "\n" separates lines
                font_size: 48 # font size in points used to render the sample text (default: 48)
```

| Option | Default | Description |
|--------|---------|-------------|
| `thumbnails.enabled` | `true` | Process thumbnails for asset fonts. |
| `thumbnails.preview_text` | `The quick brown fox jumps over the lazy dog`, the alphabet (upper and lower case), digits and punctuation | Sample text which is rendered for the thumbnails, `\n` separates lines. |
| `thumbnails.font_size` | `48` | Font size (in points, 8 - 256) used to render the sample text. |

Changing `preview_text` or `font_size` doesn't require clearing existing thumbnails, they are part of the
thumbnail file name, so new thumbnails are generated automatically.

## Type Mapping

Font files are detected by their MIME type (`font/*`, `application/x-font-*`, `application/font-*`,
`application/vnd.ms-opentype`, `application/vnd.ms-fontobject`) and - if the MIME type cannot be detected - by their
file extension. Like for every other asset type, the mapping can be extended with the `pimcore.assets.type_definitions`
configuration. The `matching` patterns are merged with the default patterns, e.g. to map PostScript Type 1 fonts
to the `font` type as well:

```yaml
pimcore:
    assets:
        type_definitions:
            map:
                font:
                    matching: [ "/\\.(pfa|pfb)$/i" ]
```
