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

use Pimcore\ExtensionManager;
use Pimcore\File;
use Pimcore\Logger;

class Extensionmanager_AdminController extends \Pimcore\Controller\Action\Admin
{
    public function init()
    {
        parent::init();

        $this->checkPermission("plugins");
    }

    public function getExtensionsAction()
    {
        $configurations = [];

        // plugins
        $pluginConfigs = ExtensionManager::getPluginConfigs();
        foreach ($pluginConfigs as $config) {
            $className = $config["plugin"]["pluginClassName"];
            $updateable = false;

            $revisionFile = PIMCORE_PLUGINS_PATH . "/" . $config["plugin"]["pluginName"] . "/.pimcore_extension_revision";
            if (is_file($revisionFile)) {
                $updateable = true;
            }

            if (!empty($className)) {
                $isEnabled = ExtensionManager::isEnabled("plugin", $config["plugin"]["pluginName"]);

                $plugin = [
                    "id" => $config["plugin"]["pluginName"],
                    "type" => "plugin",
                    "name" => isset($config["plugin"]["pluginNiceName"]) ? $config["plugin"]["pluginNiceName"] : '',
                    "description" => isset($config["plugin"]["pluginDescription"]) ? $config["plugin"]["pluginDescription"] : '',
                    "installed" => $isEnabled ? $className::isInstalled() : null,
                    "active" => $isEnabled,
                    "configuration" => isset($config["plugin"]["pluginIframeSrc"]) ? $config["plugin"]["pluginIframeSrc"] : null,
                    "updateable" => $updateable,
                    "version" => isset($config["plugin"]["pluginVersion"]) ? $config["plugin"]["pluginVersion"] : null
                ];

                if (isset($config["plugin"]["pluginXmlEditorFile"]) && is_readable(PIMCORE_PROJECT_ROOT . $config["plugin"]["pluginXmlEditorFile"])) {
                    $plugin['xmlEditorFile'] = $config["plugin"]["pluginXmlEditorFile"];
                }

                $configurations[] = $plugin;
            }
        }

        // bricks
        $brickConfigs = ExtensionManager::getBrickConfigs();
        // get repo state of bricks
        foreach ($brickConfigs as $id => $config) {
            $updateable = false;

            $revisionFile = PIMCORE_WEBSITE_VAR . "/areas/" . $id . "/.pimcore_extension_revision";
            if (is_file($revisionFile)) {
                $updateable = true;
            }

            $isEnabled = ExtensionManager::isEnabled("brick", $id);
            $brick = [
                "id" => $id,
                "type" => "brick",
                "name" => $config->name,
                "description" => $config->description,
                "installed" => true,
                "active" => $isEnabled,
                "updateable" => $updateable,
                "version" => $config->version
            ];
            $configurations[] = $brick;
        }

        $this->_helper->json(["extensions" => $configurations]);
    }

    public function toggleExtensionStateAction()
    {
        $type = $this->getParam("type");
        $id = $this->getParam("id");
        $method = $this->getParam("method");
        $reload = true;

        if ($type && $id) {
            ExtensionManager::$method($type, $id);
        }

        // do not reload when toggle an area-brick
        if ($type == "brick") {
            $reload = false;
        }

        $this->_helper->json(["success" => true, "reload" => $reload]);
    }


    public function installAction()
    {
        $type = $this->getParam("type");
        $id = $this->getParam("id");

        if ($type == "plugin") {
            try {
                $config = ExtensionManager::getPluginConfig($id);
                $className = $config["plugin"]["pluginClassName"];

                $message = $className::install();

                $this->_helper->json([
                    "message" => $message,
                    "reload" => $className::needsReloadAfterInstall(),
                    "status" => [
                        "installed" => $className::isInstalled()
                    ],
                    "success" => true
                ]);
            } catch (\Exception $e) {
                Logger::error($e);

                $this->_helper->json([
                    "message" => $e->getMessage(),
                    "success" => false
                ]);
            }
        }
    }

    public function uninstallAction()
    {
        $type = $this->getParam("type");
        $id = $this->getParam("id");

        if ($type == "plugin") {
            try {
                $config = ExtensionManager::getPluginConfig($id);
                $className = $config["plugin"]["pluginClassName"];

                $message = $className::uninstall();

                $this->_helper->json([
                    "message" => $message,
                    "reload" => $className::needsReloadAfterInstall(),
                    "pluginJsClassName" => $className::getJsClassName(),
                    "status" => [
                        "installed" => $className::isInstalled()
                    ],
                    "success" => true
                ]);
            } catch (\Exception $e) {
                $this->_helper->json([
                    "message" => $e->getMessage(),
                    "success" => false
                ]);
            }
        }
    }

    public function deleteAction()
    {
        $type = $this->getParam("type");
        $id = $this->getParam("id");

        ExtensionManager::delete($id, $type);

        $this->_helper->json([
            "success" => true
        ]);
    }

    public function createAction()
    {
        $success = false;
        $name = ucfirst($this->getParam("name"));
        $examplePluginPath = realpath(PIMCORE_PATH . "/modules/extensionmanager/example-plugin");
        $pluginDestinationPath = realpath(PIMCORE_PLUGINS_PATH) . DIRECTORY_SEPARATOR . $name;

        if (preg_match("/^[a-zA-Z0-9_]+$/", $name, $matches) && !is_dir($pluginDestinationPath)) {
            $pluginExampleFiles = rscandir($examplePluginPath);
            foreach ($pluginExampleFiles as $pluginExampleFile) {
                if (!is_file($pluginExampleFile)) {
                    continue;
                }
                $newPath = $pluginDestinationPath . str_replace($examplePluginPath . DIRECTORY_SEPARATOR . 'Example', '', $pluginExampleFile);
                $newPath = str_replace(DIRECTORY_SEPARATOR . "Example" . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR, $newPath);

                $content = file_get_contents($pluginExampleFile);

                // do some modifications in the content of the file
                $content = str_replace("Example", $name, $content);
                $content = str_replace(".example", ".".strtolower($name), $content);
                $content = str_replace("examplePlugin", strtolower($name)."Plugin", $content);
                $content = str_replace("Example Plugin", $name . " Plugin", $content);

                if (!file_exists(dirname($newPath))) {
                    File::mkdir(dirname($newPath));
                }

                File::put($newPath, $content);
            }
            $success = true;
        }

        $this->_helper->json([
            "success" => $success
        ]);
    }

    public function uploadAction()
    {
        $success = true;
        $tmpId = uniqid();
        $zipPath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugin-" . $tmpId . ".zip";
        $tempPath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugin-" . $tmpId;

        mkdir($tempPath);
        copy($_FILES["zip"]["tmp_name"], $zipPath);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($tempPath);
            $zip->close();
        } else {
            $success = false;
        }

        unlink($zipPath);

        // look for the plugin.xml
        $rootDir = null;
        $pluginName = null;
        $files = rscandir($tempPath);
        foreach ($files as $file) {
            if (preg_match("@[\\\/]plugin\.xml$@", $file)) {
                $rootDir = dirname($file);

                $pluginConfigArray = xmlToArray($file);
                $pluginConfig = new \Pimcore\Config\Config($pluginConfigArray);
                if ($pluginConfig->plugin->pluginName) {
                    $pluginName = $pluginConfig->plugin->pluginName;
                } else {
                    Logger::error("Unable to find 'pluginName' in " . $file);
                }

                break;
            }
        }

        if ($rootDir && $pluginName) {
            $pluginPath = PIMCORE_PLUGINS_PATH . "/" . $pluginName;

            // check for existing plugin
            if (is_dir($pluginPath)) {
                // move it to the backup directory
                rename($pluginPath, PIMCORE_BACKUP_DIRECTORY . "/" . $pluginName . "-" . time());
            }

            rename($rootDir, $pluginPath);
        } else {
            $success = false;
            Logger::err("No plugin.xml or plugin name found for uploaded plugin");
        }

        $this->_helper->json([
            "success" => $success
        ], false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }
}
