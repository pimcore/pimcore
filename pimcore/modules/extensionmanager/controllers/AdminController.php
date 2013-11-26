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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Extensionmanager_AdminController extends Pimcore_Controller_Action_Admin {

    public function init () {
        parent::init();

        $this->checkPermission("plugins");
    }

    public function getExtensionsAction () {

        $configurations = array();

        // plugins
        $pluginConfigs = Pimcore_ExtensionManager::getPluginConfigs();
        foreach ($pluginConfigs as $config) {
            $className = $config["plugin"]["pluginClassName"];
            $updateable = false;

            $revisionFile = PIMCORE_PLUGINS_PATH . "/" . $config["plugin"]["pluginName"] . "/.pimcore_extension_revision";
            if(is_file($revisionFile)) {
                $updateable = true;
            }
            
            if (!empty($className)) {
                $isEnabled = Pimcore_ExtensionManager::isEnabled("plugin", $config["plugin"]["pluginName"]);

                $plugin = array(
                    "id" => $config["plugin"]["pluginName"],
                    "type" => "plugin",
                    "name" => $config["plugin"]["pluginNiceName"],
                    "description" => $config["plugin"]["pluginDescription"],
                    "installed" => $isEnabled ? $className::isInstalled() : null,
                    "active" => $isEnabled,
                    "configuration" => $config["plugin"]["pluginIframeSrc"],
                    "updateable" => $updateable,
                    "version" => $config["plugin"]["pluginVersion"]  // NEU http://www.pimcore.org/issues/browse/PIMCORE-1947
                );

                if($config["plugin"]["pluginXmlEditorFile"] && is_readable(PIMCORE_DOCUMENT_ROOT . $config["plugin"]["pluginXmlEditorFile"])){
                    $plugin['xmlEditorFile'] = $config["plugin"]["pluginXmlEditorFile"];
                }

                $configurations[] = $plugin;
            }
        }

        // bricks
        $brickConfigs = Pimcore_ExtensionManager::getBrickConfigs();
        // get repo state of bricks
        foreach ($brickConfigs as $id => $config) {

            $updateable = false;
            
            $revisionFile = PIMCORE_WEBSITE_VAR . "/areas/" . $id . "/.pimcore_extension_revision";
            if(is_file($revisionFile)) {
                $updateable = true;
            }

            $isEnabled = Pimcore_ExtensionManager::isEnabled("brick", $id);
            $brick = array(
                "id" => $id,
                "type" => "brick",
                "name" => $config->name,
                "description" => $config->description,
                "installed" => true,
                "active" => $isEnabled,
                "updateable" => $updateable
            );
            $configurations[] = $brick;
        }

        $this->_helper->json(array("extensions" => $configurations));
    }

    public function toggleExtensionStateAction () {
        $type = $this->getParam("type");
        $id = $this->getParam("id");
        $method = $this->getParam("method");
        $reload = true;

        if($type && $id) {
            Pimcore_ExtensionManager::$method($type, $id);
        }

        // do not reload when toggle an area-brick
        if($type == "brick") {
            $reload = false;
        }

        $this->_helper->json(array("success" => true, "reload" => $reload));
    }


    public function installAction() {

        $type = $this->getParam("type");
        $id = $this->getParam("id");

        if($type == "plugin") {

            try {
                $config = Pimcore_ExtensionManager::getPluginConfig($id);
                $className = $config["plugin"]["pluginClassName"];

                $message = $className::install();

                $this->_helper->json(array(
                    "message" => $message,
                    "reload" => $className::needsReloadAfterInstall(),
                    "status" => array(
                        "installed" => $className::isInstalled()
                    ),
                    "success" => true
                ));
            } catch (Exception $e) {
                Logger::error($e);

                $this->_helper->json(array(
                    "message" => $e->getMessage(),
                    "success" => false
                ));
            }
        }
    }

    public function uninstallAction() {

        $type = $this->getParam("type");
        $id = $this->getParam("id");

        if($type == "plugin") {

            try {
                $config = Pimcore_ExtensionManager::getPluginConfig($id);
                $className = $config["plugin"]["pluginClassName"];

                $message = $className::uninstall();

                $this->_helper->json(array(
                    "message" => $message,
                    "reload" => $className::needsReloadAfterInstall(),
                    "pluginJsClassName" => $className::getJsClassName(),
                    "status" => array(
                        "installed" => $className::isInstalled()
                    ),
                    "success" => true
                ));
            } catch (Exception $e) {
                $this->_helper->json(array(
                    "message" => $e->getMessage(),
                    "success" => false
                ));
            }
        }
    }

    public function deleteAction () {

        $type = $this->getParam("type");
        $id = $this->getParam("id");

        Pimcore_ExtensionManager::delete($id, $type);

        $this->_helper->json(array(
            "success" => true
        ));
    }

    public function createAction()
    {
        $success = false;
        $name = ucfirst($this->getParam("name"));
        $examplePluginPath = realpath(PIMCORE_PATH . "/modules/extensionmanager/example-plugin");
        $pluginDestinationPath = realpath(PIMCORE_PLUGINS_PATH) . DIRECTORY_SEPARATOR . $name;

        if (preg_match("/^[a-zA-Z0-9]+$/", $name, $matches) && !is_dir($pluginDestinationPath)) {
            $pluginExampleFiles = rscandir($examplePluginPath);
            foreach ($pluginExampleFiles as $pluginExampleFile) {
                if(!is_file($pluginExampleFile)) continue;
                $newPath = $pluginDestinationPath . str_replace($examplePluginPath . DIRECTORY_SEPARATOR . 'Example', '', $pluginExampleFile);
                $newPath = str_replace(DIRECTORY_SEPARATOR . "Example" . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR, $newPath);

                $content = file_get_contents($pluginExampleFile);

                // do some modifications in the content of the file
                $content = str_replace("Example_", $name."_", $content);
                $content = str_replace("/Example/", "/".$name."/", $content);
                $content = str_replace(">Example<", ">".$name."<", $content);
                $content = str_replace(".example", ".".strtolower($name), $content);
                $content = str_replace("examplePlugin", strtolower($name)."Plugin", $content);
                $content = str_replace("Example Plugin", $name . " Plugin", $content);

                if (!file_exists(dirname($newPath))) {
                    Pimcore_File::mkdir(dirname($newPath));
                }

                Pimcore_File::put($newPath, $content);
            }
            $success = true;
        }

        $this->_helper->json(array(
            "success" => $success
        ));
    }
}
