# Amazon AWS S3 Setup

Saving certain files in a S3 bucket is only necessary if you're running in cluster mode. If you have only one EC2 instance 
in one zone, we recommend storing the data on an EBS volume instead (performance). 

There's also a new product called Amazon EFS (in preview as of May, 30th), which is a NFS share also across regions - 
this sounds like a very promising alternative for S3 for a Pimcore setup but we didn't had the chance yet to test it out 
in detail.
 
##  Setup your S3 Bucket

We recommend to use a dedicated S3 bucket, or even 2 different buckets, one for private data (versions, recycle bin, ...) 
and one for public accessible data (assets, thumbnails). 

But it's up to you whether you use an existing, a new or multiple buckets. But it's important to set the right permissions, 
`/assets` and `/tmp` need public access to work properly (they'll be referenced on your frontend).
 
Of course you'll need an AWS access key and a secret (IAM user) for the following steps.


## Install AWS Client Libraries

Run the following command in your pimcore document root:
 
```bash
composer require aws/aws-sdk-php
```

## Create a Custom [`startup.php`](../../../10_Extending_Pimcore/09_Hook_into_the_Startup_Process.md)

Create a new file (or re-use existing) with the following code in `/website/config/startup.php`. 

Please read the comments in the following code to better understand what it does and how to customize the settings. 

```php
<?php
 
use Aws\S3\S3Client;
 
// setup aws s3 client
$s3Client = new S3Client([
    'version'     => 'latest',
    'region'      => 'eu-central-1', // choose your favorite region
    'credentials' => [
        // use your aws credentials
        'key'    => 'AKIAJOAFDIFXXXXXXXXXX',
        'secret' => 'uw7fGn0if9KvQR09O+n7E8+XXXXXXXXXX',
    ],
]);
 
$s3Client->registerStreamWrapper();
 
// set default file context
\Pimcore\File::setContext(stream_context_create([
    's3' => ['seekable' => true]
]));
 
$s3BaseUrl = "https://s3.eu-central-1.amazonaws.com";
$s3TmpUrlPrefix = $s3BaseUrl . str_replace("s3:/", "", PIMCORE_TEMPORARY_DIRECTORY);
$s3AssetUrlPrefix = $s3BaseUrl . str_replace("s3:/", "", PIMCORE_ASSET_DIRECTORY);
 
// rewrite some paths in the PIMCORE_TEMPORARY_DIRECTORY folder
\Pimcore::getEventManager()->attach([
        "frontend.path.asset.image.thumbnail",
        "frontend.path.asset.document.image-thumbnail",
        "frontend.path.asset.video.image-thumbnail",
        "frontend.path.asset.video.thumbnail",
    ],
    function ($event) use ($s3TmpUrlPrefix) {
        // rewrite the path for the frontend
        $fileSystemPath = $event->getParam("filesystemPath");
 
        $cacheKey = "thumb_s3_" . md5($fileSystemPath);
        $path = \Pimcore\Cache::load($cacheKey);
 
        if(!$path) {
            if(!file_exists($fileSystemPath)) {
                // the thumbnail doesn't exist yet, so we need to create it on request -> Thumbnail controller plugin
                $path = str_replace(PIMCORE_TEMPORARY_DIRECTORY, "", $fileSystemPath);
            } else {
                $path = str_replace(PIMCORE_TEMPORARY_DIRECTORY . "/", $s3TmpUrlPrefix . "/", $fileSystemPath);
            }
 
            \Pimcore\Cache::save($path, $cacheKey, [], null, 0, true);
        }
 
        return $path;
});
 
\Pimcore::getEventManager()->attach([
        "asset.image.thumbnail",
        "asset.video.image-thumbnail",
        "asset.document.image-thumbnail"
    ],
    function ($event) {
        $thumbnail = $event->getTarget();
        $fsPath = $thumbnail->getFileSystemPath();
        if($fsPath && $event->getParam("generated")) {
            $cacheKey = "thumb_s3_" . md5($fsPath);
            \Pimcore\Cache::remove($cacheKey);
        }
});
 
// rewrite main asset path
\Pimcore::getEventManager()->attach("frontend.path.asset", function ($event) use ($s3AssetUrlPrefix) {
    $asset = $event->getTarget();
    $path = str_replace(PIMCORE_ASSET_DIRECTORY . "/", $s3AssetUrlPrefix . "/", $asset->getFileSystemPath());
    return $path;
});
```

## Customize the Storage Locations of Pimcore

Create a new file names `/constants.php` in your document root and put the following code in it.

Again, please have a look at the comments in the code.
 
```php
<?php
 
$s3BucketName = "pimcore-demo"; // this needs to be changed to the name of your S3 bucket
$s3FileWrapperPrefix = "s3://" . $s3BucketName; // do NOT change
 
// with this you can individualize the storage path of each entity in pimcore
// you can of course keep some data locally and some data in a S3 bucket - it's completely up to you
// please remember that you have to migrate existing contents manually if you have existing contents
 
// the following 2 paths need configured public access in your bucket
define("PIMCORE_ASSET_DIRECTORY", $s3FileWrapperPrefix . "/assets");
define("PIMCORE_TEMPORARY_DIRECTORY", $s3FileWrapperPrefix . "/tmp");
 
// the following paths should be private!
define("PIMCORE_VERSION_DIRECTORY", $s3FileWrapperPrefix . "/versions");
define("PIMCORE_RECYCLEBIN_DIRECTORY", $s3FileWrapperPrefix . "/recyclebin");
define("PIMCORE_BACKUP_DIRECTORY", $s3FileWrapperPrefix . "/backup");
define("PIMCORE_LOG_MAIL_PERMANENT", $s3FileWrapperPrefix . "/email");
define("PIMCORE_LOG_FILEOBJECT_DIRECTORY", $s3FileWrapperPrefix . "/fileobjects");
```


## Migrate Existing Content
If you have existing contents in your Pimcore installation, you have to migrate this content manually to your S3 bucket 
according to your custom configuration above. 

You can use the CLI tool [s3cmd](http://s3tools.org/) for this task.

```bash
s3cmd sync --recursive /var/www/website/var/assets/ s3://pimcore-demo/assets/
// ... also for all other contents you'd like to have on S3
```