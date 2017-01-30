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
    public function jsonTranslationsAdminAction()
    {
        $this->getResponse()->setHeader("Content-Type", "text/javascript", true);

        $language = $this->getParam("language");

        $list = new Translation\Admin\Listing();
        $list->setOrder("asc");
        $list->setOrderKey("key");
        $list->load();

        $translations = [];
        foreach ($list->getTranslations() as $t) {
            $translations[$t->getKey()] = $t->getTranslation($language);
        }
        $this->view->translations = $translations;
    }

    public function jsonTranslationsSystemAction()
    {
        $this->getResponse()->setHeader("Content-Type", "text/javascript", true);

        $language = $this->getParam("language");

        $languageFiles = [
            $language => Tool\Admin::getLanguageFile($language),
            "en" => Tool\Admin::getLanguageFile("en")
        ];

        $translations = [];
        foreach ($languageFiles as $langKey => $languageFile) {
            if (file_exists($languageFile)) {
                $rawTranslations = json_decode(file_get_contents($languageFile), true);
                foreach ($rawTranslations as $entry) {
                    if (!isset($translations[$entry["term"]])) {
                        $translations[$entry["term"]] = $entry["definition"];
                    }
                }
            }
        }

        $broker = \Pimcore\API\Plugin\Broker::getInstance();
        $pluginTranslations = $broker->getTranslations($language);
        $translations = array_merge($pluginTranslations, $translations);

        $this->view->translations = $translations;
    }

    public function scriptProxyAction()
    {
        $this->disableViewAutoRender();

        $allowedFileTypes = ["js", "css"];
        $scripts = explode(",", $this->getParam("scripts"));

        if ($this->getParam("scriptPath")) {
            $scriptPath = PIMCORE_DOCUMENT_ROOT . $this->getParam("scriptPath");
        } else {
            $scriptPath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/";
        }

        $scriptsContent = "";
        foreach ($scripts as $script) {
            $filePath = $scriptPath . $script;
            if (is_file($filePath) && is_readable($filePath) && in_array(\Pimcore\File::getFileExtension($script), $allowedFileTypes)) {
                $scriptsContent .= file_get_contents($filePath);
            }
        }

        $fileExtension = \Pimcore\File::getFileExtension($scripts[0]);
        $contentType = "text/javascript";
        if ($fileExtension == "css") {
            $contentType = "text/css";
        }

        $lifetime = 86400;
        $this->getResponse()->setHeader("Cache-Control", "max-age=" . $lifetime, true);
        $this->getResponse()->setHeader("Pragma", "", true);
        $this->getResponse()->setHeader("Content-Type", $contentType, true);
        $this->getResponse()->setHeader("Expires", gmdate("D, d M Y H:i:s", time() + $lifetime) . " GMT", true);

        echo $scriptsContent;
    }

    public function adminCssAction()
    {
        // customviews config
        $cvData = Tool::getCustomViewConfig();
        $this->view->customviews = $cvData;


        $this->getResponse()->setHeader("Content-Type", "text/css; charset=UTF-8", true);
    }

    public function pingAction()
    {
        $response = [
            "success" => true
        ];


        $this->_helper->json($response);
    }

    public function availableLanguagesAction()
    {
        $this->getResponse()->setHeader("Content-Type", "text/javascript", true);

        $locales = Tool::getSupportedLocales();
        $this->view->languages = $locales;
    }

    public function getValidFilenameAction()
    {
        $this->_helper->json([
            "filename" => \Pimcore\Model\Element\Service::getValidKey($this->getParam("value"), $this->getParam("type"))
        ]);
    }

    /* FILEEXPLORER */

    public function fileexplorerTreeAction()
    {
        $this->checkPermission("fileexplorer");
        $referencePath = $this->getFileexplorerPath("node");

        $items = scandir($referencePath);
        $contents = [];

        foreach ($items as $item) {
            if ($item == "." || $item == "..") {
                continue;
            }

            $file = $referencePath . "/" . $item;
            $file = str_replace("//", "/", $file);

            if (is_dir($file) || is_file($file)) {
                $itemConfig = [
                    "id" => "/fileexplorer" . str_replace(PIMCORE_DOCUMENT_ROOT, "", $file),
                    "text" => $item,
                    "leaf" => true,
                    "writeable" => is_writable($file)
                ];

                if (is_dir($file)) {
                    $itemConfig["leaf"] = false;
                    $itemConfig["type"] = "folder";
                    if (\Pimcore\Tool\Admin::isExtJS6() && is_dir_empty($file)) {
                        $itemConfig["loaded"] = true;
                    }
                    $itemConfig["expandable"] = true;
                } elseif (is_file($file)) {
                    $itemConfig["type"] = "file";
                }

                $contents[] = $itemConfig;
            }
        }

        $this->_helper->json($contents);
    }

    public function fileexplorerContentAction()
    {
        $this->checkPermission("fileexplorer");

        $success = false;
        $writeable = false;
        $file = $this->getFileexplorerPath("path");
        if (is_file($file)) {
            if (is_readable($file)) {
                $content = file_get_contents($file);
                $success = true;
                $writeable = is_writeable($file);
            }
        }

        $this->_helper->json([
            "success" => $success,
            "content" => $content,
            "writeable" => $writeable,
            "path" => preg_replace("@^" . preg_quote(PIMCORE_DOCUMENT_ROOT) . "@", "", $file)
        ]);
    }

    public function fileexplorerContentSaveAction()
    {
        $this->checkPermission("fileexplorer");

        $success = false;

        if ($this->getParam("content") && $this->getParam("path")) {
            $file = $this->getFileexplorerPath("path");
            if (is_file($file) && is_writeable($file)) {
                File::put($file, $this->getParam("content"));

                $success = true;
            }
        }

        $this->_helper->json([
                                  "success" => $success
                             ]);
    }

    public function fileexplorerAddAction()
    {
        $this->checkPermission("fileexplorer");

        $success = false;

        if ($this->getParam("filename") && $this->getParam("path")) {
            $path = $this->getFileexplorerPath("path");
            $file = $path . "/" . $this->getParam("filename");

            $file= resolvePath($file);
            if (strpos($file, PIMCORE_DOCUMENT_ROOT) !== 0) {
                throw new \Exception("not allowed");
            }

            if (is_writeable(dirname($file))) {
                File::put($file, "");

                $success = true;
            }
        }

        $this->_helper->json([
                                  "success" => $success
                             ]);
    }

    public function fileexplorerAddFolderAction()
    {
        $this->checkPermission("fileexplorer");

        $success = false;

        if ($this->getParam("filename") && $this->getParam("path")) {
            $path = $this->getFileexplorerPath("path");
            $file = $path . "/" . $this->getParam("filename");

            $file= resolvePath($file);
            if (strpos($file, PIMCORE_DOCUMENT_ROOT) !== 0) {
                throw new \Exception("not allowed");
            }

            if (is_writeable(dirname($file))) {
                File::mkdir($file);

                $success = true;
            }
        }

        $this->_helper->json([
            "success" => $success
        ]);
    }

    public function fileexplorerDeleteAction()
    {
        $this->checkPermission("fileexplorer");

        if ($this->getParam("path")) {
            $file = $this->getFileexplorerPath("path");
            if (is_writeable($file)) {
                unlink($file);
                $success = true;
            }
        }

        $this->_helper->json([
              "success" => $success
        ]);
    }

    private function getFileexplorerPath($paramName = 'node')
    {
        $path = preg_replace("/^\/fileexplorer/", "", $this->getParam($paramName));
        $path = resolvePath(PIMCORE_DOCUMENT_ROOT . $path);

        if (strpos($path, PIMCORE_DOCUMENT_ROOT) !== 0) {
            throw new \Exception('operation permitted, permission denied');
        }

        return $path;
    }

    public function maintenanceAction()
    {
        $this->checkPermission("maintenance_mode");

        if ($this->getParam("activate")) {
            Tool\Admin::activateMaintenanceMode();
        }

        if ($this->getParam("deactivate")) {
            Tool\Admin::deactivateMaintenanceMode();
        }

        $this->_helper->json([
              "success" => true
        ]);
    }

    public function httpErrorLogAction()
    {
        $this->checkPermission("http_errors");

        $db = Db::get();

        $limit = intval($this->getParam("limit"));
        $offset = intval($this->getParam("start"));
        $sort = $this->getParam("sort");
        $dir = $this->getParam("dir");
        $filter = $this->getParam("filter");
        if (!$limit) {
            $limit = 20;
        }
        if (!$offset) {
            $offset = 0;
        }
        if (!$sort || !in_array($sort, ["code", "uri", "date", "count"])) {
            $sort = "count";
        }
        if (!$dir || !in_array($dir, ["DESC", "ASC"])) {
            $dir = "DESC";
        }

        $condition = "";
        if ($filter) {
            $filter = $db->quote("%" . $filter . "%");

            $conditionParts = [];
            foreach (["uri", "code", "parametersGet", "parametersPost", "serverVars", "cookies"] as $field) {
                $conditionParts[] = $field . " LIKE " . $filter;
            }
            $condition = " WHERE " . implode(" OR ", $conditionParts);
        }

        $logs = $db->fetchAll("SELECT code,uri,`count`,date FROM http_error_log " . $condition . " ORDER BY " . $sort . " " . $dir . " LIMIT " . $offset . "," . $limit);
        $total = $db->fetchOne("SELECT count(*) FROM http_error_log " . $condition);

        $this->_helper->json([
            "items" => $logs,
            "total" => $total,
            "success" => true
        ]);
    }

    public function httpErrorLogFlushAction()
    {
        $this->checkPermission("http_errors");

        $db = Db::get();
        $db->query("TRUNCATE TABLE http_error_log"); // much faster then $db->delete()

        $this->_helper->json([
            "success" => true
        ]);
    }

    public function httpErrorLogDetailAction()
    {
        $this->checkPermission("http_errors");

        $db = Db::get();
        $data = $db->fetchRow("SELECT * FROM http_error_log WHERE uri = ?", [$this->getParam("uri")]);

        foreach ($data as $key => &$value) {
            if (in_array($key, ["parametersGet", "parametersPost", "serverVars", "cookies"])) {
                $value = unserialize($value);
            }
        }

        $this->view->data = $data;
    }

    public function countryListAction()
    {
        $countries = \Zend_Locale::getTranslationList('territory');
        asort($countries);
        $options = [];

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = [
                    "name" => $translation,
                    "code" => $short
                ];
            }
        }

        $this->_helper->json(["data" => $options]);
    }

    public function languageListAction()
    {
        $locales = Tool::getSupportedLocales();

        foreach ($locales as $short => $translation) {
            $options[] = [
                "name" => $translation,
                "code" => $short
            ];
        }

        $this->_helper->json(["data" => $options]);
    }

    public function phpinfoAction()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new \Exception("Permission denied");
        }

        phpinfo();
        exit;
    }

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

    public function getLanguageFlagAction()
    {
        $iconPath = Tool::getLanguageFlagFile($this->getParam("language"));
        if (Tool\Admin::isExtJS6()) {
            header("Content-Type: image/svg+xml");
        } else {
            header("Content-Type: image/png");
        }
        echo file_get_contents($iconPath);

        exit;
    }

    public function testAction()
    {
        die("done");
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
