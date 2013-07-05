<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 10.05.13
 * Time: 09:22
 */

abstract class Deployment_Adapter_Abstract
{
    /**
     * @var
     */
    protected static $instance;

    /**
     * @var array
     */
    protected $commandLineParams = array();

    protected function __construct(){}

    protected function __clone(){}

    public static function getInstance()
    {
        if (!static::$instance) {
            $config = Deployment_Factory::getInstance()->getConfig();
            if (Pimcore_Tool::classExists($config->adapter)) {
                $adapter = new $config->adapter();
                if ($adapter instanceof self) {
                    static::$instance = $adapter;
                } else {
                    throw new Exception("The adapter has to be an instance of Deployment_Adapter_Abstract");
                }
            } else {
                throw new Exception("Could't find deployment adapter class: {$config->adapter}");
            }
        }
        return static::$instance;
    }

    public function getCommandLineParams(){
        return $this->commandLineParams;
    }

    /**
     * has to reset $argv so that only valid cli params are passed to the deployment application
     * @return mixed
     */
    public abstract function getCleanedArgv($argv);

    public abstract function setCommandLineParams();

    public abstract function executeTask();
}