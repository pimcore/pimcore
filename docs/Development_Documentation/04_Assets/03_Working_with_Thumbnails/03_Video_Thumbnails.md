# Video Thumbnails
Pimcore is able to convert videos to the most formats used in the web automatically. It is also possible capture a 
custom preview image out of the video.

<div class="notice-box">
To use all these functionalities it is required to install FFMPEG on the server. It is also required to configure the 
path to FFMPEG and to the PHP-CLI binary in the system settings. For Details of installation 
see [Installation Documentation](../../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md).
</div>

### Using Video Thumbnails in Code

##### Examples - Image Snapshots
```php
$asset = Asset::getById(123);
if($asset instanceof Asset\Video) {
 
   // get a preview image thumbnail of the video, resized to the configuration of "myThumbnail"
   echo $asset->getImageThumbnail("myThumbnail");
 
   // get a snapshot (image) out of the video at the time of 10 secs. (see second parameter) using a dynamic image thumbnail configuration
   echo $asset->getImageThumbnail(["width" => 250, "frame" => true], 10);
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

--- 
For more information about Working with Video Thumbnails in Pimcore Backend also have a look at the [User Documentation]().

[comment]: #(TODO add links)
