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
    }

    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    public function getDDLResource()
    {
        if(!$this->DDLResource) {
            // get the Zend_Db_Adapter_Abstract not the wrapper
            $this->DDLResource = Pimcore_Resource::getConnection()->getResource();
        }
        return $this->DDLResource;
    }

    /**
     * @param $resource
     */
    public function __construct($resource) {
        $this->setResource($resource);
    }

    /**
     * @param $resource
     * @return void
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    public function getResource()
    {
        return $this->resource;
    }

    
    /**
     * @throws Exception
     * @param  $method
     * @param  $args
     * @return mixed
     */
    public function __call($method, $args) {
        try {
            $r = $this->callResourceMethod($method, $args);
            return $r;
        }
        catch (Exception $e) {
            return Pimcore_Resource::errorHandler($method, $args, $e);
        }
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function callResourceMethod ($method, $args) {

        $capture = false;

        if(Pimcore::inAdmin()) {
            $methodsToCheck = array("query","update","delete","insert");
            if(in_array($method, $methodsToCheck)) {
                $capture = true;
                Pimcore_Resource::startCapturingDefinitionModifications($method, $args);
            }
        }

        $resource = $this->getResource();
        if($method == "query" && Pimcore_Resource::isDDLQuery($args[0])) {
            $resource = $this->getDDLResource();
        }

        $r = call_user_func_array(array($resource, $method), $args);

        if(Pimcore::inAdmin() && $capture) {
            Pimcore_Resource::stopCapturingDefinitionModifications();
        }

        return $r;
    }
}
