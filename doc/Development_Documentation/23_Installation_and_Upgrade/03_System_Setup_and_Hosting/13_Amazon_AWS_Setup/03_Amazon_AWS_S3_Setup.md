# Amazon AWS S3 Setup

Saving certain files in a S3 bucket is only necessary if you're running in cluster mode. If you have only one EC2 instance 
in one zone, we recommend storing the data on an EBS volume instead (performance).

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

## Create a Custom `startup.php`

Create a new file (or re-use existing) with the following code in `/app/startup.php`. 

Please read the comments in the following code to better understand what it does and how to customize the settings. 

```php
<?php

use Aws\S3\S3Client;

$s3Client = new S3Client([
    'version' => 'latest',
    'region' => 'us-east-2', // choose your favorite region
    'credentials' => [
        // use your aws credentials
        'key' => 'AKIAJOAFDIFXXXXXXXXXX',
        'secret' => 'uw7fGn0if9KvQR09O+n7E8+XXXXXXXXXX',
    ],
]);

$s3Client->registerStreamWrapper();

// set default file context
\Pimcore\File::setContext(stream_context_create([
    's3' => ['seekable' => true]
]));

```

## Configure the Event Listeners

Create a new file (or re-use existing) with the following code in `/app/config/services.yml`.

```yaml

services:
    AppBundle\EventListener\S3Listener: ~
```

## Add a class to respond to the events

Create a new file named `/S3Listener.php` with the following code in your bundle source: `/src/CommonBundle/EventListener/S3Listener.php`

Again, please have a look at the comments in the code.

```php
<?php

namespace AppBundle\EventListener;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class S3Listener implements EventSubscriberInterface
{
    public function __construct()
    {
        // you have to customize this if you'd like to deliver your assets/thumbnails in your S3 bucket by CloudFront
        $this->s3BaseUrl = "https://s3.us-east-2.amazonaws.com";
        $this->s3TmpUrlPrefix = $this->s3BaseUrl . str_replace("s3:/", "", PIMCORE_TEMPORARY_DIRECTORY);
        $this->s3AssetUrlPrefix = $this->s3BaseUrl . str_replace("s3:/", "", PIMCORE_ASSET_DIRECTORY);
    }

    public static function getSubscribedEvents()
    {
        return [
            FrontendEvents::ASSET_IMAGE_THUMBNAIL => 'onFrontendPathThumbnail',
            FrontendEvents::ASSET_DOCUMENT_IMAGE_THUMBNAIL => 'onFrontendPathThumbnail',
            FrontendEvents::ASSET_VIDEO_IMAGE_THUMBNAIL => 'onFrontendPathThumbnail',
            FrontendEvents::ASSET_VIDEO_THUMBNAIL => 'onFrontendPathThumbnail',
            FrontendEvents::ASSET_PATH => 'onFrontEndPathAsset',
            AssetEvents::IMAGE_THUMBNAIL => 'onAssetThumbnailCreated',
            AssetEvents::VIDEO_IMAGE_THUMBNAIL => 'onAssetThumbnailCreated',
            AssetEvents::DOCUMENT_IMAGE_THUMBNAIL => 'onAssetThumbnailCreated',
        ];
    }

    public function onFrontendPathThumbnail(GenericEvent $event) {
        // rewrite the path for the frontend
        $fileSystemPath = $event->getSubject()->getFileSystemPath();

        $cacheKey = "thumb_s3_" . md5($fileSystemPath);
        $path = \Pimcore\Cache::load($cacheKey);

        if(!$path) {
            if(!file_exists($fileSystemPath)) {
                // the thumbnail doesn't exist yet, so we need to create it on request -> Thumbnail controller plugin
                $path = str_replace(PIMCORE_TEMPORARY_DIRECTORY."/image-thumbnails", "", $fileSystemPath);
            } else {
                $path = str_replace(PIMCORE_TEMPORARY_DIRECTORY . "/", $this->s3TmpUrlPrefix . "/", $fileSystemPath);
            }
        }

        $event->setArgument('frontendPath',$path);
    }

    public function onAssetThumbnailCreated(GenericEvent $event)
    {
        $thumbnail = $event->getSubject();

        $fsPath = $thumbnail->getFileSystemPath();

        if ($fsPath && $event->getArgument("generated")) {
            $cacheKey = "thumb_s3_" . md5($fsPath);

            \Pimcore\Cache::remove($cacheKey);
        }
    }

    public function onFrontEndPathAsset(GenericEvent $event) {
        $asset = $event->getSubject();
        $path = str_replace(PIMCORE_ASSET_DIRECTORY . "/", $this->s3AssetUrlPrefix . "/", $asset->getFileSystemPath());

        $event->setArgument('frontendPath',$path);
    }
}
```


## Customize the Storage Locations of Pimcore

Create a new file named `/constants.php` in `/app/constants.php` and put the following code in it.

Again, please have a look at the comments in the code.
 
```php
<?php

$s3BaseUrl = "https://s3.us-east-2.amazonaws.com";
$s3BucketName = "pimcore-demo"; // this needs to be changed to the name of your S3 bucket
$s3FileWrapperPrefix = "s3://" . $s3BucketName; // do NOT change
 
// with this you can individualize the storage path of each entity in pimcore
// you can of course keep some data locally and some data in a S3 bucket - it's completely up to you
// please remember that you have to migrate existing contents manually if you have existing contents
 
// the following 2 paths need configured public access in your bucket
define("PIMCORE_ASSET_DIRECTORY", $s3FileWrapperPrefix . "/assets");
define("PIMCORE_TEMPORARY_DIRECTORY", $s3FileWrapperPrefix . "/tmp");

// constants for reference in the views
define("PIMCORE_TRANSFORMED_ASSET_URL", $s3BaseUrl . "/" . $s3BucketName . "/assets");

// the following paths should be private!
define("PIMCORE_VERSION_DIRECTORY", $s3FileWrapperPrefix . "/versions");
define("PIMCORE_RECYCLEBIN_DIRECTORY", $s3FileWrapperPrefix . "/recyclebin");
define("PIMCORE_LOG_MAIL_PERMANENT", $s3FileWrapperPrefix . "/email");
define("PIMCORE_LOG_FILEOBJECT_DIRECTORY", $s3FileWrapperPrefix . "/fileobjects");
```

## Migrate Existing Content
If you have existing contents in your Pimcore installation, you have to migrate this content manually to your S3 bucket 
according to your custom configuration above. 

You can use the CLI tool [s3cmd](http://s3tools.org/) for this task.

```bash
s3cmd sync --recursive /var/www/website/web/var/assets/ s3://pimcore-demo/assets/
// ... also for all other contents you'd like to have on S3
```
