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

namespace Pimcore;

class Update {

    /**
     * @var string
     */
    public static $updateHost = "update.pimcore.org";

    /**
     * @var bool
     */
    public static $dryRun = false;

    /**
     * @var string
     */
    public static $tmpTable = "_tmp_update";

    /**
     * @return bool
     */
    public static function isWriteable () {
        
        if(self::$dryRun) {
            return true;
        }
        
        // check permissions
        $files = rscandir(PIMCORE_PATH . "/");

        foreach ($files as $file) {
            if (!is_writable($file)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function getAvailableUpdates() {

        $currentRev = Version::$revision;
                
        self::cleanup();
 
        if(PIMCORE_DEVMODE){
            $xmlRaw = Tool::getHttpData("http://" . self::$updateHost . "/v2/getUpdateInfo.php?devmode=1&revision=" . $currentRev);    
        } else {
            $xmlRaw = Tool::getHttpData("http://" . self::$updateHost . "/v2/getUpdateInfo.php?revision=" . $currentRev);
        }

        $xml = simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);

        $revisions = array();
        $releases = array();
        if($xml instanceof \SimpleXMLElement){
            if(isset($xml->revision)) {
                foreach ($xml->revision as $r) {

                    $date = new \Zend_Date($r->date);

                    if (strlen(strval($r->version)) > 0) {
                        $releases[] = array(
                            "id" => strval($r->id),
                            "date" => strval($r->date),
                            "version" => strval($r->version),
                            "text" => strval($r->id) . " - " . $date->get(\Zend_Date::DATETIME_MEDIUM)
                        );
                    }
                    else {
                        $revisions[] = array(
                            "id" => strval($r->id),
                            "date" => strval($r->date),
                            "text" => strval($r->id) . " - " . $date->get(\Zend_Date::DATETIME_MEDIUM)
                        );
                    }
                }
            }
        } else {
            throw new \Exception("Unable to retrieve response from update server. Please ensure that your server is allowed to connect to update.pimcore.org:80");
        }

        return array(
            "revisions" => $revisions,
            "releases" => $releases
        );
    }

    /**
     * @param $toRevision
     * @return array
     */
    public static function getJobs ($toRevision) {
        
        $currentRev = Version::$revision;
        
        $xmlRaw = Tool::getHttpData("http://" . self::$updateHost . "/v2/getDownloads.php?from=" . $currentRev . "&to=" . $toRevision);
        $xml = simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);
        
        $jobs = array();
        $updateScripts = array();
        $revisions = array();
        
        if(isset($xml->download)) {
            foreach ($xml->download as $download) {

                if($download->type == "script") {
                    $updateScripts[(string) $download->revision]["preupdate"] = array(
                        "type" => "preupdate",
                        "revision" => (string) $download->revision
                    );
                    $updateScripts[(string) $download->revision]["postupdate"] = array(
                        "type" => "postupdate",
                        "revision" => (string) $download->revision
                    );
                }
            }
        }
        
        
        if(isset($xml->download)) {
            foreach ($xml->download as $download) {
                $jobs["parallel"][] = array(
                    "type" => "download",
                    "revision" => (string) $download->revision,
                    "url" => (string) $download->url
                );
                
                $revisions[] = (int) $download->revision;
            }
        }
        
        $revisions = array_unique($revisions);
        
        foreach ($revisions as $revision) {
            if($updateScripts[$revision]["preupdate"]) {                   
                $jobs["procedural"][] = $updateScripts[$revision]["preupdate"];
            }
            
            $jobs["procedural"][] = array(
                "type" => "files",
                "revision" => $revision
            );
            
            
            if($updateScripts[$revision]["postupdate"]) {                   
                $jobs["procedural"][] = $updateScripts[$revision]["postupdate"];
            }
        }
        
        $jobs["procedural"][] = array(
            "type" => "languages"
        );
 
        $jobs["procedural"][] = array(
            "type" => "clearcache"
        );
 
        $jobs["procedural"][] = array(
            "type" => "cleanup"
        );
        
        return $jobs;
    }

    /**
     * @param $revision
     * @param $url
     * @throws \Zend_Db_Adapter_Exception
     */
    public static function downloadData ($revision, $url) {
        
        $db = Resource::get();
        
        $db->query("CREATE TABLE IF NOT EXISTS `" . self::$tmpTable . "` (
          `revision` int(11) NULL DEFAULT NULL,
          `path` varchar(255) NULL DEFAULT NULL,
          `action` varchar(50) NULL DEFAULT NULL
        );");
        
        $downloadDir = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/update/".$revision;
        if(!is_dir($downloadDir)) {
            File::mkdir($downloadDir);
        }
        
        $filesDir = $downloadDir . "/files";
        if(!is_dir($filesDir)) {
            File::mkdir($filesDir);
        }
        
        $scriptsDir = $downloadDir . "/scripts";
        if(!is_dir($scriptsDir)) {
            File::mkdir($scriptsDir);
        }
        
        
        $xml = Tool::getHttpData($url);
        if($xml) {
            $updateFiles = simplexml_load_string($xml, null, LIBXML_NOCDATA);
            
            foreach ($updateFiles->file as $file) {
                
                if($file->type == "file") {
                    if ($file->action == "update" || $file->action == "add") {
                        $newPath = str_replace("/","~~~",$file->path);
                        $newFile = $filesDir."/".$newPath;
                        File::put($newFile, base64_decode((string) $file->content));
                    }
                    
                    $db->insert(self::$tmpTable, array(
                        "revision" => $revision,
                        "path" => (string) $file->path,
                        "action" => (string)$file->action
                    ));
                } else if ($file->type == "script") {
                    $newScript = $scriptsDir. $file->path;
                    File::put($newScript, base64_decode((string) $file->content));
                }
            }
        }
    }

    /**
     * @param $revision
     */
    public static function installData ($revision) {
        
        $db = Resource::get();
        $files = $db->fetchAll("SELECT * FROM `" . self::$tmpTable . "` WHERE revision = ?", $revision);
        
        foreach ($files as $file) { 
            if ($file["action"] == "update" || $file["action"] == "add") {
                if (!is_dir(dirname(PIMCORE_DOCUMENT_ROOT . $file["path"]))) {
                    if(!self::$dryRun) {
                        File::mkdir(dirname(PIMCORE_DOCUMENT_ROOT . $file["path"]));
                    }
                }
                $srcFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/update/".$revision."/files/" . str_replace("/","~~~",$file["path"]);
                $destFile = PIMCORE_DOCUMENT_ROOT . $file["path"];
                
                if(!self::$dryRun) {
                    copy($srcFile, $destFile);
                }
            }
            else if ($file["action"] == "delete") {
                if(!self::$dryRun) {
                    unlink(PIMCORE_DOCUMENT_ROOT . $file["path"]);
        
                    // remove also directory if its empty
                    if (count(glob(dirname(PIMCORE_DOCUMENT_ROOT . $file["path"]) . "/*")) === 0) {
                        recursiveDelete(dirname(PIMCORE_DOCUMENT_ROOT . $file["path"]), true);
                    }
                }
            }
        }
    }

    /**
     * @param $revision
     * @param $type
     * @return array
     */
    public static function executeScript ($revision, $type) {
        
        $script = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/update/".$revision . "/scripts/" . $type . ".php";

        $maxExecutionTime = 900;
        @ini_set("max_execution_time", $maxExecutionTime);
        set_time_limit($maxExecutionTime);

        Model\Cache::disable(); // it's important to disable the cache here eg. db-schemas, ...

        if(is_file($script)) {
            ob_start();
            try {
                if(!self::$dryRun) {
                    include($script);
                }
            }
            catch (\Exception $e) {
                \Logger::error($e);
            }
            $outputMessage = ob_get_clean();
        }
 
        return array(
            "message" => $outputMessage,
            "success" => true
        );
    }

    /**
     *
     */
    public static function cleanup () {
        
        // remove database tmp table
        $db = Resource::get();
        $db->query("DROP TABLE IF EXISTS `" . self::$tmpTable . "`");
        
        //delete tmp data
        recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/update", true);
    }
    
 
 
 
 
 
 
 
 
 
    /**
     * download language files for all existing system languages for pimcore core and plugins
     * @static
     * @return bool
     */
    public static function downloadLanguages() {
        return self::downloadLanguage();
    }


    /**
     * Download language files for a specific language for pimcore core and all plugins or
     * download language files for all existing system languages for pimcore core and plugins if parameter is null
     * @static
     * @param  $language
     * @return bool
     */
    public static function downloadLanguage($lang = null) {

        $languages = Tool\Admin::getLanguages();
        if (!empty($lang)) {
            $languages = array($lang);
        } else {
            //omit core language
            $additonalLanguages = array();
            foreach($languages as $lang){
                if($lang != "en"){
                    $additonalLanguages[]=$lang;
                }
            }
            $languages=$additonalLanguages;
        }

        //directory for additional languages
        $langDir = PIMCORE_CONFIGURATION_DIRECTORY . "/texts";
        if (!is_dir($langDir)) {
            File::mkdir($langDir);
        }

        $success = is_dir($langDir);
        if ($success) {
            if(is_array($languages)) {
                foreach ($languages as $language) {
                    //TODO: remove hard coded
                    $src = "http://www.pimcore.org/?controller=translation&action=download&language=" . $language;
                    $data = Tool::getHttpData($src);
    
                    if (!empty($language) and !empty($data)) {
                        try {
                            $languageFile = $langDir . "/" . $language . ".csv";
                            $fh = fopen($languageFile, 'w');
                            fwrite($fh, $data);
                            fclose($fh);
    
                        } catch (\Exception $e) {
                            \Logger::error("could not download language file");
                            \Logger::error($e);
                            $success = false;
                        }
                    }
                }
            }
        } else {
            \Logger::warning("Pimcore_Update: Could not create language dir [  $langDir ]");
        }
        return $success;

    }


    public static function updateMaxmindDb () {

        $downloadUrl = "http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz";
        $geoDbFile = PIMCORE_CONFIGURATION_DIRECTORY . "/GeoLite2-City.mmdb";
        $geoDbFileGz = $geoDbFile . ".gz";

        $firstTuesdayOfMonth = strtotime(date("F") . " 2013 tuesday");
        $filemtime = 0;
        if(file_exists($geoDbFile)) {
            $filemtime = filemtime($geoDbFile);
        }

        // update if file is older than 30 days, or if it is the first tuesday of the month
        if($filemtime < (time()-30*86400) || (date("m/d/Y") == date("m/d/Y", $firstTuesdayOfMonth) && $filemtime < time()-86400)) {
            $data = Tool::getHttpData($downloadUrl);
            if(strlen($data) > 1000000) {
                File::put($geoDbFileGz, $data);

                @unlink($geoDbFile);

                $sfp = gzopen($geoDbFileGz, "rb");
                $fp = fopen($geoDbFile, "w");

                while ($string = gzread($sfp, 4096)) {
                    fwrite($fp, $string, strlen($string));
                }
                gzclose($sfp);
                fclose($fp);

                unlink($geoDbFileGz);

                \Logger::info("Updated MaxMind GeoIP2 Database in: " . $geoDbFile);
            } else {
                \Logger::err("Failed to update MaxMind GeoIP2, size is under about 1M");
            }
        } else {
            \Logger::debug("MayMind GeoIP2 Download skipped, everything up to date, last update: " . date("m/d/Y H:i", $filemtime));
        }
    }
}
