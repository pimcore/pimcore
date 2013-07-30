<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 22.04.13
 * Time: 23:55
 */

class Deployment_Factory
{
    protected static $instance;
    protected static $config;
    protected static $adapter;
    protected static $instanceAdapter;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @return Deployment_Factory
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static;
            static::$config = Deployment_Helper_General::getConfig();
            static::$adapter = Deployment_Adapter_Abstract::getInstance();
            static::$instanceAdapter = Deployment_Instance_Adapter_Abstract::getInstance();
        }
        return static::$instance;
    }

    public function getAdapter()
    {
        return static::$adapter;
    }

    /**
     * @return Deployment_Instance_Adapter_Abstract
     */
    public function getInstanceAdapter()
    {
        return static::$instanceAdapter;
    }

    public function getConfig()
    {
        return static::$config;
    }
}