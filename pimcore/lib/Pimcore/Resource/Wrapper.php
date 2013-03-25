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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Resource_Wrapper {

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $resource;

    /**
     * use a seperate connection for DDL queries to avoid implicit commits
     * @var Zend_Db_Adapter_Abstract
     */
    protected $DDLResource;

    /**
     * @param \Zend_Db_Adapter_Abstract $DDLResource
     */
    public function setDDLResource($DDLResource)
    {
        $this->DDLResource = $DDLResource;
        return $this;
    }

    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    public function getDDLResource()
    {
        if(!$this->DDLResource) {
            // get the Zend_Db_Adapter_Abstract not the wrapper
            $this->DDLResource = Pimcore_Resource::getConnection(true);
        }
        return $this->DDLResource;
    }

    /**
     *
     */
    public function closeDDLResource() {
        if($this->DDLResource) {
            try {
                Logger::debug("closing mysql connection with ID: " . $this->DDLResource->fetchOne("SELECT CONNECTION_ID()"));
                $this->DDLResource->closeConnection();
                $this->DDLResource = null;
            } catch (\Exception $e) {
                // this is the case when the mysql connection has gone away (eg. when forking using pcntl)
                Logger::info($e);
            }
        }
    }

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
     * @return Zend_Db_Adapter_Abstract
     */
    public function getResource()
    {
        if(!$this->resource) {
            // get the Zend_Db_Adapter_Abstract not the wrapper
            $this->resource = Pimcore_Resource::getConnection(true);
        }
        return $this->resource;
    }

    /**
     *
     */
    public function closeResource() {
        if($this->resource) {
            try {
                Logger::debug("closing mysql connection with ID: " . $this->resource->fetchOne("SELECT CONNECTION_ID()"));
                $this->resource->closeConnection();
                $this->resource = null;
            } catch (\Exception $e) {
                // this is the case when the mysql connection has gone away (eg. when forking using pcntl)
                Logger::info($e);
            }
        }
    }

    /**
     * @throws Exception
     * @param  $method
     * @param  $args
     * @return mixed
     */
    public function __call($method, $args) {
        try {

            // this wrapper allows you to append "try" to every method (eg. tryInsert()) to disable error logging in
            // Pimcore_Resource::errorHandler(). This is especially useful if you try to insert contents first, if that
            // fails try to update them. This is currently used in documents, assets, backend search, object folder, ...
            /*
             * ##### EXAMPLE #####
             try {
                 $this->db->tryInsert("documents", $data);
             }
             catch (Exception $e) {
                 $this->db->update("documents", $data, $this->db->quoteInto("id = ?", $this->model->getId() ));
             }
             */
            $logError = true;
            if(strpos($method, "try") === 0) {
                $method = preg_replace("/^try/", "", $method);
                lcfirst($method);
                $logError = false;
            }

            $r = $this->callResourceMethod($method, $args);
            return $r;
        }
        catch (Exception $e) {
            return Pimcore_Resource::errorHandler($method, $args, $e, $logError);
        }
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function callResourceMethod ($method, $args) {

        $isDDLQuery = false;
        $resource = $this->getResource();
        if($method == "query" && Pimcore_Resource::isDDLQuery($args[0])) {
            $resource = $this->getDDLResource();
            $isDDLQuery = true;
        }

        $capture = false;

        if(Pimcore::inAdmin()) {
            $methodsToCheck = array("query","update","delete","insert");
            if(in_array($method, $methodsToCheck)) {
                $capture = true;
                Pimcore_Resource::startCapturingDefinitionModifications($resource, $method, $args);
            }
        }

        $r = call_user_func_array(array($resource, $method), $args);

        if(Pimcore::inAdmin() && $capture) {
            Pimcore_Resource::stopCapturingDefinitionModifications($resource);
        }

        return $r;
    }
}
