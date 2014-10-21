<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Resource;

use Pimcore\Resource;

class Wrapper {

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $resource;

    /**
     * use a seperate connection for DDL queries to avoid implicit commits
     * @var \Zend_Db_Adapter_Abstract
     */
    //protected $DDLResource;

    /**
     * @param \Zend_Db_Adapter_Abstract $DDLResource
     */
    /*public function setDDLResource($DDLResource)
    {
        $this->DDLResource = $DDLResource;
        return $this;
    }*/

    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    /*public function getDDLResource()
    {
        if(!$this->DDLResource) {
            // get the \Zend_Db_Adapter_Abstract not the wrapper
            $this->DDLResource = Pimcore_Resource::getConnection(true);
        }
        return $this->DDLResource;
    }*/

    /**
     *
     */
    /*public function closeDDLResource() {
        $this->closeConnectionResource($this->DDLResource);
        $this->DDLResource = null;
    }*/

    /**
     * @param $resource
     */
    public function __construct($resource = false) {
        if($resource) {
            $this->setResource($resource);
        }
    }

    /**
     * @param $resource
     * @return void
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    public function getResource()
    {
        if(!$this->resource) {
            // get the \Zend_Db_Adapter_Abstract not the wrapper
            $this->resource = Resource::getConnection(true);
        }
        return $this->resource;
    }

    /**
     *
     */
    public function closeResource() {
        $this->closeConnectionResource($this->resource);
        $this->resource = null;
    }

    /**
     * @param \Zend_Db_Adapter_Abstract $resource
     */
    protected function closeConnectionResource($resource) {
        if($resource) {
            try {
                $connectionId = null;

                // unfortunately mysqli doesn't throw an exception in the case the connection is lost (issues a warning)
                // and when sending a query to the broken connection (eg. when forking)
                // so we have to handle mysqli and pdo_mysql differently
                if($resource instanceof \Zend_Db_Adapter_Mysqli) {
                    if($resource->getConnection()) {
                        $connectionId = $resource->getConnection()->thread_id;
                    }
                } else if ($resource instanceof \Zend_Db_Adapter_Pdo_Mysql) {
                    $connectionId = $resource->fetchOne("SELECT CONNECTION_ID()");
                }
                \Logger::debug(get_class($resource) . ": closing MySQL-Server connection with ID: " . $connectionId);

                $resource->closeConnection();
            } catch (\Exception $e) {
                // this is the case when the mysql connection has gone away (eg. when forking using pcntl)
                \Logger::info($e);
            }
        }
    }


    /**
     * insert on dublicate key update extension to the \Zend_Db Adapter
     * @param $table
     * @param array $data
     * @return mixed
     * @throws \Zend_Db_Adapter_Exception
     */
    public function insertOrUpdate($table, array $data)
    {
        // extract and quote col names from the array keys
        $i = 0;
        $bind = array();
        $cols = array();
        $vals = array();
        foreach ($data as $col => $val) {
            $cols[] = $this->quoteIdentifier($col, true);
            if ($val instanceof \Zend_Db_Expr) {
                $vals[] = $val->__toString();
            } else {
                if ($this->supportsParameters('positional')) {
                    $vals[] = '?';
                    $bind[] = $val;
                } else {
                    if ($this->supportsParameters('named')) {
                        $bind[':col' . $i] = $val;
                        $vals[] = ':col' . $i;
                        $i++;
                    } else {
                        /** @see \Zend_Db_Adapter_Exception */
                        throw new \Zend_Db_Adapter_Exception(get_class($this->getResource()) . " doesn't support positional or named binding");
                    }
                }
            }
        }


        // build the statement
        $set = array();
        foreach ($cols as $i => $col) {
            $set[] = sprintf('%s = %s', $col, $vals[$i]);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s;',
            $this->quoteIdentifier($table, true),
            implode(', ', $cols),
            implode(', ', $vals),
            implode(', ', $set)
        );

        // execute the statement and return the number of affected rows
        if ($this->supportsParameters('positional')) {
            $bind = array_values($bind);
        }

        $bind = array_merge($bind, $bind);

        $stmt = $this->query($sql, $bind);
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * @throws \Exception
     * @param  $method
     * @param  $args
     * @return mixed
     */
    public function __call($method, $args) {
        try {
            $r = $this->callResourceMethod($method, $args);
            return $r;
        }
        catch (\Exception $e) {
            return Resource::errorHandler($method, $args, $e);
        }
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function callResourceMethod ($method, $args) {

        $resource = $this->getResource();
        /*if($method == "query" && Pimcore_Resource::isDDLQuery($args[0])) {
            $resource = $this->getDDLResource();
        }*/

        $capture = false;

        if(\Pimcore::inAdmin()) {
            $methodsToCheck = array("query","update","delete","insert");
            if(in_array($method, $methodsToCheck)) {
                $capture = true;
                Resource::startCapturingDefinitionModifications($resource, $method, $args);
            }
        }

        $r = call_user_func_array(array($resource, $method), $args);

        if(\Pimcore::inAdmin() && $capture) {
            Resource::stopCapturingDefinitionModifications($resource);
        }

        return $r;
    }
}
