
# How to configure Pimcore to use a Master/Slave Database Connection 
**IMPORTANT**: Please be aware that the master/slave connection can only be used for a clustered MariaDB/MySQL environment, **NOT** 
for a master/slave replication server setup! Due to the extensive multi-layered, consistent and tagged caching of Pimcore
it is necessary that Pimcore always has access to the latest data in the database. Due to the asynchronous nature 
of the master/slave replication, this isn't ensured for that. 

### Create a Project specific Database Connection Class 

Create a new class at `src\AppBundle\Db\Connection.php`, with the following content: 

```php
<?php

namespace AppBundle\Db;

use Pimcore\Db\PimcoreExtensionsTrait;
use Pimcore\Db\ConnectionInterface;

class Connection extends \Doctrine\DBAL\Connections\MasterSlaveConnection implements ConnectionInterface
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


### Configure the Master/Slave Connection

The main database connection which you have configured for Pimcore is always the master connection. 
Then you can add as many slave hosts as you like, in the following example there's just one slave host, 
where only the host is different (in our case `slave1`), all other options are reused from the master connection. 

```yml 
doctrine:
    dbal:
        connections:
            default:
                wrapper_class: '\AppBundle\Db\Connection'
                slaves:
                    slave1:
                          host: 'slave1'
                          port: '%pimcore_system_config.database.params.port%'
                          dbname: '%pimcore_system_config.database.params.dbname%'
                          user: '%pimcore_system_config.database.params.username%'
                          password: '%pimcore_system_config.database.params.password%'
                          charset: UTF8MB4
```