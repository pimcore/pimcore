<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Tool;
use Pimcore\File;
use Pimcore\Db;
use Pimcore\Model\Translation;

class Admin_MiscController extends \Pimcore\Controller\Action\Admin
{

    public function getAvailableModulesAction()
    {
        $system_modules = [
            "searchadmin", "reports", "webservice", "admin", "update", "install", "extensionmanager"
        ];
        $modules = [];
        $front = $this->getFrontController();
        foreach ($front->getControllerDirectory() as $module => $path) {
            if (in_array($module, $system_modules)) {
                continue;
            }
            $modules[] = ["name" => $module];
        }
        $this->_helper->json([
            "data" => $modules
        ]);
    }

    /**
     * page & snippet controller/action/template selector store providers
     */

    public function getAvailableControllersAction()
    {
        $controllers = [];
        $controllerDir = $this->getControllerDir();
        $controllerFiles = rscandir($controllerDir);
        foreach ($controllerFiles as $file) {
            $file = str_replace($controllerDir, "", $file);
            $dat = [];
            if (strpos($file, ".php") !== false) {
                $file = lcfirst(str_replace("Controller.php", "", $file));
                $file = strtolower(preg_replace("/[A-Z]/", "-\\0", $file));
                $dat["name"] = str_replace("/-", "_", $file);
                $controllers[] = $dat;
            }
        }

        $this->_helper->json([
            "data" => $controllers
        ]);
    }

    public function getAvailableActionsAction()
    {
        $actions = [];
        $controller = $this->getParam("controllerName");
        $controllerClass = str_replace("-", " ", $controller);
        $controllerClass = str_replace(" ", "", ucwords($controllerClass));
        $reflectionClass = "{$controllerClass}Controller";
        $controllerClass = preg_replace_callback("/([_])([a-z])/i", function ($matches) {
            return "/" . strtoupper($matches[2]);
        }, $controllerClass);

        $controllerDir = $this->getControllerDir();
        $controllerFile = $controllerDir . $controllerClass . "Controller.php";
        if (is_file($controllerFile)) {
            require_once $controllerFile;

            if (!Tool::classExists($reflectionClass)) {
                if ($this->getParam('moduleName')) {
                    $reflectionClass = $this->getParam('moduleName').'_'.$reflectionClass;
                }
            }

            if (Tool::classExists($reflectionClass)) {
                $oReflectionClass = new \ReflectionClass($reflectionClass);

                $methods = $oReflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
                $methods = array_filter(
                    $methods, function (\ReflectionMethod $method) {
                        return preg_match('/^([a-zA-Z0-9]+)Action$/', $method->getName());
                    });
                $actions = array_values(array_map(
                    function (\ReflectionMethod $method) {
                        $name = preg_replace('/Action$/', '', $method->getName());
                        $filter = new \Zend_Filter_Word_CamelCaseToDash();
                        $name = $filter->filter($name);
                        $name = strtolower($name);

                        return ["name" => $name];
                    }, $methods
                ));
            }
        }

        $this->_helper->json([
            "data" => $actions
        ]);
    }

    public function getAvailableTemplatesAction()
    {
        $templates = [];
        $viewPath = PIMCORE_WEBSITE_PATH . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "scripts";
        $files = rscandir($viewPath . DIRECTORY_SEPARATOR);
        foreach ($files as $file) {
            $dat = [];
            if (strpos($file, \Pimcore\View::getViewScriptSuffix()) !== false) {
                $dat["path"] = str_replace($viewPath, "", $file);
                $dat["path"] = str_replace("\\", "/", $dat["path"]); // unix directory separator are compatible with windows, not the reverse
                $templates[] = $dat;
            }
        }

        $this->_helper->json([
            "data" => $templates
        ]);
    }


    /**
     * Determines by the moduleName GET-parameter which controller directory
     * to use; the default (param empty or "website") or of the corresponding
     * module or plugin
     *
     * @return string
     * @throws Zend_Controller_Exception
     */
    private function getControllerDir()
    {
        $controllerDir = PIMCORE_WEBSITE_PATH . DIRECTORY_SEPARATOR . "controllers" . DIRECTORY_SEPARATOR;
        if ($module = $this->getParam("moduleName")) {
            if ($module != "" && $module != "website") { // => not the default
                $front   = $this->getFrontController();
                $modules = $front->getControllerDirectory();
                if (array_key_exists($module, $modules)) {
                    $controllerDir = $modules[$module] . DIRECTORY_SEPARATOR;
                }
            }
        }

        return $controllerDir;
    }
}
