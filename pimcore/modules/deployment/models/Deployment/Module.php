<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 06.07.13
 * Time: 11:11
 */

class Deployment_Module extends Pimcore_API_Module_Abstract {

    public function __construct(){
        parent::__construct();
        $this->init();
    }

    public function init(){
        $includePaths = array(
            PIMCORE_PATH . "/modules/deployment/models", //needs to be defined  - otherwise resourceclasses won't be loaded
            PIMCORE_PATH . "/modules/deployment/lib",
        );
        set_include_path(get_include_path() . implode(PATH_SEPARATOR, $includePaths));

        if(!Pimcore_Tool::isFrontend()){
            $routeWebservice = new Zend_Controller_Router_Route(
                'webservice-deployment/:controller/:action/*',
                array(
                    "module" => "deployment",
                    "controller" => "rest",
                    "action" => "index"
                )
            );
            Zend_Controller_Front::getInstance()->getRouter()->addRoute('webserviceDeployment', $routeWebservice);
        }
    }

    public static function getModulePath(){
        return dirname(dirname(__DIR__));
    }
}