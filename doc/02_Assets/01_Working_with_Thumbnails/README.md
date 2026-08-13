---
title: Working with Thumbnails
description: Generating optimized image, video, and document thumbnails from Pimcore assets.
---

# Working with Thumbnails

Pimcore provides a thumbnail processing engine that calculates optimized output variants
from source assets. For different output channels, Pimcore can generate images with the right
dimensions, file sizes, formats, and quality settings from a single source file.

## Allowed Formats

Pimcore allows the following formats for thumbnails out of the box:
`'avif', 'eps', 'gif', 'jpeg', 'jpg', 'pjpeg', 'png', 'svg', 'tiff', 'webm', 'webp', 'print'`.

To add a different format, extend the list in your configuration.
Note that you must include the complete list of formats when overriding:

```yaml
pimcore:
    assets:
        thumbnails:
            allowed_formats:
                - 'avif'
                - 'eps'
                - 'gif'
                - 'jpeg'
                - 'jpg'
                - 'pjpeg'
                - 'png'
                - 'svg'
                - 'tiff'
                - 'webm'
                - 'webp'
                - 'pdf'
                - 'print' # Add your desired format here
```

## Thumbnail Types

- [Image Thumbnails](./01_Image_Thumbnails.md) - transformation pipelines for resizing, cropping,
  and format conversion
- [Video Thumbnails](./02_Video_Thumbnails.md) - transcoding and preview image extraction
- [Asset Document Thumbnails](./03_Asset_Document_Thumbnails.md) - preview images for PDFs,
  Office documents, and other formats
- [Advanced Image Thumbnail Features](./04_Advanced_Image_Thumbnails.md) - high-resolution support,
  media queries, focal points, auto format, ICC profiles, and more
