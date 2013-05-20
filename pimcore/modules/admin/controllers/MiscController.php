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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
                file_put_contents($file, $this->getParam("content"));
                chmod($file, 0766);

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
                file_put_contents($file, "");
                chmod($file, 0766);

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
                mkdir($file);

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

    public function xliffExportJobsAction() {

        $data = Zend_Json::decode($this->getParam("data"));
        $elements = array();
        $jobs = array();
        $exportId = uniqid();
        $source = $this->getParam("source");
        $target = $this->getParam("target");

        // XLIFF requires region in language code
        /*$languages = Zend_Locale::getLocaleList();
        if(strlen($source) < 5) {
            foreach ($languages as $key => $value) {
                if(strlen($key) > 4 && strpos($key, $source . "_") === 0) {
                    $source = $key;
                    break;
                }
            }
        }

        if(strlen($target) < 5) {
            foreach ($languages as $key => $value) {
                if(strlen($key) > 4 && strpos($key, $target . "_") === 0) {
                    $target = $key;
                    break;
                }
            }
        }*/

        $source = str_replace("_","-", $source);
        $target = str_replace("_","-", $target);

        if($data && is_array($data)) {
            foreach ($data as $element) {
                $elements[] = array(
                    "id" => $element["id"],
                    "type" => $element["type"]
                );

                if($element["children"]) {
                    $el = Element_Service::getElementById($element["type"], $element["id"]);
                    $listClass = ucfirst($element["type"]) . "_List";
                    $list = new $listClass();
                    $list->setCondition(($el instanceof Object_Abstract ? "o_" : "") . "path LIKE ?", array($el->getFullPath() . ($el->getFullPath() != "/" ? "/" : "") . "%"));
                    $idList = $list->loadIdList();

                    foreach($idList as $id) {
                        $elements[] = array(
                            "id" => $id,
                            "type" => $element["type"]
                        );
                    }
                }
            }
        }

        // one job = 10 elements
        $elements = array_chunk($elements, 10);
        foreach($elements as $chunk) {
            $jobs[] = array(array(
                "url" => "/admin/misc/xliff-export",
                "params" => array(
                    "id" => $exportId,
                    "source" => $source,
                    "target" => $target,
                    "data" => Zend_Json::encode($chunk)
                )
            ));
        }

        $this->_helper->json(array(
            "success" => true,
            "jobs" => $jobs,
            "id" => $exportId
        ));
    }

    public function xliffExportAction() {

        $id = $this->getParam("id");
        $data = Zend_Json::decode($this->getParam("data"));
        $source = $this->getParam("source");
        $target = $this->getParam("target");

        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $id . ".xliff";
        if(!is_file($exportFile)) {
            // create initial xml file structure
            $xliff = new SimpleXMLElement('<xliff></xliff>');
            $xliff->addAttribute('version', '1.2');
            $xliff->asXML($exportFile);
        }

        $xliff = simplexml_load_file($exportFile, null, LIBXML_NOCDATA);

        foreach ($data as $el) {
            $element = Element_Service::getElementById($el["type"], $el["id"]);
            $file = $xliff->addChild('file');
            $file->addAttribute('original', Element_Service::getElementType($element) . '-' . $element->getId());
            $file->addAttribute('source-language', $source);
            $file->addAttribute('target-language', $target);
            $file->addAttribute('datatype', "html");
            $file->addAttribute('tool', "pimcore");
            $file->addAttribute('category', Element_Service::getElementType($element));

            $header = $file->addChild('header');

            $body = $file->addChild('body');
            $addedElements = false;

            // elements
            if($element instanceof Document) {
                if(method_exists($element, "getElements")) {
                    foreach ($element->getElements() as $tag) {

                        if(in_array($tag->getType(), array("wysiwyg", "input", "textarea", "image"))) {

                            if($tag->getType() == "image") {
                                $content = $tag->getText();
                            } else {
                                $content = $tag->getData();
                            }

                            if(is_string($content)) {
                                $contentCheck = trim(strip_tags($content));
                                if(!empty($contentCheck)) {
                                    $this->addTransUnitNode($body, "tag~-~" . $tag->getName(), $content, $source);
                                    $addedElements = true;
                                }
                            }
                        }
                    }
                }

                if($element instanceof Document_Page) {
                    $data = array(
                        "title" => $element->getTitle(),
                        "description" => $element->getDescription(),
                        "keywords" => $element->getKeywords()
                    );

                    foreach ($data as $key => $content) {
                        if(!empty($content)) {
                            $this->addTransUnitNode($body, "settings~-~" . $key, $content, $source);
                            $addedElements = true;
                        }
                    }
                }
            } else if ($element instanceof Object_Concrete) {
                if($fd = $element->getClass()->getFieldDefinition("localizedfields")) {
                    $definitions = $fd->getFielddefinitions();

                    $locale = new Zend_Locale(str_replace("-","_", $source));
                    if(Pimcore_Tool::isValidLanguage((string) $locale)) {
                        $locale = (string) $locale;
                    } else {
                        $locale = $locale->getLanguage();
                    }

                    foreach($definitions as $definition) {

                        // check allowed datatypes
                        if(!in_array($definition->getFieldtype(), array("input", "textarea", "wysiwyg"))) {
                            continue;
                        }

                        $content = $element->{"get" . ucfirst($definition->getName())}($locale);

                        if(!empty($content)) {
                            $this->addTransUnitNode($body, "localizedfield~-~" . $definition->getName(), $content, $source);
                            $addedElements = true;
                        }
                    }
                }
            }

            // properties
            $properties = $element->getProperties();
            if(is_array($properties)) {
                foreach($properties as $property) {
                    if($property->getType() == "text" && !$property->isInherited()) {

                        // exclude text properties
                        if($element instanceof Document) {
                            if(in_array($property->getName(), array(
                                "language",
                                "navigation_target",
                                "navigation_exclude",
                                "navigation_class",
                                "navigation_anchor",
                                "navigation_parameters",
                                "navigation_relation",
                                "navigation_accesskey",
                                "navigation_tabindex"))) {
                                continue;
                            }
                        }

                        $content = $property->getData();
                        if(!empty($content)) {
                            $this->addTransUnitNode($body, "property~-~" . $property->getName(), $content, $source);
                            $addedElements = true;
                        }
                    }
                }
            }

            // remove file if it is empty
            if(!$addedElements) {
                $file = dom_import_simplexml($file);
                $file->parentNode->removeChild($file);
            }
        }

        $xliff->asXML($exportFile);

        $this->_helper->json(array(
            "success" => true
        ));
    }

    public function xliffExportDownloadAction() {
        $id = $this->getParam("id");
        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $id . ".xliff";

        header("Content-Type: application/x-xliff+xml");
        header('Content-Disposition: attachment; filename="' . basename($exportFile) . '"');

        while(@ob_end_flush());
        flush();

        readfile($exportFile);
        @unlink($exportFile);
        exit;
    }

    public function xliffImportUploadAction() {

        $jobs = array();
        $id = uniqid();
        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $id . ".xliff";
        copy($_FILES["file"]["tmp_name"], $importFile);

        $xliff = simplexml_load_file($importFile, null, LIBXML_NOCDATA);
        $steps = count($xliff->file);

        for($i=0; $i<$steps; $i++) {
            $jobs[] = array(array(
                "url" => "/admin/misc/xliff-import-element",
                "params" => array(
                    "id" => $id,
                    "step" => $i
                )
            ));
        }

        $this->_helper->json(array(
            "success" => true,
            "jobs" => $jobs,
            "id" => $id
        ), false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function xliffImportElementAction() {

        include_once("simple_html_dom.php");

        $id = $this->getParam("id");
        $step = $this->getParam("step");
        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $id . ".xliff";

        $xliff = simplexml_load_file($importFile, null, LIBXML_NOCDATA);
        $file = $xliff->file[(int)$step];
        $target = $file["target-language"];

        if(!Pimcore_Tool::isValidLanguage($target)) {
            $locale = new Zend_Locale($target);
            $target = $locale->getLanguage();
            if(!Pimcore_Tool::isValidLanguage($target)) {
                $this->_helper->json(array(
                    "success" => false
                ));
            }
        }

        list($type, $id) = explode("-", $file["original"]);
        $element = Element_Service::getElementById($type, $id);

        if($element) {
            foreach($file->body->{"trans-unit"} as $transUnit) {
                list($fieldType, $name) = explode("~-~", $transUnit["id"]);
                $content = $transUnit->target->asXml();
                $content = $this->unescapeXliff($content);

                if($element instanceof Document) {
                    if($fieldType == "tag" && method_exists($element, "getElement")) {
                        $tag = $element->getElement($name);
                        $tag->setDataFromEditmode($content);
                    }

                    if($fieldType == "settings" && $element instanceof Document_Page) {
                        $setter = "set" . ucfirst($name);
                        if(method_exists($element, $setter)) {
                            $element->$setter($content);
                        }
                    }
                } else if($element instanceof Object_Concrete) {
                    if($fieldType == "localizedfield") {
                        $setter = "set" . ucfirst($name);
                        if(method_exists($element, $setter)) {
                            $element->$setter($content, $target);
                        }
                    }
                }

                if($fieldType == "property") {
                    $property = $element->getProperty($name, true);
                    $property->setData($content);
                }
            }

            $element->save();
        }

        $this->_helper->json(array(
            "success" => true
        ));
    }

    protected function addTransUnitNode($xml, $name, $content, $source) {
        $transUnit = $xml->addChild('trans-unit');
        $transUnit->addAttribute("id", htmlentities($name));

        $sourceNode = $transUnit->addChild('source');
        $sourceNode->addAttribute("xmlns:xml:lang", $source);

        $node = dom_import_simplexml($sourceNode);
        $no = $node->ownerDocument;
        $f = $no->createDocumentFragment();
        $f->appendXML($this->escapeXliff($content));
        @$node->appendChild($f);
    }

    protected function unescapeXliff($content) {

        $content = preg_replace("/<\/?target([^>.]+)?>/i", "", $content);

        if(preg_match("/<\/?(bpt|ept)/", $content)) {
            $xml = str_get_html($content);
            if($xml) {
                $els = $xml->find("bpt,ept");
                foreach($els as $el) {
                    $content = html_entity_decode($el->innertext, null, "UTF-8");
                    $el->outertext = $content;
                }
            }
            $content = $xml->save();
        }

        return $content;
    }

    protected function escapeXliff($content) {
        $count = 1;
        $openTags = array();
        $final = array();

        $content = html_entity_decode($content, null, "UTF-8");

        if(!preg_match_all("/<([^>.]+)>([^<.]+)?/", $content, $matches)) {
            // return original content if it doesn't contain HTML tags
            return '<![CDATA[' . $content . ']]>';
        }

        foreach($matches[0] as $match) {
            $parts = explode(">", $match);
            $parts[0] .= ">";
            foreach ($parts as $part) {
                $part = trim($part);
                if(!empty($part)) {

                    if(preg_match("/<([a-z0-9\/]+)/", $part, $tag)) {
                        $tagName = str_replace("/", "", $tag[1]);
                        if(strpos($tag[1], "/") === false) {
                            $openTags[$count] = array("tag" => $tagName, "id" => $count);
                            $part = '<bpt id="' . $count . '"><![CDATA[' . $part . ']]></bpt>';

                            $count++;
                        } else {
                            $closingTag = array_pop($openTags);
                            $part = '<ept id="' . $closingTag["id"] . '"><![CDATA[' . $part . ']]></ept>';
                        }
                    } else {
                        $part = '<![CDATA[' . $part . ']]>';
                    }

                    if(!empty($part)) {
                        $final[] = $part;
                    }
                }
            }
        }

        $content = implode("", $final);
        return $content;
    }

    public function testAction()
    {

        die("done");
    }
}

