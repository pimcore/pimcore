# Cluster Setup

A typical custer setup has the following architecture: 

![Pimcore_Cluster_Setup](../../img/cluster-setup.png) 

## Filesystem
It's absolutely necessary that the contents of `website/var/` are shared between all application servers.
Sharing has to be done using a real network filesystem (such as NFS) supporting locks and so on. 

**Do not use `rsync` or other nasty solutions to share the data between the application servers**. 


## Database Cluster with Write-Only Connection 
Sometimes it is necessary to have a dedicated "write-only" database connection. A common case is for instance when Pimcore is running on a MyQSL/MariaDB Galera Cluster and you want to effectively avoid deadlocks. 
See also [Galera known limitations](https://mariadb.com/kb/en/mariadb/mariadb-galera-cluster-known-limitations/).  
 
To have a dedicated write connection you have to modify your `website/var/config/system.php` and add the following part to it:  

```php
"database" => [
    "adapter" => "Pdo_Mysql",
    "params" => [
        "username" => "root",
        "password" => "elements",
        "dbname" => "pimcore",
        "host" => "localhost",
        "port" => "3306"
    ],
  
    // now comes the interesting part:
    "writeOnly" => [
        "params" => [
            "username" => "root",
            "password" => "elements",
            "dbname" => "pimcore",
            "host" => "localhost",
            "port" => "3306"
        ]
    ]
],
```

Pimcore will now automatically use the 2nd configuration for data manipulation and DDL queries. 

