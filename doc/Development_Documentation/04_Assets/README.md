# Assets

Assets are files that can be managed within the Pimcore system which you can organize in folders. The most common assets 
are images. Other kinds of common assets are PDF or MS Word documents which people can download from the website.
Pimcore is able to render preview images for most file types. 

##### Following file types are supported for preview: 
* Images: All common formats such as .jpg, .png, .eps, .psd, .tif, .svg, ... (See [Imagemagick website](https://imagemagick.org/script/formats.php) for a full list of supported formats)
* Videos: All common formats such as .avi, .mp4, .mkv, .mov, ... (See [FFmpeg website](https://www.ffmpeg.org/general.html#File-Formats) for a full list of supported formats)
* Documents: All Microsoft Office formats such as .docx, .docm, .pptx, .xlsx and formats such as .pdf, .rtf, ... (See [LibreOffice documentation](https://wiki.documentfoundation.org/images/1/13/1_-_File_Formats.odt) for a full list of supported formats)

> Please note: The asset preview tab for documents uses Google services if the Browser has no PDF 
> displaying capabilities and Ghostscript or LibreOffice are not availabe on the server.

Some file types, like images, can be edited directly in Pimcore and can be used to create thumbnails for different 
output channels. Note that the image editor does use the [miniPaint image editor](https://github.com/viliusle/miniPaint) under the hood.

![Pimcore Assets](../img/pimcore_assets.png)
   
The sub chapters of this chapter provide insight into details for
 * [Working with Assets via PHP API](./01_Working_with_PHP_API.md) 
 * [Working with Thumbnails](./03_Working_with_Thumbnails/README.md)
 * [Accessing Assets via WebDAV](./05_Accessing_Assets_via_WebDAV.md)
 * [Restricting Public Asset Access](./07_Restricting_Public_Asset_Access.md)
