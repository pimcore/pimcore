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

use Pimcore\Model\Cache; 
use Pimcore\Tool;
use Pimcore\Config;
use Pimcore\Model\Metadata;
use Pimcore\Model\Property;
use Pimcore\Model\Asset;
use Pimcore\Model\WebsiteSetting;
use Pimcore\Model\Document;
use Pimcore\Model\Glossary;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Redirect;
use Pimcore\Model\Element;
use Pimcore\Model;

class Admin_SettingsController extends \Pimcore\Controller\Action\Admin {


    public function metadataAction() {

        if ($this->getParam("data")) {
            $this->checkPermission("predefined_properties");

            if ($this->getParam("xaction") == "destroy") {

                $id = \Zend_Json::decode($this->getParam("data"));

                $metadata = Metadata\Predefined::getById($id);
                $metadata->delete();

                $this->_helper->json(array("success" => true, "data" => array()));
            }
            else if ($this->getParam("xaction") == "update") {

                $data = \Zend_Json::decode($this->getParam("data"));

                // save type
                $metadata = Metadata\Predefined::getById($data["id"]);

                $metadata->setValues($data);

                $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage($metadata->getName(), $metadata->getLanguage());
                if ($existingItem && $existingItem->getId() != $metadata->getId()) {
                    $this->_helper->json(array("message" => "rule_violation", "success" => false));
                }

                $metadata->minimize();
                $metadata->save();
                $metadata->expand();

                $this->_helper->json(array("data" => $metadata, "success" => true));
            }
            else if ($this->getParam("xaction") == "create") {
                $data = \Zend_Json::decode($this->getParam("data"));
                unset($data["id"]);

                // save type
                $metadata = Metadata\Predefined::create();

                $metadata->setValues($data);

                $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage($metadata->getName(), $metadata->getLanguage());
                if ($existingItem) {
                    $this->_helper->json(array("message" => "rule_violation", "success" => false));
                }

                $metadata->save();

                $this->_helper->json(array("data" => $metadata, "success" => true));
            }
        }
        else {
            // get list of types

            $list = new Metadata\Predefined\Listing();
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
            if (is_array($list->getDefinitions())) {
                foreach ($list->getDefinitions() as $metadata) {
                    $metadata->expand();
                    $properties[] = $metadata;
                }
            }

            $this->_helper->json(array("data" => $properties, "success" => true, "total" => $list->getTotalCount()));
        }
    }

    public function getPredefinedMetadataAction() {
        $type = $this->getParam("type");
        $subType = $this->getParam("subType");
        $list = Metadata\Predefined\Listing::getByTargetType($type, array($subType));
        $result = array();
        foreach ($list as $item) {
            /** @var $item Metadata\Predefined */
            $item->expand();
            $result[] = $item;
        }


        $this->_helper->json(array("data" => $result, "success" => true));

    }

    public function propertiesAction() {

        if ($this->getParam("data")) {
            $this->checkPermission("predefined_properties");

            if ($this->getParam("xaction") == "destroy") {

                $id = \Zend_Json::decode($this->getParam("data"));

                $property = Property\Predefined::getById($id);
                $property->delete();

                $this->_helper->json(array("success" => true, "data" => array()));
            }
            else if ($this->getParam("xaction") == "update") {

                $data = \Zend_Json::decode($this->getParam("data"));

                // save type
                $property = Property\Predefined::getById($data["id"]);
                $property->setValues($data);

                $property->save();

                $this->_helper->json(array("data" => $property, "success" => true));
            }
            else if ($this->getParam("xaction") == "create") {
                $data = \Zend_Json::decode($this->getParam("data"));
                unset($data["id"]);

                // save type
                $property = Property\Predefined::create();
                $property->setValues($data);

                $property->save();

                $this->_helper->json(array("data" => $property, "success" => true));
            }
        }
        else {
            // get list of types

            $list = new Property\Predefined\Listing();
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


    private function deleteThumbnailFolders ($root, $thumbnailName) {
        // delete all thumbnails which are using this config
        function delete ($dir, $thumbnail, &$matches = array()) {
            $dirs = glob($dir . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                if(preg_match('@/thumb__' . $thumbnail . '$@', $dir) || preg_match('@/thumb__' . $thumbnail . '_auto@', $dir)) {
                    recursiveDelete($dir);
                }
                delete($dir, $thumbnail, $matches);
            }
            return $matches;
        };

        delete($root, $thumbnailName);
    }

    private function deleteThumbnailTmpFiles(Asset\Image\Thumbnail\Config $thumbnail) {
        $this->deleteThumbnailFolders(PIMCORE_TEMPORARY_DIRECTORY . "/image-thumbnails", $thumbnail->getName());
    }

    private function deleteVideoThumbnailTmpFiles(Asset\Video\Thumbnail\Config $thumbnail) {
        $this->deleteThumbnailFolders(PIMCORE_TEMPORARY_DIRECTORY . "/video-thumbnails", $thumbnail->getName());
    }

    public function getSystemAction() {

        $this->checkPermission("system_settings");

        $values = Config::getSystemConfig();

        if (($handle = fopen(PIMCORE_PATH . "/config/timezones.csv", "r")) !== FALSE) {
            while (($rowData = fgetcsv($handle, 10000, ",", '"')) !== false) {
                $timezones[] = $rowData[0];
            }
            fclose($handle);
        }

        $locales = Tool::getSupportedLocales();
        $languageOptions = array();
        foreach ($locales as $short => $translation) {
            if(!empty($short)) {
                $languageOptions[] = array(
                    "language" => $short,
                    "display" => $translation . " ($short)"
                );
                $validLanguages[] = $short;
            }
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
        $userList = new Model\User\Listing();
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
                "client_ip" => Tool::getClientIp(),
                "google_private_key_exists" => file_exists(\Pimcore\Google\Api::getPrivateKeyPath()),
                "google_private_key_path" => \Pimcore\Google\Api::getPrivateKeyPath()
            )
        );

        $this->_helper->json($response);

        $this->_helper->json(false);
    }

    public function setSystemAction() {

        $this->checkPermission("system_settings");

        $values = \Zend_Json::decode($this->getParam("data"));

        // convert all special characters to their entities so the xml writer can put it into the file
        $values = array_htmlspecialchars($values);

        // email settings
        $oldConfig = Config::getSystemConfig();
        $oldValues = $oldConfig->toArray();

        // fallback languages
        $fallbackLanguages = array();
        $languages = explode(",", $values["general.validLanguages"]);
        $filteredLanguages = array();
        foreach($languages as $language) {
            if(isset($values["general.fallbackLanguages." . $language])) {
                $fallbackLanguages[$language] = str_replace(" ", "", $values["general.fallbackLanguages." . $language]);
            }

            if(\Zend_Locale::isLocale($language)) {
                $filteredLanguages[] = $language;
            }
        }

        // delete views from old languages
        $oldLanguages = explode(",",$oldConfig->get("general")->toArray()["validLanguages"]);
        $newLanguages = $languages;
        $dbName = $oldConfig->get("database")->toArray()["params"]["dbname"];
        foreach ($oldLanguages as $oldLanguage){
            if (!in_array($oldLanguage, $newLanguages)) {
                $this->deleteViews($oldLanguage, $dbName);
            }
        }

        $settings = array(
            "general" => array(
                "timezone" => $values["general.timezone"],
                "php_cli" => $values["general.php_cli"],
                "domain" => $values["general.domain"],
                "redirect_to_maindomain" => $values["general.redirect_to_maindomain"],
                "language" => $values["general.language"],
                "validLanguages" => implode(",", $filteredLanguages),
                "fallbackLanguages" => $fallbackLanguages,
                "theme" => $values["general.theme"],
                "contactemail" => $values["general.contactemail"],
                "loginscreencustomimage" => $values["general.loginscreencustomimage"],
                "disableusagestatistics" => $values["general.disableusagestatistics"],
                "debug" => $values["general.debug"],
                "debug_ip" => $values["general.debug_ip"],
                "http_auth" => array(
                    "username" => $values["general.http_auth.username"],
                    "password" => $values["general.http_auth.password"]
                ),
                "custom_php_logfile" => $values["general.custom_php_logfile"],
                "debugloglevel" => $values["general.debugloglevel"],
                "disable_whoops" => $values["general.disable_whoops"],
                "debug_admin_translations" => $values["general.debug_admin_translations"],
                "devmode" => $values["general.devmode"],
                "logrecipient" => $values["general.logrecipient"],
                "viewSuffix" => $values["general.viewSuffix"],
                "instanceIdentifier" => $values["general.instanceIdentifier"],
            ),
            "database" => $oldValues["database"], // db cannot be changed here
            "documents" => array(
                "versions" => array(
                    "days" => $values["documents.versions.days"],
                    "steps" => $values["documents.versions.steps"]
                ),
                "default_controller" => $values["documents.default_controller"],
                "default_action" => $values["documents.default_action"],
                "error_pages" => array(
                    "default" => $values["documents.error_pages.default"]
                ),
                "createredirectwhenmoved" => $values["documents.createredirectwhenmoved"],
                "allowtrailingslash" => $values["documents.allowtrailingslash"],
                "allowcapitals" => $values["documents.allowcapitals"],
                "generatepreview" => $values["documents.generatepreview"],
                "wkhtmltoimage" => $values["documents.wkhtmltoimage"],
                "wkhtmltopdf" => $values["documents.wkhtmltopdf"]
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
                "ghostscript" => $values["assets.ghostscript"],
                "libreoffice" => $values["assets.libreoffice"],
                "pngcrush" => $values["assets.pngcrush"],
                "imgmin" => $values["assets.imgmin"],
                "jpegoptim" => $values["assets.jpegoptim"],
                "pdftotext" => $values["assets.pdftotext"],
                "icc_rgb_profile" => $values["assets.icc_rgb_profile"],
                "icc_cmyk_profile" => $values["assets.icc_cmyk_profile"],
                "hide_edit_image" => $values["assets.hide_edit_image"]
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
                "less" => $values["outputfilters.less"],
                "lesscpath" => $values["outputfilters.lesscpath"]
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

        // email & newsletter
        foreach(array("email", "newsletter") as $type) {
            $smtpPassword = $values[$type . ".smtp.auth.password"];
            if (empty($smtpPassword)) {
                $smtpPassword = $oldValues[$type]['smtp']['auth']['password'];
            }

            $settings[$type] = array(
                "sender" => array(
                    "name" => $values[$type . ".sender.name"],
                    "email" => $values[$type . ".sender.email"]),
                "return" => array(
                    "name" => $values[$type . ".return.name"],
                    "email" => $values[$type . ".return.email"]),
                "method" => $values[$type . ".method"],
                "smtp" => array(
                    "host" => $values[$type . ".smtp.host"],
                    "port" => $values[$type . ".smtp.port"],
                    "ssl" => $values[$type . ".smtp.ssl"],
                    "name" => $values[$type . ".smtp.name"],
                    "auth" => array(
                        "method" => $values[$type . ".smtp.auth.method"],
                        "username" => $values[$type . ".smtp.auth.username"],
                        "password" => $smtpPassword
                    )
                )
            );

            if(array_key_exists($type . ".debug.emailAddresses", $values)) {
                $settings[$type]["debug"] = array("emailaddresses" => $values[$type . ".debug.emailAddresses"]);
            }

            if(array_key_exists($type . ".bounce.type", $values)) {
                $settings[$type]["bounce"] = array(
                    "type" => $values[$type . ".bounce.type"],
                    "maildir" => $values[$type . ".bounce.maildir"],
                    "mbox" => $values[$type . ".bounce.mbox"],
                    "imap" => array(
                        "host" => $values[$type . ".bounce.imap.host"],
                        "port" => $values[$type . ".bounce.imap.port"],
                        "username" => $values[$type . ".bounce.imap.username"],
                        "password" => $values[$type . ".bounce.imap.password"],
                        "ssl" => $values[$type . ".bounce.imap.ssl"]
                    )
                );
            }
        }
        $settings["newsletter"]["usespecific"] = $values["newsletter.usespecific"];

        $config = new \Zend_Config($settings, true);
        $writer = new \Zend_Config_Writer_Xml(array(
            "config" => $config,
            "filename" => PIMCORE_CONFIGURATION_SYSTEM
        ));
        $writer->write();

        $this->_helper->json(array("success" => true));

    }

    public function clearCacheAction() {

        $this->checkPermission("clear_cache");

        // empty document cache
        Cache::clearAll();

        $db = \Pimcore\Resource::get();
        $db->query("truncate table cache_tags");
        $db->query("truncate table cache");

        // empty cache directory
        recursiveDelete(PIMCORE_CACHE_DIRECTORY, false);
        // PIMCORE-1854 - recreate .dummy file => should remain
        \Pimcore\File::put(PIMCORE_CACHE_DIRECTORY . "/.dummy", "");

        $this->_helper->json(array("success" => true));

    }

    public function clearOutputCacheAction() {

        $this->checkPermission("clear_cache");

        // remove "output" out of the ignored tags, if a cache lifetime is specified
        Cache::removeIgnoredTagOnClear("output");

        // empty document cache
        Cache::clearTag("output");

        $this->_helper->json(array("success" => true));
    }

    public function clearTemporaryFilesAction() {

        $this->checkPermission("clear_temp_files");

        // public files
        recursiveDelete(PIMCORE_TEMPORARY_DIRECTORY, false);

        // system files
        recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY, false);

        // recreate .dummy files # PIMCORE-2629
        \Pimcore\File::put(PIMCORE_TEMPORARY_DIRECTORY . "/.dummy", "");
        \Pimcore\File::put(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/.dummy", "");

        $this->_helper->json(array("success" => true));
    }


    public function staticroutesAction() {

        if ($this->getParam("data")) {

            $this->checkPermission("routes");

            $data = \Zend_Json::decode($this->getParam("data"));

            if(is_array($data)) {
                foreach ($data as &$value) {
                    $value = trim($value);
                }
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
        }
        else {
            // get list of routes

            $list = new Staticroute\Listing();

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

    public function getAvailableLanguagesAction() {

        if ($languages = Tool::getValidLanguages()) {
            $this->_helper->json($languages);
        }

        $t = new Model\Translation\Website();
        $this->_helper->json($t->getAvailableLanguages());
    }

    public function getAvailableAdminLanguagesAction() {

        $langs = array();
        $availableLanguages = Tool\Admin::getLanguages();
        $locales = Tool::getSupportedLocales();

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

            $this->checkPermission("redirects");

            if ($this->getParam("xaction") == "destroy") {

                $id = \Zend_Json::decode($this->getParam("data"));

                $redirect = Redirect::getById($id);
                $redirect->delete();

                $this->_helper->json(array("success" => true, "data" => array()));
            }
            else if ($this->getParam("xaction") == "update") {

                $data = \Zend_Json::decode($this->getParam("data"));

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
                $data = \Zend_Json::decode($this->getParam("data"));
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
        }
        else {
            // get list of routes

            $list = new Redirect\Listing();
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
            $this->checkPermission("glossary");

            Cache::clearTag("glossary");

            if ($this->getParam("xaction") == "destroy") {

                $id = \Zend_Json::decode($this->getParam("data"));

                $glossary = Glossary::getById($id);
                $glossary->delete();

                $this->_helper->json(array("success" => true, "data" => array()));
            }
            else if ($this->getParam("xaction") == "update") {

                $data = \Zend_Json::decode($this->getParam("data"));

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
                $data = \Zend_Json::decode($this->getParam("data"));
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
        }
        else {
            // get list of glossaries

            $list = new Glossary\Listing();
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

    public function getAvailableSitesAction() {

        $sitesList = new Model\Site\Listing();
        $sitesObjects = $sitesList->load();
        $sites = array(array(
            "id" => "",
            "rootId" => 1,
            "domains" => "",
            "rootPath" => "/",
            "domain" => $this->view->translate("main_site")
        ));

        foreach ($sitesObjects as $site) {

            if ($site->getRootDocument()) {
                if ($site->getMainDomain()) {
                    $sites[] = array(
                        "id" => $site->getId(),
                        "rootId" => $site->getRootId(),
                        "domains" => implode(",", $site->getDomains()),
                        "rootPath" => $site->getRootPath(),
                        "domain" => $site->getMainDomain()
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

    public function getAvailableCountriesAction() {
        $countries = \Zend_Locale::getTranslationList('territory');
        asort($countries);

        $options = array();

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = array(
                    "key" => $translation . " (" . $short . ")" ,
                    "value" => $short
                );
            }
        }

        $result = array("data" => $options, "success" => true, "total" => count($options));

        $this->_helper->json($result);
    }


    public function thumbnailAdapterCheckAction () {
        $instance = \Pimcore\Image::getInstance();
        if($instance instanceof \Pimcore\Image\Adapter\GD) {
            echo '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                $this->view->translate("important_use_imagick_pecl_extensions_for_best_results_gd_is_just_a_fallback_with_less_quality") .
                '</span>';
        }

        exit;
    }


    public function thumbnailTreeAction () {

        $this->checkPermission("thumbnails");

        $dir = Asset\Image\Thumbnail\Config::getWorkingDir();

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

        $this->checkPermission("thumbnails");

        $alreadyExist = false;

        try {
            Asset\Image\Thumbnail\Config::getByName($this->getParam("name"));
            $alreadyExist = true;
        } catch (\Exception $e) {
            $alreadyExist = false;
        }

        if(!$alreadyExist) {
            $pipe = new Asset\Image\Thumbnail\Config();
            $pipe->setName($this->getParam("name"));
            $pipe->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $pipe->getName()));
    }

    public function thumbnailDeleteAction () {

        $this->checkPermission("thumbnails");

        $pipe = Asset\Image\Thumbnail\Config::getByName($this->getParam("name"));
        $pipe->delete();

        $this->_helper->json(array("success" => true));
    }


    public function thumbnailGetAction () {

        $this->checkPermission("thumbnails");

        $pipe = Asset\Image\Thumbnail\Config::getByName($this->getParam("name"));
        //$pipe->delete();

        $this->_helper->json($pipe);
    }


    public function thumbnailUpdateAction () {

        $this->checkPermission("thumbnails");

        $pipe = Asset\Image\Thumbnail\Config::getByName($this->getParam("name"));
        $settingsData = \Zend_Json::decode($this->getParam("settings"));
        $mediaData = \Zend_Json::decode($this->getParam("medias"));

        foreach ($settingsData as $key => $value) {
            $setter = "set" . ucfirst($key);
            if(method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }
        }

        $pipe->resetItems();

        foreach ($mediaData as $mediaName => $items) {
            foreach ($items as $item) {
                $type = $item["type"];
                unset($item["type"]);

                $pipe->addItem($type, $item, $mediaName);
            }
        }

        $pipe->save();

        $this->deleteThumbnailTmpFiles($pipe);

        $this->_helper->json(array("success" => true));
    }


    public function videoThumbnailAdapterCheckAction () {

        if(!\Pimcore\Video::isAvailable()) {
            echo '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                $this->view->translate("php_cli_binary_and_or_ffmpeg_binary_setting_is_missing") .
                '</span>';
        }

        exit;
    }


    public function videoThumbnailTreeAction () {

        $this->checkPermission("thumbnails");

        $dir = Asset\Video\Thumbnail\Config::getWorkingDir();

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

        $this->checkPermission("thumbnails");

        $alreadyExist = false;

        try {
            Asset\Video\Thumbnail\Config::getByName($this->getParam("name"));
            $alreadyExist = true;
        } catch (\Exception $e) {
            $alreadyExist = false;
        }

        if(!$alreadyExist) {
            $pipe = new Asset\Video\Thumbnail\Config();
            $pipe->setName($this->getParam("name"));
            $pipe->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $pipe->getName()));
    }

    public function videoThumbnailDeleteAction () {

        $this->checkPermission("thumbnails");

        $pipe = Asset\Video\Thumbnail\Config::getByName($this->getParam("name"));
        $pipe->delete();

        $this->_helper->json(array("success" => true));
    }


    public function videoThumbnailGetAction () {

        $this->checkPermission("thumbnails");

        $pipe = Asset\Video\Thumbnail\Config::getByName($this->getParam("name"));
        $this->_helper->json($pipe);
    }


    public function videoThumbnailUpdateAction () {

        $this->checkPermission("thumbnails");

        $pipe = Asset\Video\Thumbnail\Config::getByName($this->getParam("name"));
        $data = \Zend_Json::decode($this->getParam("configuration"));

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

        $this->checkPermission("robots.txt");

        $siteSuffix = "";
        if($this->getParam("site")) {
            $siteSuffix = "-" . $this->getParam("site");
        }

        $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY . "/robots" . $siteSuffix . ".txt";

        if($this->getParam("data") !== null) {
            // save data
            \Pimcore\File::put($robotsPath, $this->getParam("data"));

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

        $this->checkPermission("tag_snippet_management");

        $dir = Model\Tool\Tag\Config::getWorkingDir();

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

        $this->checkPermission("tag_snippet_management");

        try {
            Model\Tool\Tag\Config::getByName($this->getParam("name"));
            $alreadyExist = true;
        } catch (\Exception $e) {
            $alreadyExist = false;
        }

        if(!$alreadyExist) {
            $tag = new Model\Tool\Tag\Config();
            $tag->setName($this->getParam("name"));
            $tag->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $tag->getName()));
    }

    public function tagManagementDeleteAction () {

        $this->checkPermission("tag_snippet_management");

        $tag = Model\Tool\Tag\Config::getByName($this->getParam("name"));
        $tag->delete();

        $this->_helper->json(array("success" => true));
    }


    public function tagManagementGetAction () {

        $this->checkPermission("tag_snippet_management");

        $tag = Model\Tool\Tag\Config::getByName($this->getParam("name"));
        $this->_helper->json($tag);
    }


    public function tagManagementUpdateAction () {

        $this->checkPermission("tag_snippet_management");

        $tag = Model\Tool\Tag\Config::getByName($this->getParam("name"));
        $data = \Zend_Json::decode($this->getParam("configuration"));
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

        if($this->getParam("name") != $data["name"]) {
            $tag->setName($this->getParam("name")); // set the old name again, so that the old file get's deleted
            $tag->delete(); // delete the old config / file
            $tag->setName($data["name"]);
        }

        $tag->save();

        $this->_helper->json(array("success" => true));
    }

    public function websiteSettingsAction() {

        try {
            if ($this->getParam("data")) {

                $this->checkPermission("website_settings");

                $data = \Zend_Json::decode($this->getParam("data"));

                if(is_array($data)) {
                    foreach ($data as &$value) {
                        $value = trim($value);
                    }
                }

                if ($this->getParam("xaction") == "destroy") {
                    $setting = WebsiteSetting::getById($data);
                    $setting->delete();

                    $this->_helper->json(array("success" => true, "data" => array()));
                }
                else if ($this->getParam("xaction") == "update") {
                    // save routes
                    $setting = WebsiteSetting::getById($data["id"]);

                    switch ($setting->getType()) {
                        case "document":
                        case "asset":
                        case "object":
                            if (isset($data["data"])) {
                                $path = $data["data"];
                                $element = Element\Service::getElementByPath($setting->getType(), $path);
                                $data["data"] = $element ? $element->getId() : null;
                            }
                            break;
                    }

                    $setting->setValues($data);

                    $setting->save();

                    $data = $this->getWebsiteSettingForEditMode($setting);

                    $this->_helper->json(array("data" => $data, "success" => true));
                }
                else if ($this->getParam("xaction") == "create") {

                    unset($data["id"]);

                    // save route
                    $setting = new WebsiteSetting();
                    $setting->setValues($data);

                    $setting->save();

                    $this->_helper->json(array("data" => $setting, "success" => true));
                }
            }
            else {
                // get list of routes

                $list = new WebsiteSetting\Listing();

                $list->setLimit($this->getParam("limit"));
                $list->setOffset($this->getParam("start"));

                if($this->getParam("sort")) {
                    $list->setOrderKey($this->getParam("sort"));
                    $list->setOrder($this->getParam("dir"));
                } else {
                    $list->setOrderKey("name");
                    $list->setOrder("asc");
                }

                if($this->getParam("filter")) {
                    $list->setCondition("`name` LIKE " . $list->quote("%".$this->getParam("filter")."%"));
                }

                $totalCount = $list->getTotalCount();
                $list = $list->load();

                $settings = array();
                foreach ($list as $item) {
                    $resultItem = $this->getWebsiteSettingForEditMode($item);
                    $settings[] = $resultItem;
                }

                $this->_helper->json(array("data" => $settings, "success" => true, "total" => $totalCount));
            }
        } catch (\Exception $e) {
            throw $e;
            $this->_helper->json(false);
        }

        $this->_helper->json(false);
    }

    private function getWebsiteSettingForEditMode($item) {
        $resultItem = array(
            "id" => $item->getId(),
            "name" => $item->getName(),
            "type" => $item->getType(),
            "siteId" => $item->getSiteId(),
            "creationDate" => $item->getCreationDate(),
            "modificationDate" => $item->getModificationDate()
        );


        switch ($item->getType()) {
            case "document":
            case "asset":
            case "object":
                $element = Element\Service::getElementById($item->getType(), $item->getData());
                if ($element) {
                    $resultItem["data"] = $element->getFullPath();
                }
                break;
            default:
                $resultItem["data"] = $item->getData("data");
                break;
        }
        return $resultItem;
    }
    
    public function getAvailableAlgorithmsAction () {
        
    	$options = array();
		
    	$algorithms = hash_algos();
        foreach ($algorithms as $algorithm) {

            $options[] = array(
                "key" => $algorithm,
                "value" => $algorithm
            );
            
        }

        $result = array("data" => $options, "success" => true, "total" => count($options));

        $this->_helper->json($result);

    }

    /**
     * deleteViews
     * delete views for localized fields when languages are removed to
     * prevent mysql errors
     * @param $language
     * @param $dbName
     */
    protected function deleteViews ($language, $dbName) {

        $db = \Pimcore\Resource::get();
        $views = $db->fetchAll("SHOW FULL TABLES IN " . $dbName . " WHERE TABLE_TYPE LIKE 'VIEW'");

        foreach($views as $view) {
            if (preg_match("/^object_localized_[0-9]+_" . $language . "$/", $view["Tables_in_" . $dbName])){
                $sql = "DROP VIEW " . $view["Tables_in_" . $dbName];
                $db->query($sql);
            }
        }
    }
}
