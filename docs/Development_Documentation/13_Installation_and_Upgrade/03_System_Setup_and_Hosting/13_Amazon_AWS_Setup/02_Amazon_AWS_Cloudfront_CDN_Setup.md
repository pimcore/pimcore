# Amazon AWS Cloudfront CDN Setup

## When using S3 as Storage Backend

Change the rewriting in your [startup.php](./03_Amazon_AWS_S3_Setup.md) according to your cloudfront hostname 
(frontend.path.asset.image.thumbnail event). 

Basically it's just setting a different hostname for the variable `$s3AssetUrlPrefix`.

```php
$s3AssetUrlPrefix = "https://d2m10exxxxx123.cloudfront.net";
```

Configure your cloudfront distribution to use S3 as the origin (your-bucket.s3.amazonaws.com). 


## Using Local Storage or EBS

Create a distribution with a custom origin domain name - just use your main host name (no matter if using a AWS backend 
or a custom server solution).
 
### Rewrite Paths of Pimcore Resources

This is very similar to the S3 setup. Basically we use the event api to rewrite the path of `/asset` and `/tmp` resources 
according to the cloudfront domain name. 

To do so we have to create/add a custom [startup.php](../../../10_Extending_Pimcore/09_Hook_into_the_Startup_Process.md) 
with the following script:
 
```php
<?php
 
$cloudFrontPrefix = "https://d2m10exxxxx123.cloudfront.net";
 
if (!\Pimcore::inAdmin() && !\Pimcore\Tool::isFrontentRequestByAdmin()) {
    \Pimcore::getEventManager()->attach([
        "frontend.path.asset.image.thumbnail",
        "frontend.path.asset.document.image-thumbnail",
        "frontend.path.asset.video.image-thumbnail",
        "frontend.path.asset.video.thumbnail",
    ],
        function ($event) use ($cloudFrontPrefix) {
            // rewrite the path for the frontend
            $fileSystemPath = $event->getParam("filesystemPath");
            $target = $event->getTarget();
            $fileModTime = null;
            if($target instanceof \Pimcore\Model\Asset) {
                $fileModTime = $target->getModificationDate();
            } elseif (method_exists($target, "getAsset") && $target->getAsset()) {
                $fileModTime = $target->getAsset()->getModificationDate();
            } elseif (file_exists($fileSystemPath)) {
                $fileModTime = filemtime($fileSystemPath);
            }
 
            $path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fileSystemPath);
            if($fileModTime) {
                $path = "/cache-buster-" . $fileModTime . $path; // add a cache-buster
            }
            $path = $cloudFrontPrefix . $path;
 
            return $path;
        });
 
    \Pimcore::getEventManager()->attach("frontend.path.asset", function ($event) use ($cloudFrontPrefix) {
        $asset = $event->getTarget();
        $path = $asset->getRealFullPath();
        $path = "/cache-buster-" . $asset->getModificationDate() . $path; // add a cache-buster
        $path = $cloudFrontPrefix . $path;
 
        return $path;
    });
}
```

## Your Custom Static Data

### If using S3
You have to create another cloudfront distribution, with a custom origin that points to your main domain. 

### If using Local Storage, EBS or not an AWS backend
In this case you have already a custom origin, which can be reused for your custom statics as well.
 
### Rewriting Paths of HeadLink and HeadScript Helpers
Add the following to your startup.php:

```php
\Pimcore::getEventManager()->attach("frontend.view.helper.head-link", function (\Zend_EventManager_Event $event) {
    $item = $event->getParam("item");
 
    if (isset($item->href)) {
        $item->href = "https://d2m10exxxxx123.cloudfront.net" . $item->href;
    }
});
 
\Pimcore::getEventManager()->attach("frontend.view.helper.head-script", function (\Zend_EventManager_Event $event) {
    $item = $event->getParam("item");
 
    if (is_array($item->attributes)) {
        if (isset($item->attributes["src"])) {
            $item->attributes["src"] = "https://d2m10exxxxx123.cloudfront.net" . $item->attributes["src"];
        }
    }
});
```
