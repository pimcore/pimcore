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

class Admin_MiscController extends Pimcore_Controller_Action_Admin
{

    public function liveconnectAction()
    {

        $token = $this->getParam("token");
        Pimcore_Liveconnect::setToken($token);
        $this->view->token = $token;
    }

    public function jsonTranslationsAdminAction()
    {
        $this->getResponse()->setHeader("Content-Type", "text/javascript", true);

        $language = $this->getParam("language");

        $list = new Translation_Admin_List();
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

        $languageFile = Pimcore_Tool_Admin::getLanguageFile($language);
        if (!is_file($languageFile)) {
            $languageFile = Pimcore_Tool_Admin::getLanguageFile("en");
        }

        $row = 1;
        $handle = fopen($languageFile, "r");
        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
            $translations[$data[0]] = $data[1];
        }
        fclose($handle);

        $broker = Pimcore_API_Plugin_Broker::getInstance();
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
        $cvData = Pimcore_Tool::getCustomViewConfig();
        $this->view->customviews = $cvData;


        $this->getResponse()->setHeader("Content-Type", "text/css; charset=UTF-8", true);
    }

    public function proxyAction() {
        if($this->getParam("url")) {

            header("Content-Type: application/javascript");

            $client = Pimcore_Tool::getHttpClient();
            $client->setUri($this->getParam("url"));

            try {
                $response = $client->request(Zend_Http_Client::GET);

                if ($response->isSuccessful()) {
                    echo $this->getParam("callback") . "(" . Zend_Json::encode("data:" .$response->getHeader("Content-Type") . ";base64," . base64_encode($response->getBody())) . ");";
                } else {
                    throw new Exception("Invalid response");
                }
            } catch (Exception $e) {
                echo $this->getParam("callback") . "(" . Zend_Json::encode("error:Application error") . ")";
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

        $locales = Pimcore_Tool::getSupportedLocales();
        $this->view->languages = $locales;
    }

    public function getValidFilenameAction () {
        $this->_helper->json(array(
            "filename" => Pimcore_File::getValidFilename($this->getParam("value"))
        ));
    }

    /* FILEEXPLORER */

    public function fileexplorerTreeAction()
    {
        $this->checkPermission("fileexplorer");

        $path = preg_replace("/^\/fileexplorer/", "", $this->getParam("node"));
        $referencePath = PIMCORE_DOCUMENT_ROOT . $path;

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
        $path = preg_replace("/^\/fileexplorer/", "", $this->getParam("path"));
        $file = PIMCORE_DOCUMENT_ROOT . $path;
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
                                  "path" => $path
                             ));
    }

    public function fileexplorerContentSaveAction()
    {
        $this->checkPermission("fileexplorer");

        $success = false;

        if ($this->getParam("content") && $this->getParam("path")) {
            $file = PIMCORE_DOCUMENT_ROOT . $this->getParam("path");
            if (is_file($file) && is_writeable($file)) {
                Pimcore_File::put($file, $this->getParam("content"));

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
            $path = preg_replace("/^\/fileexplorer/", "", $this->getParam("path"));
            $file = PIMCORE_DOCUMENT_ROOT . $path . "/" . $this->getParam("filename");

            if (is_writeable(dirname($file))) {
                Pimcore_File::put($file, "");

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
            $path = preg_replace("/^\/fileexplorer/", "", $this->getParam("path"));
            $file = PIMCORE_DOCUMENT_ROOT . $path . "/" . $this->getParam("filename");

            if (is_writeable(dirname($file))) {
                Pimcore_File::mkdir($file);

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
            $path = preg_replace("/^\/fileexplorer/", "", $this->getParam("path"));
            $file = PIMCORE_DOCUMENT_ROOT . $path;
            if (is_writeable($file)) {
                unlink($file);
                $success = true;
            }
        }

        $this->_helper->json(array(
              "success" => $success
        ));
    }

    public function maintenanceAction()
    {
        $this->checkPermission("maintenance_mode");

        if ($this->getParam("activate")) {
            Pimcore_Tool_Admin::activateMaintenanceMode();
        }

        if ($this->getParam("deactivate")) {
            Pimcore_Tool_Admin::deactivateMaintenanceMode();
        }

        $this->_helper->json(array(
              "success" => true
        ));
    }

    public function httpErrorLogAction() {

        $this->checkPermission("http_errors");

        $db = Pimcore_Resource::get();

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

        $db = Pimcore_Resource::get();
        $db->delete("http_error_log");

        $this->_helper->json(array(
            "success" => true
        ));
    }

    public function httpErrorLogDetailAction() {

        $this->checkPermission("http_errors");

        $db = Pimcore_Resource::get();
        $data = $db->fetchRow("SELECT * FROM http_error_log WHERE id = ?", array($this->getParam("id")));

        foreach ($data as $key => &$value) {
            if(in_array($key, array("parametersGet", "parametersPost", "serverVars", "cookies"))) {
                $value = unserialize($value);
            }
        }

        $this->view->data = $data;
    }

    public function countryListAction() {
        $countries = Zend_Locale::getTranslationList('territory');
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
        $locales = Pimcore_Tool::getSupportedLocales();

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
        phpinfo();
        exit;
    }


    protected function getBounceMailbox () {

        $mail = null;
        $config = Pimcore_Config::getSystemConfig();

        if($config->email->bounce->type == "Mbox") {
            $mail = new Zend_Mail_Storage_Mbox(array(
                'filename' => $config->email->bounce->mbox
            ));
        } else if ($config->email->bounce->type == "Maildir") {
            $mail = new Zend_Mail_Storage_Maildir(array(
                'dirname' => $config->email->bounce->maildir
            ));
        } else if ($config->email->bounce->type == "IMAP") {
            $mail = new Zend_Mail_Storage_Imap(array(
                'host' => $config->email->bounce->imap->host,
                "port" => $config->email->bounce->imap->port,
                'user' => $config->email->bounce->imap->username,
                'password' => $config->email->bounce->imap->password,
                "ssl" => (bool) $config->email->bounce->imap->ssl
            ));
        } else {
            // default
            $pathes = array(
                "/var/mail/" . get_current_user(),
                "/var/spool/mail/" . get_current_user()
            );

            foreach ($pathes as $path) {
                if(is_dir($path)) {
                    $mail = new Zend_Mail_Storage_Maildir(array(
                        'dirname' => $path . "/"
                    ));
                } else if(is_file($path)) {
                    $mail = new Zend_Mail_Storage_Mbox(array(
                        'filename' => $path
                    ));
                }
            }
        }

        return $mail;
    }

    public function bounceMailInboxListAction() {

        $this->checkPermission("bounce_mail_inbox");

        $offset = ($this->getParam("start")) ? $this->getParam("start")+1 : 1;
        $limit = ($this->getParam("limit")) ? $this->getParam("limit") : 40;

        $mail = $this->getBounceMailbox();
        $mail->seek($offset);

        $mails = array();
        $count = 0;
        while ($mail->valid()) {
            $count++;

            $message = $mail->current();

            $mailData = array(
                "subject" => iconv(mb_detect_encoding($message->subject), "UTF-8", $message->subject),
                "to" => $message->to,
                "from" => $message->from,
                "id" => (int) $mail->key()
            );

            $date = new Zend_Date($message->date);
            $mailData["date"] = $date->get(Zend_Date::DATETIME_MEDIUM);

            $mails[] = $mailData;

            if($count >= $limit) {
                break;
            }

            $mail->next();
        }

        $this->_helper->json(array(
            "data" => $mails,
            "success" => true,
            "total" => $mail->countMessages()
        ));
    }

    public function bounceMailInboxDetailAction() {

        $this->checkPermission("bounce_mail_inbox");

        $mail = $this->getBounceMailbox();

        $message = $mail->getMessage((int) $this->getParam("id"));
        $message->getContent();

        $this->view->mail = $mail; // we have to pass $mail too, otherwise the stream is closed
        $this->view->message = $message;
    }


    /**
     * page & snippet controller/action/template selector store providers
     */

    public function getAvailableControllersAction() {

        $controllers = array();
        $controllerDir = PIMCORE_WEBSITE_PATH . "/controllers/";
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
        $viewPath = PIMCORE_WEBSITE_PATH . "/views/scripts";
        $files = rscandir($viewPath . "/");
        foreach ($files as $file) {
            $dat = array();
            if(strpos($file, Pimcore_View::getViewScriptSuffix()) !== false) {
                $dat["path"] = str_replace($viewPath, "", $file);
                $templates[] = $dat;
            }
        }

        $this->_helper->json(array(
            "data" => $templates
        ));
    }

    public function robohashAction() {
        $seed = crc32($this->getParam("seed", rand(0,20000)));
        $colors = array("blue","brown","green","grey","orange","pink","purple","red","white","yellow");
        $partDirs = array("003#01Body", "004#02Face", "000#Mouth","001#Eyes","002#Accessory");

        $im = null;

        srand($seed);
        $color = $colors[array_rand($colors)];
        $dir = PIMCORE_PATH . "/static/img/robohash/" . $color;

        foreach ($partDirs as $key => $partDir) {
            $partDir = $dir . "/" . $partDir;
            $files = scandir($partDir);

            srand($seed + $key);
            $id = rand(0,9);

            foreach ($files as $file) {
                if(preg_match("/^00" . $id . "#/", $file)) {
                    $partIm = imagecreatefrompng($partDir . "/" . $file);
                    break;
                }
            }

            if($im) {
                imagecopy($im, $partIm, 0,0,0,0,300,300);
            } else {
                $im = $partIm;
                imagesavealpha($im, true);
            }
        }

        if($this->getParam("width") && $this->getParam("height")) {
            $w = $this->getParam("width");
            $h = $this->getParam("height");
            $imResized = imagecreatetruecolor($w, $h);
            imagesavealpha($imResized, true);
            imagealphablending($imResized, false);
            $trans_colour = imagecolorallocatealpha($imResized, 255, 0, 0, 127);
            imagefill($imResized, 0, 0, $trans_colour);
            imagecopyresampled($imResized, $im, 0, 0, 0, 0, $w, $h, 300, 300);
            $im = $imResized;
        }

        header("Content-Type: image/png");
        imagepng($im);
        imagedestroy($im);
        exit;
    }

    public function testAction()
    {

        die("done");
    }
}

