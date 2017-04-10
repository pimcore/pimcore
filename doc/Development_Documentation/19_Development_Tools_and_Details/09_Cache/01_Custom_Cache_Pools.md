# Custom Cache Pools

Pimcore uses a custom [`PSR-6`](http://www.php-fig.org/psr/psr-6/) implementation which is targeted to the heavy use of 
cache tagging used in core caching. Therefore a cache pool utilized by the `CoreHandlerInterface` needs to implement both
PSR-6 and [php-cache/tag-interop](https://github.com/php-cache/tag-interop). If implementing custom pools please make sure 
tags and items are written in a transactional way.

Pimcore ships with an adapter for [Symfony's Cache Component](http://symfony.com/doc/master/components/cache.html) which
you can use to get started quickly with a whole range of cache implementations. As example, see how the predefined filesystem
adapter which is used as fallback is defined:

```yaml
# pimcore/lib/Pimcore/Bundle/CoreBundle/Resources/config/cache.yml
services:
    # symfony filesystem cache adapter
    pimcore.cache.core.symfony_adapter.filesystem:
        class: Symfony\Component\Cache\Adapter\FilesystemAdapter
        arguments:
            - 'pimcore'
            - '%pimcore.cache.core.default_lifetime%'
            - '%kernel.cache_dir%/pimcore'
        calls:
            - [setLogger, ['@logger']]
        tags:
            - { name: monolog.logger, channel: cache }


    # filesystem cache pool using the symfony adapter
    # if the symfony adapter is no TagAwareAdapter, it will be automatically wrapped
    pimcore.cache.core.pool.filesystem:
        class: Pimcore\Cache\Pool\SymfonyAdapterProxy
        arguments:
            - '@pimcore.cache.core.symfony_adapter.filesystem'
            - '%pimcore.cache.core.default_lifetime%'
        calls:
            - [setLogger, ['@logger']]
        tags:
            - { name: monolog.logger, channel: cache }
```

If you want to start with a completely custom implementation, please see the following files as reference:

* [PimcoreCacheItemPoolInterface](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Cache/Pool/PimcoreCacheItemPoolInterface.php))
* [PimcoreCacheItemInterface](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Cache/Pool/PimcoreCacheItemInterface.php)
* [AbstractCacheItemPool](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Cache/Pool/AbstractCacheItemPool.php)

To use your custom pool, register it as service and update the configuration to use the custom pool:

```yaml
# services.yml
services:
    app.cache.custom_pool:
        class: AppBundle\Cache\CustomCachePool
```

```yaml
# config.yml
pimcore:
    cache:
        pool_service_id: app.cache.custom_pool
```
