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
        $r = call_user_func_array(array($this->getResource(), $method), $args);
        return $r;
    }
}
