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
             "mp4" => "/public/var/tmp/video.....mp4",
             "webm" => "/public/var/tmp/video.....webm"
         )
      */
   } else if ($thumbnail["status"] == "inprogress")  {
      echo "transcoding in progress, please wait ...";
   } else {
      echo "transcoding failed :(";
   }
}
```

#### Adaptive bitrate video-streaming
This feature allows you to generate a MPEG-DASH (.mpd) file for Adaptive  bitrate video-streaming.

As soon as you define transformations based on the bitrates in thumbnail config, the `.mpd` file will be generated with bitrate streams. 
The `.mpd` file will be referenced in  generated `<video>` Tag.

However, you have to include a polyfill for all major browsers to support Adaptive  bitrate video-streaming: https://github.com/Dash-Industry-Forum/dash.js
```php
{{ pimcore_video('campaignVideo', {
        width: auto,
        height: auto,
        thumbnail: 'new',
    }) }}
```
generates frontend:
```html
<video width="100%" height="auto" controls="controls" class="pimcore_video" preload="auto" src="blob:http://xyz/01f91372-ddd8-4d3f-ac85-e420432d9704">
    <source type="video/mp4" src="/videodata/video-thumb__955__campaignVideo/Volkswagen-Van.mp4">
    <source type="application/dash+xml" src="/videodata/video-thumb__955__campaignVideo/Volkswagen-Van.mpd">
</video>
```

### Using with the Video Editable
Please have a look at [Video Editable](../../03_Documents/01_Editables/38_Video.md). 
