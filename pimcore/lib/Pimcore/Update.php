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

class Pimcore_Update {

    public static $updateHost = "update.pimcore.org";
    public static $dryRun = false;
    public static $tmpTable = "_tmp_update";
    
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
    
    public static function getAvailableUpdates() {

        $currentRev = Pimcore_Version::$revision;
                
        self::cleanup();
 
        if(PIMCORE_DEVMODE){
            $xmlRaw = Pimcore_Tool::getHttpData("http://" . self::$updateHost . "/v2/getUpdateInfo.php?devmode=1&revision=" . $currentRev);    
        } else {
            $xmlRaw = Pimcore_Tool::getHttpData("http://" . self::$updateHost . "/v2/getUpdateInfo.php?revision=" . $currentRev);
        }

        $xml = simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);

        $revisions = array();
        $releases = array();
        if(isset($xml->revision)){
            foreach ($xml->revision as $r) {

                $date = new Zend_Date($r->date);

                if (strlen(strval($r->version)) > 0) {
                    $releases[] = array(
                        "id" => strval($r->id),
                        "date" => strval($r->date),
                        "version" => strval($r->version),
                        "text" => strval($r->id) . " - " . $date->get(Zend_Date::DATETIME_MEDIUM)
                    );
                }
                else {
                    $revisions[] = array(
                        "id" => strval($r->id),
                        "date" => strval($r->date),
                        "text" => strval($r->id) . " - " . $date->get(Zend_Date::DATETIME_MEDIUM)
                    );
                }
            }
        }

        return array(
            "revisions" => $revisions,
            "releases" => $releases
        );
    }
    
    public static function getJobs ($toRevision) {
        
        $currentRev = Pimcore_Version::$revision;
        
        $xmlRaw = Pimcore_Tool::getHttpData("http://" . self::$updateHost . "/v2/getDownloads.php?from=" . $currentRev . "&to=" . $toRevision);
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
    
    public static function downloadData ($revision, $url) {
        
        $db = Pimcore_Resource::get();
        
        $db->query("CREATE TABLE IF NOT EXISTS `" . self::$tmpTable . "` (
          `revision` int(11) NULL DEFAULT NULL,
          `path` varchar(255) NULL DEFAULT NULL,
          `action` varchar(50) NULL DEFAULT NULL
        );");
        
        $downloadDir = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/update/".$revision;
        if(!is_dir($downloadDir)) {
            mkdir($downloadDir,0755,true);
        }
        
        $filesDir = $downloadDir . "/files";
        if(!is_dir($filesDir)) {
            mkdir($filesDir,0755,true);
        }
        
        $scriptsDir = $downloadDir . "/scripts";
        if(!is_dir($scriptsDir)) {
            mkdir($scriptsDir,0755,true);
        }
        
        
        $xml = Pimcore_Tool::getHttpData($url);
        if($xml) {
            $updateFiles = simplexml_load_string($xml, null, LIBXML_NOCDATA);
            
            foreach ($updateFiles->file as $file) {
                
                if($file->type == "file") {
                    if ($file->action == "update" || $file->action == "add") {
                        $newPath = str_replace("/","~~~",$file->path);
                        $newFile = $filesDir."/".$newPath;
                        file_put_contents($newFile, base64_decode((string) $file->content));
                        chmod($newFile, 0766);
                    }
                    
                    $db->insert(self::$tmpTable, array(
                        "revision" => $revision,
                        "path" => (string) $file->path,
                        "action" => (string)$file->action
                    ));
                } else if ($file->type == "script") {
                    $newScript = $scriptsDir. $file->path;
                    file_put_contents($newScript, base64_decode((string) $file->content));
                    chmod($newScript, 0766);
                }
            }
        }
    }
    
    public static function installData ($revision) {
        
        $db = Pimcore_Resource::get();
        $files = $db->fetchAll("SELECT * FROM `" . self::$tmpTable . "` WHERE revision = ?", $revision);
        
        foreach ($files as $file) { 
            if ($file["action"] == "update" || $file["action"] == "add") {
                if (!is_dir(dirname(PIMCORE_DOCUMENT_ROOT . $file["path"]))) {
                    if(!self::$dryRun) {
                        mkdir(dirname(PIMCORE_DOCUMENT_ROOT . $file["path"]), 0755, true);
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
    
    public static function executeScript ($revision, $type) {
        
        $script = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/update/".$revision . "/scripts/" . $type . ".php";

        $maxExecutionTime = 900;
        @ini_set("max_execution_time", $maxExecutionTime);
        set_time_limit($maxExecutionTime);

        if(is_file($script)) {
            ob_start();
            try {
                if(!self::$dryRun) {
                    include($script);
                }
            }
            catch (Exception $e) {
                Logger::error($e);
            }
            $outputMessage = ob_get_clean();
        }
 
        return array(
            "message" => $outputMessage,
            "success" => true
        );
    }
    
    public static function cleanup () {
        
        // remove database tmp table
        $db = Pimcore_Resource::get();
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
        return Pimcore_Update::downloadLanguage();
    }


    /**
     * Download language files for a specific language for pimcore core and all plugins or
     * download language files for all existing system languages for pimcore core and plugins if parameter is null
     * @static
     * @param  $language
     * @return bool
     */
    public static function downloadLanguage($lang = null) {

        $languages = Pimcore_Tool_Admin::getLanguages();
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
        $langDir = PIMCORE_WEBSITE_PATH . "/var/config/texts";
        if (!is_dir($langDir)) {
            mkdir($langDir, 0755, true);
        }

        $success = is_dir($langDir);
        if ($success) {
            if(is_array($languages)) {
                foreach ($languages as $language) {
                    //TODO: remove hard coded
                    $src = "http://www.pimcore.org/?controller=translation&action=download&language=" . $language;
                    $data = Pimcore_Tool::getHttpData($src);
    
                    if (!empty($language) and !empty($data)) {
                        try {
                            $languageFile = $langDir . "/" . $language . ".csv";
                            $fh = fopen($languageFile, 'w');
                            fwrite($fh, $data);
                            fclose($fh);
    
                        } catch (Exception $e) {
                            Logger::error("could not download language file");
                            Logger::error($e);
                            $success = false;
                        }
                    }
                }
            }
        } else {
            Logger::warning("Pimcore_Update: Could not create language dir [  $langDir ]");
        }
        return $success;

    }
}
