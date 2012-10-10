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

class Admin_SettingsController extends Pimcore_Controller_Action_Admin {

    public function propertiesAction() {

        if ($this->getParam("data")) {
            if ($this->getUser()->isAllowed("predefined_properties")) {
                if ($this->getParam("xaction") == "destroy") {

                    $id = Zend_Json::decode($this->getParam("data"));

                    $property = Property_Predefined::getById($id);
                    $property->delete();

                    $this->_helper->json(array("success" => true, "data" => array()));
                }
                else if ($this->getParam("xaction") == "update") {

                    $data = Zend_Json::decode($this->getParam("data"));

                    // save type
                    $property = Property_Predefined::getById($data["id"]);
                    $property->setValues($data);

                    $property->save();

                    $this->_helper->json(array("data" => $property, "success" => true));
                }
                else if ($this->getParam("xaction") == "create") {
                    $data = Zend_Json::decode($this->getParam("data"));
                    unset($data["id"]);

                    // save type
                    $property = Property_Predefined::create();
                    $property->setValues($data);

                    $property->save();

                    $this->_helper->json(array("data" => $property, "success" => true));
                }
            }
            else {
                if ($this->getUser() != null) {
                    Logger::err("user [" . $this->getUser()->getId() . "] attempted to modify properties predefined, but has no permission to do so.");
                }
                else {
                    Logger::err("attempt to modify properties predefined, but no user in session.");
                }
            }
        }
        else {
            // get list of types

            $list = new Property_Predefined_List();
            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            if($this->getParam("sort")) {
                $list->setOrderKey($this->getParam("sort"));
                $list->setOrder($this->getParam("dir"));
            }

            if($this->getParam("filter")) {
                $list->setCondition("`name` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `description` LIKE " . $list->quote("%".$this->getParam("filter")."%"));
            }

            $list->load();

            $properties = array();
            if (is_array($list->getProperties())) {
                foreach ($list->getProperties() as $property) {
                    $properties[] = $property;
                }
            }

            $this->_helper->json(array("data" => $properties, "success" => true, "total" => $list->getTotalCount()));
        }
    }

    private function deleteThumbnailTmpFiles(Asset_Image_Thumbnail_Config $thumbnail) {
        // delete all thumbnails which are using this config
        $files = scandir(PIMCORE_TEMPORARY_DIRECTORY);
        foreach ($files as $file) {
            if (preg_match("/^thumb_(.*)__" . $thumbnail->getName() . "/", $file)) {
                unlink(PIMCORE_TEMPORARY_DIRECTORY . "/" . $file);
            }
        }
    }

    private function deleteVideoThumbnailTmpFiles(Asset_Video_Thumbnail_Config $thumbnail) {
        // delete all thumbnails which are using this config
        $files = scandir(PIMCORE_TEMPORARY_DIRECTORY);
        foreach ($files as $file) {
            if (preg_match("/^video_(.*)__" . $thumbnail->getName() . "/", $file)) {
                unlink(PIMCORE_TEMPORARY_DIRECTORY . "/" . $file);
            }
        }
    }

    public function getSystemAction() {
        if ($this->getUser()->isAllowed("system_settings")) {
            $values = Pimcore_Config::getSystemConfig();

            if (($handle = fopen(PIMCORE_PATH . "/config/timezones.csv", "r")) !== FALSE) {
                while (($rowData = fgetcsv($handle, 10000, ",", '"')) !== false) {
                    $timezones[] = $rowData[0];
                }
                fclose($handle);
            }

            $locales = Pimcore_Tool::getSupportedLocales();
            $languageOptions = array();
            foreach ($locales as $short => $translation) {
                $languageOptions[] = array(
                    "language" => $short,
                    "display" => $translation . " ($short)"
                );
                $validLanguages[] = $short;
            }

            $valueArray = $values->toArray();
            $valueArray['general']['validLanguage'] = explode(",", $valueArray['general']['validLanguages']);

            //for "wrong" legacy values
            if (is_array($valueArray['general']['validLanguage'])) {
                foreach ($valueArray['general']['validLanguage'] as $existingValue) {
                    if (!in_array($existingValue, $validLanguages)) {
                        $languageOptions[] = array(
                            "language" => $existingValue,
                            "display" => $existingValue
                        );
                    }
                }
            }

            //debug email addresses - add as array ckogler
            if (!empty($valueArray['email']['debug']['emailaddresses'])) {
                $emailAddresses = explode(",", $valueArray['email']['debug']['emailaddresses']);
                if (is_array($emailAddresses)) {
                    foreach ($emailAddresses as $emailAddress) {
                        $valueArray['email']['debug']['emaildebugaddressesArray'][] = array("value" => $emailAddress);
                    }
                }
            }else{
                $valueArray['email']['debug']['emaildebugaddressesArray'][] = array("value" => '');
            }

            //cache exclude patterns - add as array
            if (!empty($valueArray['cache']['excludePatterns'])) {
                $patterns = explode(",", $valueArray['cache']['excludePatterns']);
                if (is_array($patterns)) {
                    foreach ($patterns as $pattern) {
                        $valueArray['cache']['excludePatternsArray'][] = array("value" => $pattern);
                    }
                }
            }

            //remove password from values sent to frontend
            $valueArray['database']["params"]['password'] = "##SECRET_PASS##";

            //admin users as array
            $adminUsers = array();
            $userList = new User_List();
            $userList->setCondition("admin = 1 and email is not null and email != ''");
            $users = $userList->load();
            if (is_array($users)) {
                foreach ($users as $user) {
                    $adminUsers[] = array("id" => $user->getId(), "username" => $user->getName());
                }
            }
            $adminUsers[] = array("id" => "", "username" => "-");

            $response = array(
                "values" => $valueArray,
                "adminUsers" => $adminUsers,
                "config" => array(
                    "timezones" => $timezones,
                    "languages" => $languageOptions,
                    "client_ip" => Pimcore_Tool::getClientIp(),
                    "google_private_key_exists" => file_exists(Pimcore_Google_Api::getPrivateKeyPath()),
                    "google_private_key_path" => Pimcore_Google_Api::getPrivateKeyPath()
                )
            );

            $this->_helper->json($response);
        } else {
            if ($this->getUser() != null) {
                Logger::err("user [" . $this->getUser()->getId() . "] attempted to view system settings, but has no permission to do so.");
            } else {
                Logger::err("attempt to view system settings, but no user in session.");
            }
        }

        $this->_helper->json(false);
    }

    public function setSystemAction() {
        if ($this->getUser()->isAllowed("system_settings")) {
            $values = Zend_Json::decode($this->getParam("data"));

            // convert all special characters to their entities so the xml writer can put it into the file
            $values = array_htmlspecialchars($values);

            $oldConfig = Pimcore_Config::getSystemConfig();
            $oldValues = $oldConfig->toArray();
            $smtpPassword = $values["email.smtp.auth.password"];
            if (empty($smtpPassword)) {
                $smtpPassword = $oldValues['email']['smtp']['auth']['password'];
            }

            $errorPages = array();
            foreach ($values as $key => $value) {
                if(strpos($key, "error_pages")) {
                    $keySteps = explode(".",$key);
                    $errorPages[$keySteps[2]] = $value;
                }
            }

            $settings = array(
                "general" => array(
                    "timezone" => $values["general.timezone"],
                    "php_cli" => $values["general.php_cli"],
                    "domain" => $values["general.domain"],
                    "redirect_to_maindomain" => $values["general.redirect_to_maindomain"],
                    "language" => $values["general.language"],
                    "validLanguages" => $values["general.validLanguages"],
                    "theme" => $values["general.theme"],
                    "loginscreenimageservice" => $values["general.loginscreenimageservice"],
                    "loginscreencustomimage" => $values["general.loginscreencustomimage"],
                    "debug" => $values["general.debug"],
                    "debug_ip" => $values["general.debug_ip"],
                    "http_auth" => array(
                        "username" => $values["general.http_auth.username"],
                        "password" => $values["general.http_auth.password"]
                    ),
                    "firephp" => $values["general.firephp"],
                    "loglevel" => array(
                        "debug" => $values["general.loglevel.debug"],
                        "info" => $values["general.loglevel.info"],
                        "notice" => $values["general.loglevel.notice"],
                        "warning" => $values["general.loglevel.warning"],
                        "error" => $values["general.loglevel.error"],
                        "critical" => $oldValues["general"]["loglevel"]["critical"],
                        "alert" => $oldValues["general"]["loglevel"]["alert"],
                        "emergency" => $oldValues["general"]["loglevel"]["emergency"],
                    ),
                    "debug_admin_translations" => $values["general.debug_admin_translations"],
                    "devmode" => $values["general.devmode"],
                    "logrecipient" => $values["general.logrecipient"],
                    "viewSuffix" => $values["general.viewSuffix"],
                    "targeting" => $values["general.targeting"]
                ),
                "database" => $oldValues["database"], // db cannot be changed here
                "documents" => array(
                    "versions" => array(
                        "days" => $values["documents.versions.days"],
                        "steps" => $values["documents.versions.steps"]
                    ),
                    "default_controller" => $values["documents.default_controller"],
                    "default_action" => $values["documents.default_action"],
                    "error_pages" => $errorPages,
                    "createredirectwhenmoved" => $values["documents.createredirectwhenmoved"],
                    "allowtrailingslash" => $values["documents.allowtrailingslash"],
                    "allowcapitals" => $values["documents.allowcapitals"]
                ),
                "objects" => array(
                    "versions" => array(
                        "days" => $values["objects.versions.days"],
                        "steps" => $values["objects.versions.steps"]
                    )
                ),
                "assets" => array(
                    "webdav" => array(
                        "hostname" => $values["assets.webdav.hostname"]
                    ),
                    "versions" => array(
                        "days" => $values["assets.versions.days"],
                        "steps" => $values["assets.versions.steps"]
                    ),
                    "ffmpeg" => $values["assets.ffmpeg"],
                    "qtfaststart" => $values["assets.qtfaststart"],
                    "icc_rgb_profile" => $values["assets.icc_rgb_profile"],
                    "icc_cmyk_profile" => $values["assets.icc_cmyk_profile"]
                ),
                "services" => array(
                    "translate" => array(
                        "apikey" => $values["services.translate.apikey"]
                    ),
                    "google" => array(
                        "client_id" => $values["services.google.client_id"],
                        "email" => $values["services.google.email"],
                        "simpleapikey" => $values["services.google.simpleapikey"],
                        "browserapikey" => $values["services.google.browserapikey"]
                    )
                ),
                "cache" => array(
                    "enabled" => $values["cache.enabled"],
                    "lifetime" => $values["cache.lifetime"],
                    "excludePatterns" => $values["cache.excludePatterns"],
                    "excludeCookie" => $values["cache.excludeCookie"]
                ),
                "outputfilters" => array(
                    "imagedatauri" => $values["outputfilters.imagedatauri"],
                    "less" => $values["outputfilters.less"],
                    "lesscpath" => $values["outputfilters.lesscpath"],
                    "cssminify" => $values["outputfilters.cssminify"],
                    "javascriptminify" => $values["outputfilters.javascriptminify"],
                    "javascriptminifyalgorithm" => $values["outputfilters.javascriptminifyalgorithm"]
                ),
                "email" => array(
                    "sender" => array(
                        "name" => $values["email.sender.name"],
                        "email" => $values["email.sender.email"]),
                    "return" => array(
                        "name" => $values["email.return.name"],
                        "email" => $values["email.return.email"]),
                    "method" => $values["email.method"],
                    "smtp" => array(
                        "host" => $values["email.smtp.host"],
                        "port" => $values["email.smtp.port"],
                        "ssl" => $values["email.smtp.ssl"],
                        "name" => $values["email.smtp.name"],
                        "auth" => array(
                            "method" => $values["email.smtp.auth.method"],
                            "username" => $values["email.smtp.auth.username"],
                            "password" => $smtpPassword
                        )
                    ),
                    "debug" => array( //ckogler
                        "emailaddresses" => $values["email.debug.emailAddresses"],
                    ),
                ),
                "webservice" => array(
                    "enabled" => $values["webservice.enabled"]
                ),
                "httpclient" => array(
                    "adapter" => $values["httpclient.adapter"],
                    "proxy_host" => $values["httpclient.proxy_host"],
                    "proxy_port" => $values["httpclient.proxy_port"],
                    "proxy_user" => $values["httpclient.proxy_user"],
                    "proxy_pass" => $values["httpclient.proxy_pass"],
                )
            ); 

            $config = new Zend_Config($settings, true);
            $writer = new Zend_Config_Writer_Xml(array(
                "config" => $config,
                "filename" => PIMCORE_CONFIGURATION_SYSTEM
            ));
            $writer->write();

            $this->_helper->json(array("success" => true));
        } else {
            if ($this->getUser() != null) {
                Logger::err("user [" . $this->getUser()->getId() . "] attempted to change system settings, but has no permission to do so.");
            } else {
                Logger::err("attempt to change system settings, but no user in session.");
            }
        }
        $this->_helper->json(false);
    }

    public function clearCacheAction() {
        if ($this->getUser()->isAllowed("clear_cache")) {

            // empty document cache
            Pimcore_Model_Cache::clearAll();

            // empty cache directory
            $files = scandir(PIMCORE_CACHE_DIRECTORY);
            foreach ($files as $file) {
                if (is_file(PIMCORE_CACHE_DIRECTORY . "/" . $file)) {
                    unlink(PIMCORE_CACHE_DIRECTORY . "/" . $file);
                }
            }

            $this->_helper->json(array("success" => true));
        }
        else {
            if ($this->getUser() != null) {
                Logger::err("user [" . $this->getUser()->getId() . "] attempted to clear cache, but has no permission to do so.");
            } else {
                Logger::err("attempt to clear cache, but no user in session.");
            }
        }
        $this->_helper->json(false);
    }

    public function clearOutputCacheAction() {
        if ($this->getUser()->isAllowed("clear_cache")) {

            // remove "output" out of the ignored tags, if a cache lifetime is specified
            Pimcore_Model_Cache::removeIgnoredTagOnClear("output");

            // empty document cache
            Pimcore_Model_Cache::clearTag("output");

            $this->_helper->json(array("success" => true));
        }
        else {
            if ($this->getUser() != null) {
                Logger::err("user [" . $this->getUser()->getId() . "] attempted to clear ouput cache, but has no permission to do so.");
            } else {
                Logger::err("attempt to clear output cache, but no user in session.");
            }
        }
        $this->_helper->json(false);
    }

    public function clearTemporaryFilesAction() {
        if ($this->getUser()->isAllowed("clear_temp_files")) {

            // public files
            $files = scandir(PIMCORE_TEMPORARY_DIRECTORY);
            foreach ($files as $file) {
                if (is_file(PIMCORE_TEMPORARY_DIRECTORY . "/" . $file)) {
                    unlink(PIMCORE_TEMPORARY_DIRECTORY . "/" . $file);
                }
            }

            // system files
            $files = scandir(PIMCORE_SYSTEM_TEMP_DIRECTORY);
            foreach ($files as $file) {
                if (is_file(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $file)) {
                    unlink(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $file);
                }
            }

            $this->_helper->json(array("success" => true));
        }
        else {
            if ($this->getUser() != null) {
                Logger::err("user [" . $this->getUser()->getId() . "] attempted to clear temporary files, but has no permission to do so.");
            }
            else {
                Logger::err("attempt to clear temporary files, but no user in session.");
            }
        }

        $this->_helper->json(false);
    }


    public function staticroutesAction() {

        if ($this->getParam("data")) {

            if ($this->getUser()->isAllowed("routes")) {

                $data = Zend_Json::decode($this->getParam("data"));

                foreach ($data as &$value) {
                    $value = trim($value);
                }

                if ($this->getParam("xaction") == "destroy") {
                    $route = Staticroute::getById($data);
                    $route->delete();

                    $this->_helper->json(array("success" => true, "data" => array()));
                }
                else if ($this->getParam("xaction") == "update") {
                    // save routes
                    $route = Staticroute::getById($data["id"]);
                    $route->setValues($data);

                    $route->save();

                    $this->_helper->json(array("data" => $route, "success" => true));
                }
                else if ($this->getParam("xaction") == "create") {

                    unset($data["id"]);

                    // save route
                    $route = new Staticroute();
                    $route->setValues($data);

                    $route->save();

                    $this->_helper->json(array("data" => $route, "success" => true));
                }
            } else {
                Logger::err("user [" . $this->getUser()->getId() . "] attempted to modify static routes, but has no permission to do so.");
            }
        }
        else {
            // get list of routes

            $list = new Staticroute_List();

            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            if($this->getParam("sort")) {
                $list->setOrderKey($this->getParam("sort"));
                $list->setOrder($this->getParam("dir"));
            }

            if($this->getParam("filter")) {
                $list->setCondition("`name` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `pattern` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `reverse` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `controller` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `action` LIKE " . $list->quote("%".$this->getParam("filter")."%"));
            }
            
            $list->load();

            $routes = array();
            foreach ($list->getRoutes() as $route) {
                $routes[] = $route;
            }

            $this->_helper->json(array("data" => $routes, "success" => true, "total" => $list->getTotalCount()));
        }

        $this->_helper->json(false);
    }

    public function translationsImportAction() {

        $admin = $this->getParam("admin");

        if ($this->getUser()->isAllowed("translations")) {

            $tmpFile = $_FILES["Filedata"]["tmp_name"];
            if($admin){
                Translation_Admin::importTranslationsFromFile($tmpFile,true);
            }else{
                Translation_Website::importTranslationsFromFile($tmpFile,true);
            }

            $this->_helper->json(array(
                "success" => true
            ), false);

            // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
            // Ext.form.Action.Submit and mark the submission as failed
            $this->getResponse()->setHeader("Content-Type", "text/html");

        } else {
            Logger::err("user [" . $this->getUser()->getId() . "] attempted to import translations csv, but has no permission to do so.");
            die();
        }

    }

    public function translationsExportAction() {

        $admin = $this->getParam("admin");

        if ($this->getUser()->isAllowed("translations")) {

            // clear translation cache
            Pimcore_Model_Cache::clearTag("translator");

            if ($admin) {
                $list = new Translation_Admin_List();
            } else {
                $list = new Translation_Website_List();
            }

            $list->setOrder("asc");
            $list->setOrderKey("key");
            $list->load();

            $translations = array();
            foreach ($list->getTranslations() as $t) {
                $translations[] = array_merge(array("key" => $t->getKey(), "date" => $t->getDate()), $t->getTranslations());
            }

            $languages = Pimcore_Tool::getValidLanguages();
            //header column
            $columns = array_keys($translations[0]);
            //add language columns which have no translations yet
            foreach ($languages as $l) {
                if (!in_array($l, $columns)) {
                    $columns[] = $l;
                }
            }

            $headerRow = array();
            foreach ($columns as $key => $value) {
                $headerRow[] = '"' . $value . '"';
            }
            $csv = implode(";", $headerRow) . "\r\n";

            foreach ($translations as $t) {
                $tempRow = array();
                foreach ($columns as $key) {
                    $value = $t[$key];
                    //clean value of evil stuff such as " and linebreaks
                    if (is_string($value)) {
                        $value = Pimcore_Tool_Text::removeLineBreaks($value);
                        $value = str_replace('"', '&quot;', $value);

                        $tempRow[$key] = '"' . $value . '"';
                    } else $tempRow[$key] = "";
                }
                $csv .= implode(";", $tempRow) . "\r\n";
            }
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=\"export.csv\"");
            echo $csv;
            die();
        } else {
            Logger::err("user [" . $this->getUser()->getId() . "] attempted to export translations csv, but has no permission to do so.");
            die();
        }

    }

    public function addAdminTranslationKeysAction() {
        $this->removeViewRenderer();

        $keys = $this->getParam("keys");
        if ($keys) {
            $availableLanguages = Pimcore_Tool_Admin::getLanguages();
            $data = Zend_Json_Decoder::decode($keys);
            foreach ($data as $translationData) {

                $t = null; // reset

                try{
                    $t = Translation_Admin::getByKey($translationData);
                } catch (Exception $e) {
                    Logger::log($e);
                }
                if (!$t instanceof Translation_Admin) {
                    $t = new Translation_Admin();

                    $t->setKey($translationData);
                    $t->setDate(time());

                    foreach ($availableLanguages as $lang) {
                        $t->addTranslation($lang, "");
                    }

                    try {
                        $t->save();
                    } catch (Exception $e) {
                        Logger::log($e);
                    }
                }
            }
        }
    }

    public function translationsAction() {

        $admin = $this->getParam("admin");

        if ($admin) {
            $class = "Translation_Admin";
        } else {
            $class = "Translation_Website";
        }

        if ($this->getUser()->isAllowed("translations")) {

            // clear translation cache
            Pimcore_Model_Cache::clearTags(array("translator","translate"));

            if ($this->getParam("data")) {

                $data = Zend_Json::decode($this->getParam("data"));

                if ($this->getParam("xaction") == "destroy") {
                    $data = Zend_Json::decode($this->getParam("data"));
                    $t = $class::getByKey($data);
                    $t->delete();

                    $this->_helper->json(array("success" => true, "data" => array()));
                }
                else if ($this->getParam("xaction") == "update") {
                    $t = $class::getByKey($data["key"]);

                    foreach ($data as $key => $value) {
                        if ($key != "key") {
                            $t->addTranslation($key, $value);
                        }
                    }

                    if ($data["key"]) {
                        $t->setKey($data["key"]);
                    }

                    $t->save();

                    $return = array_merge(array("key" => $t->getKey(), "date" => $t->getDate()), $t->getTranslations());

                    $this->_helper->json(array("data" => $return, "success" => true));
                }
                else if ($this->getParam("xaction") == "create") {

                    try {
                        $t = $class::getByKey($data["key"]);
                    }
                    catch (Exception $e) {

                        $t = new $class();

                        $t->setKey($data["key"]);
                        $t->setDate(time());

                        foreach (Pimcore_Tool::getValidLanguages() as $lang) {
                            $t->addTranslation($lang, "");
                        }
                        $t->save();
                    }

                    $return = array_merge(array(
                        "key" => $t->getKey(),
                        "date" => $t->getDate()
                    ), $t->getTranslations());

                    $this->_helper->json(array("data" => $return, "success" => true));
                }
            }
            else {
                // get list of types
                if ($admin) {
                    $list = new Translation_Admin_List();

                } else {
                    $list = new Translation_Website_List();
                }
                
                $list->setOrder("asc");
                $list->setOrderKey("key");

                if($this->getParam("dir")) {
                    $list->setOrder($this->getParam("dir"));
                }
                if($this->getParam("sort")) {
                    $list->setOrderKey($this->getParam("sort"));
                }

                $list->setLimit($this->getParam("limit"));
                $list->setOffset($this->getParam("start"));
                if ($this->getParam("filter")) {
                    $filterTerm = $list->quote("%".mb_strtolower($this->getParam("filter"))."%");
                    $list->setCondition("lower(`key`) LIKE " . $filterTerm . " OR lower(`text`) LIKE " . $filterTerm);
                }
                $list->load();

                $translations = array();
                foreach ($list->getTranslations() as $t) {
                    $translations[] = array_merge($t->getTranslations(), array("key" => $t->getKey(), "date" => $t->getDate()));
                }

                $this->_helper->json(array("data" => $translations, "success" => true, "total" => $list->getTotalCount()));
            }
        } else {
            Logger::err("user [" . $this->getUser()->getId() . "] attempted to access translations, but has no permission to do so.");
        }

        $this->_helper->json(false);
    }

    public function translationsCleanupAction() {

        $listClass = "Translation_" . ucfirst($this->getParam("type")) . "_List";
        if(class_exists($listClass)) {

            $list = new $listClass();
            $list->cleanup();

            Pimcore_Model_Cache::clearTags(array("translator","translate"));

            $this->_helper->json(array("success" => true));
        }

        $this->_helper->json(array("success" => false));
    }

    public function getAvailableLanguagesAction() {

        if ($languages = Pimcore_Tool::getValidLanguages()) {
            $this->_helper->json($languages);
        }

        $t = new Translation_Website();
        $this->_helper->json($t->getAvailableLanguages());
    }

    public function getAvailableAdminLanguagesAction() {

        $langs = array();
        $availableLanguages = Pimcore_Tool_Admin::getLanguages();
        $locales = Pimcore_Tool::getSupportedLocales();

        foreach ($availableLanguages as $lang) {
            if(array_key_exists($lang, $locales)) {
                $langs[] = array(
                    "language" => $lang,
                    "display" => $locales[$lang]
                );
            }
        }

        $this->_helper->json($langs);
    }

    public function redirectsAction() {

        if ($this->getParam("data")) {

            if ($this->getUser()->isAllowed("redirects")) {

                if ($this->getParam("xaction") == "destroy") {

                    $id = Zend_Json::decode($this->getParam("data"));

                    $redirect = Redirect::getById($id);
                    $redirect->delete();

                    $this->_helper->json(array("success" => true, "data" => array()));
                }
                else if ($this->getParam("xaction") == "update") {

                    $data = Zend_Json::decode($this->getParam("data"));

                    // save redirect
                    $redirect = Redirect::getById($data["id"]);

                    if ($data["target"]) {
                        if ($doc = Document::getByPath($data["target"])) {
                            $data["target"] = $doc->getId();
                        }
                    }

                    $redirect->setValues($data);

                    $redirect->save();

                    $redirectTarget = $redirect->getTarget();
                    if (is_numeric($redirectTarget)) {
                        if ($doc = Document::getById(intval($redirectTarget))) {
                            $redirect->setTarget($doc->getFullPath());
                        }
                    }
                    $this->_helper->json(array("data" => $redirect, "success" => true));
                }
                else if ($this->getParam("xaction") == "create") {
                    $data = Zend_Json::decode($this->getParam("data"));
                    unset($data["id"]);

                    // save route
                    $redirect = new Redirect();

                    if ($data["target"]) {
                        if ($doc = Document::getByPath($data["target"])) {
                            $data["target"] = $doc->getId();
                        }
                    }

                    $redirect->setValues($data);

                    $redirect->save();

                    $redirectTarget = $redirect->getTarget();
                    if (is_numeric($redirectTarget)) {
                        if ($doc = Document::getById(intval($redirectTarget))) {
                            $redirect->setTarget($doc->getFullPath());
                        }
                    }
                    $this->_helper->json(array("data" => $redirect, "success" => true));
                }
            } else {
                Logger::err("user [" . $this->getUser()->getId() . "] attempted to modify static routes, but has no permission to do so.");
            }
        }
        else {
            // get list of routes

            $list = new Redirect_List();
            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            if($this->getParam("sort")) {
                $list->setOrderKey($this->getParam("sort"));
                $list->setOrder($this->getParam("dir"));
            }

            if($this->getParam("filter")) {
                $list->setCondition("`source` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `target` LIKE " . $list->quote("%".$this->getParam("filter")."%"));
            }
            

            $list->load();

            $redirects = array();
            foreach ($list->getRedirects() as $redirect) {

                if ($link = $redirect->getTarget()) {
                    if (is_numeric($link)) {
                        if ($doc = Document::getById(intval($link))) {
                            $redirect->setTarget($doc->getFullPath());
                        }
                    }
                }

                $redirects[] = $redirect;
            }

            $this->_helper->json(array("data" => $redirects, "success" => true, "total" => $list->getTotalCount()));
        }

        $this->_helper->json(false);
    }


    public function glossaryAction() {

        if ($this->getParam("data")) {

            if ($this->getUser()->isAllowed("glossary")) {

                Pimcore_Model_Cache::clearTag("glossary");

                if ($this->getParam("xaction") == "destroy") {

                    $id = Zend_Json::decode($this->getParam("data"));

                    $glossary = Glossary::getById($id);
                    $glossary->delete();

                    $this->_helper->json(array("success" => true, "data" => array()));
                }
                else if ($this->getParam("xaction") == "update") {

                    $data = Zend_Json::decode($this->getParam("data"));

                    // save glossary
                    $glossary = Glossary::getById($data["id"]);

                    if ($data["link"]) {
                        if ($doc = Document::getByPath($data["link"])) {
                            $tmpLink = $data["link"];
                            $data["link"] = $doc->getId();
                        }
                    }


                    $glossary->setValues($data);

                    $glossary->save();

                    if ($link = $glossary->getLink()) {
                        if (intval($link) > 0) {
                            if ($doc = Document::getById(intval($link))) {
                                $glossary->setLink($doc->getFullPath());
                            }
                        }
                    }

                    $this->_helper->json(array("data" => $glossary, "success" => true));
                }
                else if ($this->getParam("xaction") == "create") {
                    $data = Zend_Json::decode($this->getParam("data"));
                    unset($data["id"]);

                    // save glossary
                    $glossary = new Glossary();

                    if ($data["link"]) {
                        if ($doc = Document::getByPath($data["link"])) {
                            $tmpLink = $data["link"];
                            $data["link"] = $doc->getId();
                        }
                    }

                    $glossary->setValues($data);

                    $glossary->save();

                    if ($link = $glossary->getLink()) {
                        if (intval($link) > 0) {
                            if ($doc = Document::getById(intval($link))) {
                                $glossary->setLink($doc->getFullPath());
                            }
                        }
                    }

                    $this->_helper->json(array("data" => $glossary, "success" => true));
                }
            } else {
                Logger::err("user [" . $this->getUser()->getId() . "] attempted to modify static routes, but has no permission to do so.");
            }
        }
        else {
            // get list of routes

            $list = new Glossary_List();
            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            if($this->getParam("sort")) {
                $list->setOrderKey($this->getParam("sort"));
                $list->setOrder($this->getParam("dir"));
            }

            if($this->getParam("filter")) {
                $list->setCondition("`text` LIKE " . $list->quote("%".$this->getParam("filter")."%"));
            }

            $list->load();

            $glossaries = array();
            foreach ($list->getGlossary() as $glossary) {

                if ($link = $glossary->getLink()) {
                    if (intval($link) > 0) {
                        if ($doc = Document::getById(intval($link))) {
                            $glossary->setLink($doc->getFullPath());
                        }
                    }
                }

                $glossaries[] = $glossary;
            }

            $this->_helper->json(array("data" => $glossaries, "success" => true, "total" => $list->getTotalCount()));
        }

        $this->_helper->json(false);
    }

    public function systemlogAction() {

        $file = PIMCORE_LOG_DEBUG;
        $lines = 400;

        $handle = fopen($file, "r");
        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = array();
        while ($linecounter > 0) {
            $t = " ";
            while ($t != "\n") {
                if (fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($handle);
                $pos--;
            }
            $linecounter--;
            if ($beginning) {
                rewind($handle);
            }
            $text[$lines - $linecounter - 1] = fgets($handle);
            if ($beginning) break;
        }
        fclose($handle);

        //$lines = array_reverse($text);
        $lines = $text;

        $this->view->lines = $lines;
    }

    public function getAvailableSitesAction() {

        $sitesList = new Site_List();
        $sitesObjects = $sitesList->load();
        $sites = array();

        foreach ($sitesObjects as $site) {

            if ($site->getRootDocument()) {
                $domains = $site->getDomains();
                $domain = $domains[0];

                if ($domain) {
                    $sites[] = array(
                        "id" => $site->getId(),
                        "rootId" => $site->getRootId(),
                        "domains" => implode(",", $site->getDomains()),
                        "rootPath" => $site->getRootPath(),
                        "domain" => $domain
                    );
                }
            }
            else {
                // site is useless, parent doesn't exist anymore
                $site->delete();
            }
        }

        $this->_helper->json($sites);
    }

    public function websiteSaveAction() {

        $data = Zend_Json::decode($this->getParam("data"));

        // convert all special characters to their entities to ensure that Zend_Config can write it
        foreach ($data as &$setting) {
            $setting["data"] = htmlspecialchars($setting["data"],ENT_COMPAT,"UTF-8");
        }

        $config = new Zend_Config($data, true);
        $writer = new Zend_Config_Writer_Xml(array(
            "config" => $config,
            "filename" => PIMCORE_CONFIGURATION_DIRECTORY . "/website.xml"
        ));
        $writer->write();

        // clear cache
        Pimcore_Model_Cache::clearTags(array("output", "system","website_config"));


        $this->_helper->json(array("success" => true));
    }

    public function websiteLoadAction() {

        $configFile = PIMCORE_CONFIGURATION_DIRECTORY . "/website.xml";

        // file doesn't exist => send empty array to frontend
        if (!is_file($configFile)) {
            $this->_helper->json(array(
                "settings" => array()
            ));
            return;
        }

        $rawConfig = new Zend_Config_Xml($configFile);
        $arrayData = $rawConfig->toArray();
        $data = array();

        foreach ($arrayData as $key => $value) {

            if ($value["type"] == "bool") {
                $value["data"] = (bool) $value["data"];
            }

            $data[] = array(
                "name" => $key,
                "type" => $value["type"],
                "data" => $value["data"],
                "siteId" => ($value["siteId"] > 0) ? (int)$value["siteId"] : '',
            );
        }

        $this->_helper->json(array(
            "settings" => $data
        ));
    }

    public function thumbnailAdapterCheckAction () {
        $instance = Pimcore_Image::getInstance();
        if($instance instanceof Pimcore_Image_Adapter_GD) {
            echo '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                 $this->view->translate("important_use_imagick_pecl_extensions_for_best_results_gd_is_just_a_fallback_with_less_quality") .
                 '</span>';
        }

        exit;
    }


    public function thumbnailTreeAction () {

        $dir = Asset_Image_Thumbnail_Config::getWorkingDir();

        $pipelines = array();
        $files = scandir($dir);
        foreach ($files as $file) {
            if(strpos($file, ".xml")) {
                $name = str_replace(".xml", "", $file);
                $pipelines[] = array(
                    "id" => $name,
                    "text" => $name
                );
            }
        }

        $this->_helper->json($pipelines);
    }

    public function thumbnailAddAction () {


        $alreadyExist = false;

        try {
            Asset_Image_Thumbnail_Config::getByName($this->getParam("name"));
            $alreadyExist = true;
        } catch (Exception $e) {
            $alreadyExist = false;
        }

        if(!$alreadyExist) {
            $pipe = new Asset_Image_Thumbnail_Config();
            $pipe->setName($this->getParam("name"));
            $pipe->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $pipe->getName()));
    }

    public function thumbnailDeleteAction () {

        $pipe = Asset_Image_Thumbnail_Config::getByName($this->getParam("name"));
        $pipe->delete();

        $this->_helper->json(array("success" => true));
    }


    public function thumbnailGetAction () {

        $pipe = Asset_Image_Thumbnail_Config::getByName($this->getParam("name"));
        //$pipe->delete();

        $this->_helper->json($pipe);
    }


    public function thumbnailUpdateAction () {

        $pipe = Asset_Image_Thumbnail_Config::getByName($this->getParam("name"));
        $settingsData = Zend_Json::decode($this->getParam("settings"));
        $itemsData = Zend_Json::decode($this->getParam("items"));

        foreach ($settingsData as $key => $value) {
            $setter = "set" . ucfirst($key);
            if(method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }
        }

        $pipe->resetItems();

        foreach ($itemsData as $item) {

            $type = $item["type"];
            unset($item["type"]);

            $pipe->addItem($type, $item);
        }

        $pipe->save();

        $this->deleteThumbnailTmpFiles($pipe);

        $this->_helper->json(array("success" => true));
    }


    public function videoThumbnailAdapterCheckAction () {

        if(!Pimcore_Video::isAvailable()) {
            echo '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
             $this->view->translate("php_cli_binary_and_or_ffmpeg_binary_setting_is_missing") .
             '</span>';
        }

        exit;
    }


    public function videoThumbnailTreeAction () {

        $dir = Asset_Video_Thumbnail_Config::getWorkingDir();

        $pipelines = array();
        $files = scandir($dir);
        foreach ($files as $file) {
            if(strpos($file, ".xml")) {
                $name = str_replace(".xml", "", $file);
                $pipelines[] = array(
                    "id" => $name,
                    "text" => $name
                );
            }
        }

        $this->_helper->json($pipelines);
    }

    public function videoThumbnailAddAction () {


        $alreadyExist = false;

        try {
            Asset_Video_Thumbnail_Config::getByName($this->getParam("name"));
            $alreadyExist = true;
        } catch (Exception $e) {
            $alreadyExist = false;
        }

        if(!$alreadyExist) {
            $pipe = new Asset_Video_Thumbnail_Config();
            $pipe->setName($this->getParam("name"));
            $pipe->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $pipe->getName()));
    }

    public function videoThumbnailDeleteAction () {

        $pipe = Asset_Video_Thumbnail_Config::getByName($this->getParam("name"));
        $pipe->delete();

        $this->_helper->json(array("success" => true));
    }


    public function videoThumbnailGetAction () {

        $pipe = Asset_Video_Thumbnail_Config::getByName($this->getParam("name"));
        $this->_helper->json($pipe);
    }


    public function videoThumbnailUpdateAction () {

        $pipe = Asset_Video_Thumbnail_Config::getByName($this->getParam("name"));
        $data = Zend_Json::decode($this->getParam("configuration"));

        $items = array();
        foreach ($data as $key => $value) {
            $setter = "set" . ucfirst($key);
            if(method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }

            if(strpos($key,"item.") === 0) {
                $cleanKeyParts = explode(".",$key);
                $items[$cleanKeyParts[1]][$cleanKeyParts[2]] = $value;
            }
        }

        $pipe->resetItems();
        foreach ($items as $item) {

            $type = $item["type"];
            unset($item["type"]);

            $pipe->addItem($type, $item);
        }

        $pipe->save();

        $this->deleteVideoThumbnailTmpFiles($pipe);

        $this->_helper->json(array("success" => true));
    }

    public function robotsTxtAction () {

        $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY . "/robots.txt";

        if($this->getParam("data") !== null) {
            // save data
            file_put_contents($robotsPath, $this->getParam("data"));

            $this->_helper->json(array(
                "success" => true
            ));
        } else {
            // get data
            $data = "";
            if(is_file($robotsPath)) {
                $data = file_get_contents($robotsPath);
            }

            $this->_helper->json(array(
                "success" => true,
                "data" => $data,
                "onFileSystem" => file_exists(PIMCORE_DOCUMENT_ROOT . "/robots.txt")
            ));
        }
    }



    public function tagManagementTreeAction () {

        $dir = Tool_Tag_Config::getWorkingDir();

        $tags = array();
        $files = scandir($dir);
        foreach ($files as $file) {
            if(strpos($file, ".xml")) {
                $name = str_replace(".xml", "", $file);
                $tags[] = array(
                    "id" => $name,
                    "text" => $name
                );
            }
        }

        $this->_helper->json($tags);
    }

    public function tagManagementAddAction () {

        try {
            Tool_Tag_Config::getByName($this->getParam("name"));
            $alreadyExist = true;
        } catch (Exception $e) {
            $alreadyExist = false;
        }

        if(!$alreadyExist) {
            $tag = new Tool_Tag_Config();
            $tag->setName($this->getParam("name"));
            $tag->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $tag->getName()));
    }

    public function tagManagementDeleteAction () {

        $tag = Tool_Tag_Config::getByName($this->getParam("name"));
        $tag->delete();

        $this->_helper->json(array("success" => true));
    }


    public function tagManagementGetAction () {

        $tag = Tool_Tag_Config::getByName($this->getParam("name"));
        $this->_helper->json($tag);
    }


    public function tagManagementUpdateAction () {

        $tag = Tool_Tag_Config::getByName($this->getParam("name"));
        $data = Zend_Json::decode($this->getParam("configuration"));
        $data = array_htmlspecialchars($data);

        $items = array();
        foreach ($data as $key => $value) {
            $setter = "set" . ucfirst($key);
            if(method_exists($tag, $setter)) {
                $tag->$setter($value);
            }

            if(strpos($key,"item.") === 0) {
                $cleanKeyParts = explode(".",$key);
                $items[$cleanKeyParts[1]][$cleanKeyParts[2]] = $value;
            }
        }

        $tag->resetItems();
        foreach ($items as $item) {
            $tag->addItem($item);
        }

        // parameters get/post
        $params = array();
        for ($i=0; $i<5; $i++) {
            $params[] = array(
                "name" => $data["params.name" . $i],
                "value" => $data["params.value" . $i]
            );
        }
        $tag->setParams($params);

        $tag->save();

        $this->_helper->json(array("success" => true));
    }
}
