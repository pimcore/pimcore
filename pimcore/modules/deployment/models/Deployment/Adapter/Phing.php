<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 10.05.13
 * Time: 08:58
 */

class Deployment_Adapter_Phing extends Deployment_Adapter_Abstract{

    protected $phingArgvParams = array('help','h',
                                       'list','l',
                                       'version','v',
                                       'quiet','q',
                                       'verbose',
                                       'debug',
                                       'longtargets',
                                       'logfile',
                                       'logger',
                                       'buildfile','f',
                                       'D',
                                       'propertyfile',
                                       'find',
                                       'inputhandler',
    );


    public function setCommandLineParams(){
        $customOptions = Pimcore_Tool_Console::getOptions(true);

        foreach($customOptions as $key => $value){
            $param = new Deployment_Classes_Phing_Param();
            $param->setname($key);
            $param->setValue($value);
            $this->commandLineParams[] = $param;
        }
    }

    /**
     * Removes invalid params from $argv - otherwise Phing would exit with an error
     *
     * @param $argv
     * @return array
     */
    public function getCleanedArgv($argv){
        $customOptions = Pimcore_Tool_Console::getOptions(true);
        $cleanedArgv = array($argv[0],$argv[1]);

        for($i = 2;$i < count($argv);$i++){
            $isPhingParam = false;
            foreach($this->phingArgvParams as $param){
                if(preg_match("/^(--?".$param.")|.*phing.*/",$argv[$i])){
                    $isPhingParam = true;
                }
            }
            if($isPhingParam){
                $cleanedArgv[] = $argv[$i];
            }
        }

        return $cleanedArgv;
    }

    public static function getDefaultBuildFile(){
        $buildXml = PIMCORE_CONFIGURATION_DIRECTORY . '/deployment/phing/build.xml';
        if(!is_readable($buildXml)){
            throw new Exception("Build file not found: $buildXml");
        }
        return $buildXml;
    }

    public static function getBinary(){
        $binary = PIMCORE_PATH . '/modules/deployment/lib/Deployment/Phing/bin/phing';
        if(Pimcore_Tool_Console::getSystemEnvironment() == 'windows'){
            $binary .= '.bat';
        }
        return $binary;
    }

    public function executeTask(){
        $opts = Pimcore_Tool_Console::getOptions();

        if(!$opts['target']){
            throw new Exception("'target' is not specified");
        }

        //add phpCliPath to get phing to run also if environment variable "php" is not set
        $cmd = Pimcore_Tool_Console::getPhpCli() .' ' . self::getBinary(). ' ' . $opts['target'] .' ';

        if(!$opts['buildfile']){
            $opts['buildfile'] = self::getDefaultBuildFile();
        }

        /*
         * phing only supports white-space separated arguments eg. buildfile /xyz/build.xml and not buildfile='/xyz/build.xml'
         * pimcore params are defined like --ckogler='hallo'
         *
         */
        foreach($opts as $key => $value){
            if(array_search($key,$this->phingArgvParams) !== false){
                $cmd .= ' -'.$key.' ' . $value;
            }else{
                $cmd .= ' --' . $key;
                if($value){
                    $cmd .= "='" . $value."'";
                }
                $cmd .= ' ';
            }
        }
        #$logFile = Deployment_Helper_General::getDefaultLogFile();

        system($cmd);
    }

}