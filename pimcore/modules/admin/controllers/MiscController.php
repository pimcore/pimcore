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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\Tool;
use Pimcore\File;
use Pimcore\Resource;
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

        $translations = array();
        foreach ($list->getTranslations() as $t) {
            $translations[$t->getKey()] = $t->getTranslation($language);
        }
        $this->view->translations = $translations;
    }

    public function jsonTranslationsSystemAction()
    {
        $this->getResponse()->setHeader("Content-Type", "text/javascript", true);

        $language = $this->getParam("language");

        $languageFile = Tool\Admin::getLanguageFile($language);
        if (!is_file($languageFile)) {
            $languageFile = Tool\Admin::getLanguageFile("en");
        }

        $row = 1;
        $handle = fopen($languageFile, "r");
        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
            $translations[$data[0]] = $data[1];
        }
        fclose($handle);

        $broker = \Pimcore\API\Plugin\Broker::getInstance();
        $pluginTranslations = $broker->getTranslations($language);
        //$pluginTranslations = $this->getApiPluginBroker()->getTranslations($language);
        $translations = array_merge($pluginTranslations, $translations);

        $this->view->translations = $translations;
    }

    public function scriptProxyAction()
    {
        $this->removeViewRenderer();

        $scripts = explode(",", $this->getParam("scripts"));
        $scriptPath = $this->getParam("scriptPath");
        $scriptsContent = "";

        foreach ($scripts as $script) {
            $filePath = PIMCORE_DOCUMENT_ROOT . $scriptPath . $script;
            if (is_file($filePath) && is_readable($filePath)) {
                $scriptsContent .= file_get_contents($filePath);
            }
        }

        header("Cache-Control: max-age=86400");
        header("Pragma: ");
        //header("Content-Length: ".strlen($scriptsContent));
        header("Content-Type: application/x-javascript");
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 86400) . " GMT");
        echo $scriptsContent;
    }

    public function adminCssAction()
    {
        // customviews config
        $cvData = Tool::getCustomViewConfig();
        $this->view->customviews = $cvData;


        $this->getResponse()->setHeader("Content-Type", "text/css; charset=UTF-8", true);
    }

    public function proxyAction() {
        if($this->getParam("url")) {

            header("Content-Type: application/javascript");

            $client = Tool::getHttpClient();
            $client->setUri($this->getParam("url"));

            try {
                $response = $client->request(\Zend_Http_Client::GET);

                if ($response->isSuccessful()) {
                    echo $this->getParam("callback") . "(" . \Zend_Json::encode("data:" .$response->getHeader("Content-Type") . ";base64," . base64_encode($response->getBody())) . ");";
                } else {
                    throw new \Exception("Invalid response");
                }
            } catch (\Exception $e) {
                echo $this->getParam("callback") . "(" . \Zend_Json::encode("error:Application error") . ")";
            }
        }

        exit;
    }

    public function pingAction()
    {

        $response = array(
            "success" => true
        );


        $this->_helper->json($response);
    }

    public function availableLanguagesAction()
    {
        $this->getResponse()->setHeader("Content-Type", "text/javascript", true);

        $locales = Tool::getSupportedLocales();
        $this->view->languages = $locales;
    }

    public function getValidFilenameAction () {
        $this->_helper->json(array(
            "filename" => File::getValidFilename($this->getParam("value"))
        ));
    }

    /* FILEEXPLORER */

    public function fileexplorerTreeAction()
    {
        $this->checkPermission("fileexplorer");
        $referencePath = $this->getFileexplorerPath("node");

        $items = scandir($referencePath);
        $contents = array();

        foreach ($items as $item) {

            if ($item == "." || $item == "..") {
                continue;
            }

            $file = $referencePath . "/" . $item;
            $file = str_replace("//", "/", $file);

            if (is_dir($file) || is_file($file)) {
                $itemConfig = array(
                    "id" => "/fileexplorer" . str_replace(PIMCORE_DOCUMENT_ROOT, "", $file),
                    "text" => $item,
                    "leaf" => true,
                    "writeable" => is_writable($file)
                );

                if (is_dir($file)) {
                    $itemConfig["leaf"] = false;
                    $itemConfig["type"] = "folder";
                } else if (is_file($file)) {
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

        $this->_helper->json(array(
            "success" => $success,
            "content" => $content,
            "writeable" => $writeable,
            "path" => preg_replace("@^" . preg_quote(PIMCORE_DOCUMENT_ROOT) . "@", "", $file)
        ));
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

        $this->_helper->json(array(
                                  "success" => $success
                             ));
    }

    public function fileexplorerAddAction()
    {
        $this->checkPermission("fileexplorer");

        $success = false;

        if ($this->getParam("filename") && $this->getParam("path")) {
            $path = $this->getFileexplorerPath("path");
            $file = $path . "/" . $this->getParam("filename");

            if (is_writeable(dirname($file))) {
                File::put($file, "");

                $success = true;
            }
        }

        $this->_helper->json(array(
                                  "success" => $success
                             ));
    }

    public function fileexplorerAddFolderAction()
    {
        $this->checkPermission("fileexplorer");

        $success = false;

        if ($this->getParam("filename") && $this->getParam("path")) {
            $path = $this->getFileexplorerPath("path");
            $file = $path . "/" . $this->getParam("filename");

            if (is_writeable(dirname($file))) {
                File::mkdir($file);

                $success = true;
            }
        }

        $this->_helper->json(array(
            "success" => $success
        ));
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

        $this->_helper->json(array(
              "success" => $success
        ));
    }

    private function getFileexplorerPath($paramName = 'node')
    {
        $path = preg_replace("/^\/fileexplorer/", "", $this->getParam($paramName));
        $path = realpath(PIMCORE_DOCUMENT_ROOT . $path);

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

        $this->_helper->json(array(
              "success" => true
        ));
    }

    public function httpErrorLogAction() {

        $this->checkPermission("http_errors");

        $db = Resource::get();

        $limit = $this->getParam("limit");
        $offset = $this->getParam("start");
        $sort = $this->getParam("sort");
        $dir = $this->getParam("dir");
        $filter = $this->getParam("filter");
        $group = $this->getParam("group");
        if(!$limit) {
            $limit = 20;
        }
        if(!$offset) {
            $offset = 0;
        }
        if(!$sort || !in_array($sort, array("id","code","path","date","amount"))) {
            $sort = "date";
        }
        if(!$dir || !in_array($dir, array("DESC","ASC"))) {
            $dir = "DESC";
        }

        $condition = "";
        if($filter) {
            $filter = $db->quote("%" . $filter . "%");

            $conditionParts = array();
            foreach (array("path", "code", "parametersGet", "parametersPost", "serverVars", "cookies") as $field) {
                $conditionParts[] = $field . " LIKE " . $filter;
            }
            $condition = " WHERE " . implode(" OR ", $conditionParts);
        }

        if($group) {
            $logs = $db->fetchAll("SELECT id,code,path,date,count(*) as amount,concat(code,path) as `group` FROM http_error_log " . $condition . " GROUP BY `group` ORDER BY " . $sort . " " . $dir . " LIMIT " . $offset . "," . $limit);
            $total = $db->fetchOne("SELECT count(*) FROM (SELECT concat(code,path) as `group` FROM http_error_log " . $condition . " GROUP BY `group`) as counting");
        } else {
            $sort = ($sort == "amount") ? "date" : $sort;
            $logs = $db->fetchAll("SELECT id,code,path,date FROM http_error_log " . $condition . " ORDER BY " . $sort . " " . $dir . " LIMIT " . $offset . "," . $limit);
            $total = $db->fetchOne("SELECT count(*) FROM http_error_log " . $condition);
        }

        $this->_helper->json(array(
            "items" => $logs,
            "total" => $total,
            "success" => true
        ));
    }

    public function httpErrorLogFlushAction() {

        $this->checkPermission("http_errors");

        $db = Resource::get();
        $db->query("TRUNCATE TABLE http_error_log"); // much faster then $db->delete()

        $this->_helper->json(array(
            "success" => true
        ));
    }

    public function httpErrorLogDetailAction() {

        $this->checkPermission("http_errors");

        $db = Resource::get();
        $data = $db->fetchRow("SELECT * FROM http_error_log WHERE id = ?", array($this->getParam("id")));

        foreach ($data as $key => &$value) {
            if(in_array($key, array("parametersGet", "parametersPost", "serverVars", "cookies"))) {
                $value = unserialize($value);
            }
        }

        $this->view->data = $data;
    }

    public function countryListAction() {
        $countries = \Zend_Locale::getTranslationList('territory');
        asort($countries);
        $options = array();

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = array(
                    "name" => $translation,
                    "code" => $short
                );
            }
        }

        $this->_helper->json(array("data" => $options));
    }

    public function languageListAction() {
        $locales = Tool::getSupportedLocales();

        foreach ($locales as $short => $translation) {
            $options[] = array(
                "name" => $translation,
                "code" => $short
            );
        }

        $this->_helper->json(array("data" => $options));
    }

    public function phpinfoAction()
    {
        if(!$this->getUser()->isAdmin()) {
            throw new \Exception("Permission denied");
        }

        phpinfo();
        exit;
    }


    /**
     * page & snippet controller/action/template selector store providers
     */

    public function getAvailableControllersAction() {

        $controllers = array();
        $controllerDir = PIMCORE_WEBSITE_PATH . DIRECTORY_SEPARATOR . "controllers" . DIRECTORY_SEPARATOR;
        $controllerFiles = rscandir($controllerDir);
        foreach ($controllerFiles as $file) {
            $file = str_replace($controllerDir, "", $file);
            $dat = array();
            if(strpos($file, ".php") !== false) {
                $file = lcfirst(str_replace("Controller.php","",$file));
                $file = strtolower(preg_replace("/[A-Z]/","-\\0", $file));
                $dat["name"] = str_replace("/-", "_", $file);
                $controllers[] = $dat;
            }
        }

        $this->_helper->json(array(
            "data" => $controllers
        ));
    }

    public function getAvailableActionsAction () {

        $actions = array();
        $controller = $this->getParam("controllerName");
        $controllerClass = str_replace("-", " ", $controller);
        $controllerClass = str_replace(" ", "", ucwords($controllerClass));
        $controllerClass = preg_replace_callback("/([_])([a-z])/i", function ($matches) {
            return "/" . strtoupper($matches[2]);
        }, $controllerClass);

        $controllerFile = PIMCORE_WEBSITE_PATH . "/controllers/" . $controllerClass . "Controller.php";
        if(is_file($controllerFile)) {
            preg_match_all("/function[ ]+([a-zA-Z0-9]+)Action/i", file_get_contents($controllerFile), $matches);
            foreach ($matches[1] as $match) {
                $dat = array();
                $dat["name"] = strtolower(preg_replace("/[A-Z]/","-\\0", $match));
                $actions[] = $dat;
            }
        }

        $this->_helper->json(array(
            "data" => $actions
        ));
    }

    public function getAvailableTemplatesAction () {

        $templates = array();
        $viewPath = PIMCORE_WEBSITE_PATH . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "scripts";
        $files = rscandir($viewPath . DIRECTORY_SEPARATOR);
        foreach ($files as $file) {
            $dat = array();
            if(strpos($file, \Pimcore\View::getViewScriptSuffix()) !== false) {
                $dat["path"] = str_replace($viewPath, "", $file);
                $dat["path"] = str_replace("\\", "/", $dat["path"]); // unix directory separator are compatible with windows, not the reverse
                $templates[] = $dat;
            }
        }

        $this->_helper->json(array(
            "data" => $templates
        ));
    }

    public function getLanguageFlagAction() {

        $iconPath = Tool::getLanguageFlagFile($this->getParam("language"));
        header("Content-Type: image/png");
        echo file_get_contents($iconPath);

        exit;
    }

    public function testAction()
    {

        die("done");
    }
}

