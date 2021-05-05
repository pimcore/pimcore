# File Storage Setup
Pimcore uses powerful & flexible file storage library, called [Flysystem](https://github.com/thephpleague/flysystem) for storing some content files like:
 - Assets
 - Assets cache
 - Image thumbnails
 - Video thumbnails
 - Versions
 - Recyclebin
 
 and so on.
 
To configure a custom file storage, just override any of the default definitions with specific adapter:

 ```yaml
flysystem:
    storages:
        pimcore.asset.storage:
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
            adapter: 'local'
            visibility: private
            options:
                directory: '%kernel.project_dir%/public/var/tmp/asset-cache'
        pimcore.thumbnail.storage:
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
            adapter: 'local'
            visibility: private
            options:
                directory: '%kernel.project_dir%/var/versions'
        pimcore.recycle_bin.storage:
            adapter: 'local'
            visibility: private
            options:
                directory: '%kernel.project_dir%/var/recyclebin'
        pimcore.admin.storage:
            adapter: 'local'
            visibility: private
            options:
                directory: '%kernel.project_dir%/var/admin'
```

You can explore all [official adapters](https://flysystem.thephpleague.com/v2/docs/adapter/local/) and [third party adapters](https://packagist.org/?query=flysystem%20adapter) to use custom file storage.

Depending on your storage (e.g. if using S3), it can be necessary to change the frontend path of assets and thumbnails, which can be done using the following configs:

 ```yaml
pimcore:
    assets:
        frontend_prefixes:
            source: https://oreo-12345678990.cloudfront.net/asset
            thumbnail: https://tavi-12345678990.cloudfront.net/thumbnail
            thumbnail_deferred: /deferred-thumbnail
```

### Example: Aws S3 Adapter for Assets
```yaml
# config/packages/prod/flysystem.yaml
services:
    assets_s3:
        class: 'Aws\S3\S3Client'
        arguments:
            -  endpoint: 'https://s3.eu-central-1.amazonaws.com'
               region: 'eu-central-1',
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
