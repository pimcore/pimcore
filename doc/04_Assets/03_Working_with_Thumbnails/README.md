# Working With Thumbnails

Pimcore provides a sophisticated thumbnail processing engine for calculating thumbnails based on source assets. So for 
different output channels Pimcore can calculate and provide optimized images in terms of dimensions, file sizes, formats
and much more.

This functionality allows true single source publishing with Pimcore.

### Allowed formats
Pimcore allows the following formats for thumbnails out of the box:
`'avif', 'eps', 'gif', 'jpeg', 'jpg', 'pjpeg', 'png', 'svg', 'tiff', 'webm', 'webp', 'print'`.

If you want to use a different format, you can easily extend the list of supported formats.
Keep in mind that you must copy the whole list of formats and add your desired format to it.
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

##### Thumbnails are available for following file types: 
* [Image Thumbnails](./01_Image_Thumbnails.md)
* [Video Thumbnails](./03_Video_Thumbnails.md)
* [Asset Document Thumbnails](./05_Document_Thumbnails.md)
