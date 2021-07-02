# File Storage Setup
Pimcore uses a powerful & flexible file storage library, called [Flysystem](https://github.com/thephpleague/flysystem) 
for storing all kind of content files like, assets, thumbnails, versioning data, ... and many more. 
 
To configure a custom file storage, just override any of the default definitions with specific adapter:

 ```yaml
flysystem:
    storages:
        pimcore.asset.storage:
            # Storage for asset source files, directory structure is equal to the asset tree structure
            adapter: 'local'
            visibility: public
            options:
                directory: '%kernel.project_dir%/public/var/assets'
                permissions:
                    file:
                        private: 0644
                    dir:
                        private: 0755
        pimcore.asset_cache.storage:
            # Storage for cached asset files, e.g. PDF and image files generated out of Office files or videos
            # which are then used by the thumbnail engine as source files
            adapter: 'local'
            visibility: private
            options:
                directory: '%kernel.project_dir%/public/var/tmp/asset-cache'
        pimcore.thumbnail.storage:
            # Storage for image and video thumbnails, directory structure is equal to the source asset tree
            adapter: 'local'
            visibility: public
            options:
                directory: '%kernel.project_dir%/public/var/tmp/thumbnails'
                permissions:
                    file:
                        private: 0644
                    dir:
                        private: 0755
        pimcore.version.storage:
            # Storage for serialized versioning data of documents/asset/data objects
            adapter: 'local'
            visibility: private
            options:
                directory: '%kernel.project_dir%/var/versions'
        pimcore.recycle_bin.storage:
            # Storage for serialized recycle bin data of documents/asset/data objects
            adapter: 'local'
            visibility: private
            options:
                directory: '%kernel.project_dir%/var/recyclebin'
        pimcore.admin.storage:
            # Storage for shared admin resources, such as the user avatar, custom logos, ...
            adapter: 'local'
            visibility: private
            options:
                directory: '%kernel.project_dir%/var/admin'
```

You can explore all [official adapters](https://flysystem.thephpleague.com/v2/docs/adapter/local/) and 
[third party adapters](https://packagist.org/?query=flysystem%20adapter) to use custom file storage.

Please keep in mind that all of those storages need to be shared between all computing nodes if running
on a clustered environment. Using the default `local` adapter is only working on single server environments.

Depending on your storage (e.g. if using S3), it can be also necessary to prefix the frontend path of assets 
and thumbnails, which can be done using the following configs:

 ```yaml
pimcore:
    assets:
        frontend_prefixes:
            # Prefix used for the original asset files
            source: https://oreo-12345678990.cloudfront.net/asset
            # Prefix used for all generated image & video thumbnails
            thumbnail: https://tavi-12345678990.cloudfront.net/thumbnail
            # Prefix used for the deferred thumbnail placeholder path. 
            # Thumbnails are usually generated on demand (if not configured differently), this 
            # prefix is used for thumbnails which were not yet generated and therefore are not 
            # available on the thumbnail storage yet. Usually it's not necessary to change this config.
            thumbnail_deferred: /deferred-thumbnail
```
This will add the configured prefix to the path of assets and thumbnails in the frontend context 
(e.g. your templates). 
So basically the path to `/Sample/Tavi.jpg` will change to
`https://tavi-12345678990.cloudfront.net/asset/Sample/Tavi.jpg` 
and `/Sample/image-thumb__362__galleryThumbnail/Tavi.jpg` changes to
`https://tavi-12345678990.cloudfront.net/thumbnail/Sample/image-thumb__362__galleryThumbnail/Tavi.jpg`

This is especially useful if using an object storage that is publicly accessible or if using a CDN 
like CloudFront for your resources. 

### Example: AWS S3 Adapter for Assets
```yaml
# config/packages/prod/flysystem.yaml
services:
    assets_s3:
        class: 'Aws\S3\S3Client'
        arguments:
            -  endpoint: 'https://s3.eu-central-1.amazonaws.com'
               region: 'eu-central-1'
               version: 'latest'
               credentials:
                   key: '%env(S3_STORAGE_KEY)%'
                   secret: '%env(S3_STORAGE_SECRET)%'

flysystem:
    storages:
        pimcore.asset.storage:
            adapter: 'aws'
            visibility: public
            options:
                client: 'assets_s3'
                bucket: 'bucket-name'
```
