# Custom Cache Backends

Pimcore uses by default the `Pimcore\Cache\Backend\MysqlTable` backend for caching. This backend 
isn't very powerful and speedy particular when there is a huge amount of items to cache.

You can use every implementation of `Zend_Cache_Backend` which supports tags, you can also use 
your own cache backend.

## Enable a Custom Cache
To enable a custom cache backend you have to create a new configuration file: `/website/config/cache.php`, 
there is already a example cache configuration in `/website/config/cache.example.php`. 

### Redis Backend (recommended)
Note: Requires the phpredis PHP Extension and the Redis Key-Value Store. Precompiled Binaries for 
Debian (and Debian-based distributions) can be found at the dotdeb Repositories. If you use 
phpredis as Session-Storage, keep in Mind that db 0 is already in use.

```php
<?php
return [
    "backend" => [
        "type" => "\\Pimcore\\Cache\\Backend\\Redis2",
        "custom" => "true",
        "options" => [
            "server" => "127.0.0.1",
            "port" => "6379",
            "persistent" => "1",
            "database" => "8",
            "use_lua" => "1"
        ]
    ]
];
```

##### Recommended Redis Configuration (redis.conf): 
```
maxmemory 1gb # depending on your data
maxmemory-policy allkeys-lru
save ""
```

## Setup a Custom Cache Frontend (e.g. for redis with id-prefix)
You can also define a custom cache frontend, this can be also done in the `cache.php`

```php
<?php
 
return [
    "frontend" => [
        "type" => "Core",
        "options" => [
            "cache_id_prefix" => "ax_",
            "lifetime" => "99999",
            "automatic_serialization" => "true"
        ]
    ],
    "backend" => [
        "type" => "\\Pimcore\\Cache\\Backend\\Redis2",
        "custom" => "true",
        "options" => [
            "server" => "127.0.0.1",
            "port" => "6379",
            "persistent" => "1",
            "database" => "8",
            "use_lua" => "1"
        ]
    ]
];
```
  
