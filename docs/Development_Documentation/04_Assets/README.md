# Assets

Assets are files that can be managed within the Pimcore system which you can organize in folders. The most common assets 
are images. Other kinds of common assets are PDF or MS Word documents which people can download from the website.
Pimcore is able to render preview images for most file types. 

> Please note: The asset preview tab for documents uses Google services if the Browser has no PDF 
> displaying capabilities and Ghostscript or LibreOffice are not availabe on the server.

Some file types, like images, can be edited directly in Pimcore and can be used to create thumbnails for different 
output channels. Note that the image editor does use the Adobe Creative SDK under the hood, specifically it does use the [Image Editor UI](https://creativesdk.adobe.com/docs/web/#/articles/imageeditorui/index.html) which does automatically resize images to 1000px or less.



![Pimcore Assets](../img/pimcore_assets.png)
   
The sub chapters of this chapter provide insight into details for
 * [Working with Assets via PHP API](./01_Working_with_PHP_API.md) 
 * [Working with Thumbnails](./03_Working_with_Thumbnails/README.md)
