# Asset Document Thumbnails (PDF, DOCX, ODF, ...)

This feature allows you to create an image thumbnail of nearly any document format, like doc(x), ppt(x), pdf, xls(x), 
odt, ods, odp and many others. 

You can of course use existing image-thumbnail configurations to create a thumbnail of your choice.
 
> **Important**   
> Please be aware that the processing of thumbnails for documents is done asynchronously as part of the [maintenance job](../../01_Getting_Started/00_Installation/01_Webserver_Installation.md#5-maintenance-cron-job), under the `pimcore_asset_update` messenger queue
 
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

This feature requires Ghostscript and at least [Gotenberg](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md#gotenberg) or [LibreOffice](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md#libreoffice-pdftotext-inkscape-) to be installed on the server.

##### To build the function generating thumbnail for List of assets:
> It is recommended to use named thumbnails for caching purpose.

```php
   $list = new Asset\Listing();
   $assets = $list->getAssets();
   foreach ($assets as $asset) {
      echo match (true) {
         $asset instanceof Asset\Image => $asset->getThumbnail('myThumbnail')?->getPath(),
         $asset instanceof Asset\Document => $asset->getImageThumbnail('myThumbnail')?->getPath(),
         default => '',
      };
   }
```

## Disable generating thumbnails for asset documents
If you want to completely disable the thumbnail generation for asset documents, this can be done with following config:
```yaml
pimcore:
    assets:
        document:
            thumbnails:
                enabled: false
```
