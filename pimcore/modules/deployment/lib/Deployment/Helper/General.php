<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 06.07.13
 * Time: 13:27
 */

class Deployment_Helper_General {

    public static $tasksParamDefinitionFiles = array();//to add custom definition files if required
    protected static $taskParamDefinitions;

    public static function getInstanceIdentifier(){
        $systemConfig = Pimcore_Config::getSystemConfig()->toArray();
        return $systemConfig['general']['instanceIdentifier'];
    }

    public static function getDefaultLogFile(){
        return PIMCORE_LOG_DIRECTORY.'/deployment.log';
    }

    /**
     * Checks if deployment is enabled
     *
     * @return bool
     */
    public static function isEnabled(){
        $config = self::getConfig();
        return $config->enabled;
    }

    /**
     * Returns the current environment system - false if no valid environment
     * @return bool | string
     */
    public static function getEnvironment(){
        $systemConfig = Pimcore_Config::getSystemConfig()->toArray();
        $environments = Deployment_Instance_Wrapper::getEnvironmentTypes();

        if($deploymentSettings = $systemConfig['deployment']){
            if(array_search($deploymentSettings['environment'],$environments)){
                return $deploymentSettings['environment'];
            }
        }
        return false;
    }

    public static function getConfig(){
        $deploymentConfigFile = PIMCORE_DEPLOYMENT_CONFIG_FILE;

        if (is_readable($deploymentConfigFile)) {
            try {
                return new Zend_Config_Xml($deploymentConfigFile);
            }catch (Exception $e){
                Logger::crit($e);
                Logger::crit("Couldn't load deployment configuration file $deploymentConfigFile");
                throw new Exception($e->getMessage());
            }
        }
        else{
            $message = "Couldn't read deployment configuration file $deploymentConfigFile";
            Logger::crit($message);
            throw new Exception($message);
        }
    }

    public static function getTaskParamDefinitions(){
        if(!self::$taskParamDefinitions){
            $configArray = array();
            $files = array_merge((array)self::$tasksParamDefinitionFiles,
                array(PIMCORE_DOCUMENT_ROOT . '/pimcore/config/deployment/phing/tasksParamDefinitions.xml',
                    PIMCORE_CONFIGURATION_DIRECTORY.'/deployment/phing/tasksParamDefinitions.xml')
            );
            foreach($files as $file){
                if(is_readable($file)){
                    try{
                        $config = new Zend_Config_Xml($file);
                        $configArray = array_merge($configArray,$config->toArray());
                    }catch (Exception $e){
                        throw new Exception("Couldn't add tasksParamDefinitions from file $file - invalid XML.");
                    }
                }else{
                    Logger::debug("tasksParamDefinitions file $file not fount - ignored.");
                }
            }
            self::$taskParamDefinitions = $configArray;
        }

        return self::$taskParamDefinitions;
    }

    public static function getValueByItemType($getter,$item){
        $propertyKey = lcfirst(str_replace('get','',$getter));
        if(method_exists($item,$getter)){
            $value = $item->$getter();
        }elseif(is_object($item)){
            $value = $item->$propertyKey;
        }elseif(is_array($item)){
            $value = $item[$propertyKey];
        }
        return $value;
    }
}