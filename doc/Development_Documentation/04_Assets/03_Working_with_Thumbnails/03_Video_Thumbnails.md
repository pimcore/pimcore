# Video Thumbnails
Pimcore is able to convert any video to web formats automatically. It is also possible capture a 
custom preview image out of the video.

> **IMPORTANT** 
> To use all the following functionalities it is required to install FFMPEG on the server.  
> For details, please have a look at [Additional Tools Installation](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md).

### Using Video Thumbnails in your Code

##### Examples - Image Snapshots
```php
$asset = Asset::getById(123);
if($asset instanceof Asset\Video) {
 
   // get a preview image thumbnail of the video, resized to the configuration of "myThumbnail"
   echo $asset->getImageThumbnail("myThumbnail");
 
   // get a snapshot (image) out of the video at the time of 10 secs. (see second parameter) using a dynamic image thumbnail configuration
   echo $asset->getImageThumbnail(["width" => 250], 10);
}
```

##### Examples - Video Transcoding
```php 
$asset = Asset::getById(123);
if($asset instanceof Asset\Video) {
 
   $thumbnail = $asset->getThumbnail("myVideoThumbnail"); // returns an array
   if($thumbnail["status"] == "finished") {
      p_r($thumbnail["formats"]); // transcoding finished, print the paths to the different formats
      /*
         OUTPUTS:
         Array(
             "mp4" => "/website/var/tmp/video.....mp4",
             "webm" => "/website/var/tmp/video.....webm"
         )
      */
   } else if ($thumbnail["status"] == "inprogress")  {
      echo "transcoding in progress, please wait ...";
   } else {
      echo "transcoding failed :(";
   }
}
```

##### Media Queries in Thumbnail Configuration
```php
// list all available medias in "myVideoThumbnail" thumbnail configuration
p_r(array_keys(Asset\Video\Thumbnail\Config::getByName("myVideoThumbnail")->getMedias()));

$asset = Asset::getById(123);
if($asset instanceof Asset\Video) {
 
   $thumbnail = $asset->getThumbnail("myVideoThumbnail"); // returns an array
   if($thumbnail["status"] == "finished") {
      p_r($thumbnail["formats"]); // transcoding finished, print the paths to the different formats
      /*
         OUTPUTS:
         Array(
             "mp4" => "/website/var/tmp/video.....mp4",
             "medias" => array:1 [
                "mp4" => array:2 [  
                    "(min-width: 576px)" => "/website/var/tmp/video..~-~%28min-width%3A%20576px%29.mp4"
                    "(min-width: 800px)" => "/website/var/tmp/video..~-~%28min-width%3A%20800px%29.mp4"
                
         )
      */
      $thumbnail["formats"]["medias"]["mp4"]["(min-width: 576px)"]; //get thumbnail path for a media query
   } else if ($thumbnail["status"] == "inprogress")  {
      echo "transcoding in progress, please wait ...";
   } else {
      echo "transcoding failed :(";
   }
}
```
--- 

### Using with the Video Editable
Please have a look at [Video Editable](../../03_Documents/01_Editables/38_Video.md). 
