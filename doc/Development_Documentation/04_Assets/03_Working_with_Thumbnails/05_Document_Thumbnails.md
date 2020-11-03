# Asset Document Thumbnails (PDF, docx, odf, ...)

This feature allows you to create an image thumbnail of nearly any document format, like doc(x), ppt(x), pdf, xls(x), 
odt, ods, odp and many others. 

You can of course use existing image-thumbnail configurations to create a thumbnail of your choice.
 
##### Examples
```php
$asset = Asset::getById(123);
if($asset instanceof Asset\Document) {
 
   // get a thumbnail of the first page, resized to the configuration of "myThumbnail"
   echo $asset->getImageThumbnail("myThumbnail");
 
 
   // get the thumbnail for the third (see second parameter) page using a dynamic configuration
   echo $asset->getImageThumbnail(["width" => 230, "contain" => true], 2);
 
    
   // get the thumbnail URL for all pages, but do not generate them immediately (see third parameter) - the thumbnails are then generated on request
   $thumbnailUrls = [];
   for($i=1; $i<=$asset->getPageCount(); $i++) {
      $thumbnailUrls[] = $asset->getImageThumbnail("myThumbnail", $i, true);
   }
 
}
```

> This feature requires Ghostscript and LibreOffice on the server installed.

