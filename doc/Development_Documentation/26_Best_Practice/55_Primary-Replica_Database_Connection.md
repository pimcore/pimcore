
# How to configure Pimcore to use a Primary/Replica Database Connection 
**IMPORTANT**: Please be aware that the primary/replica connection can only be used for a clustered MariaDB/MySQL environment, **NOT** 
for a primary/replica server setup! Due to the extensive multi-layered, consistent and tagged caching of Pimcore
it is necessary that Pimcore always has access to the latest data in the database. Due to the asynchronous nature 
of the primary/replica setup, this isn't ensured for that.

Note: Doctrine\DBAL versions older than 2.11 uses master/slave terminology.

### Create a Project specific Database Connection Class 

Create a new class at `src\AppBundle\Db\Connection.php`, with the following content: 

```php
<?php

namespace AppBundle\Db;

use Pimcore\Db\PimcoreExtensionsTrait;
use Pimcore\Db\ConnectionInterface;

class Connection extends \Doctrine\DBAL\Connections\PrimaryReadReplicaConnection implements ConnectionInterface
{
    use PimcoreExtensionsTrait;

    public function connect($connectionName = null)
    {
        $returnValue = parent::connect($connectionName);

        if ($returnValue) {
            $this->_conn->query('SET default_storage_engine=InnoDB;');
            $this->_conn->query("SET sql_mode = '';");
        }

        return $returnValue;
    }
}
```


### Configure the Primary/Replica Connection

The main database connection which you have configured for Pimcore is always the primary connection. 
Then you can add as many replica hosts as you like, in the following example there's just one replica host, 
where only the host is different (in our case `replica1`), all other options are reused from the primary connection. 

```yml
doctrine:
    dbal:
        connections:
            default:
                wrapper_class: '\AppBundle\Db\Connection'
                replicas:
                    replica1:
                          host: 'replica1'
                          port: 3306
                          dbname: dbname
                          user: username
                          password: password
                          charset: UTF8MB4
```
