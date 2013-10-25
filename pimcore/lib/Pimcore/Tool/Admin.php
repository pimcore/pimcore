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

class Pimcore_Tool_Admin {

    /**
     * finds the translation file for a given language
     *
     * @static
     * @param  string $language
     * @return string
     */
    public static function getLanguageFile($language){

        //first try website languages dir, as fallback the core dir
       $languageFile = PIMCORE_CONFIGURATION_DIRECTORY . "/texts/" . $language . ".csv";
        if(!is_file($languageFile)){
            $languageFile =  PIMCORE_PATH . "/config/texts/" . $language . ".csv";
        }
        return $languageFile;

    }

    /**
     * finds installed languages
     *
     * @static
     * @return array
     */
    public static function getLanguages () {

        $languages = array();
        $languageDirs = array(PIMCORE_PATH . "/config/texts/",PIMCORE_CONFIGURATION_DIRECTORY . "/texts/");
        foreach($languageDirs as $filesDir){
            if(is_dir($filesDir)){
                $files = scandir($filesDir);
                foreach ($files as $file) {
                    if (is_file($filesDir . $file)) {
                        $parts = explode(".", $file);
                        if ($parts[1] == "csv") {
                            if (Zend_Locale::isLocale($parts[0])) {
                                $languages[] = $parts[0];
                            }
                        }
                    }
                }
            }
        }
        return $languages;
    }

    /**
     * @static
     * @param  $scriptContent
     * @return mixed
     */
    public static function getMinimizedScriptPath ($scriptContent) {

        $scriptPath = PIMCORE_TEMPORARY_DIRECTORY."/minified_javascript_core_".md5($scriptContent).".js";

        if(!is_file($scriptPath)) {
            //$scriptContent = JSMin::minify($scriptContent); // temp. disabled until we have a better library - just combine for now
            Pimcore_File::put($scriptPath, $scriptContent);
        }

        return str_replace(PIMCORE_DOCUMENT_ROOT,"",$scriptPath);
    }

    public static function getMinimizedStylesheetPath ($stylesheetContent) {
        $stylesheetPath = PIMCORE_TEMPORARY_DIRECTORY."/minified_css_core_".md5($stylesheetContent).".css";

        if(!is_file($stylesheetPath)) {
            //$stylesheetContent = Minify_CSS::minify($stylesheetContent); // temp. disabled until we have a better library - just combine for now

            // put minified contents into one single file
            Pimcore_File::put($stylesheetPath, $stylesheetContent);
        }

        return str_replace(PIMCORE_DOCUMENT_ROOT,"",$stylesheetPath);
    }


    /**
     * determines CSV Dialect
     *
     * @static
     * @param  $file
     * @return Csv_Dialect
     */
    public static function determineCsvDialect ($file) {

        // minimum 10 lines, to be sure take more
        $sample = "";
        for ($i=0; $i<10; $i++) {
            $sample .= implode("", array_slice(file($file), 0, 11)); // grab 20 lines
        }

        try {
            $sniffer = new Csv_AutoDetect();
            $dialect = $sniffer->detect($sample);
        } catch (Exception $e) {
            // use default settings
            $dialect = new Csv_Dialect();
        }

        // validity check
        if(!in_array($dialect->delimiter, array(";",",","\t","|",":"))) {
            $dialect->delimiter = ";";
        }

        return $dialect;
    }


    /**
     * @static
     * @return string
     */
    public static function getMaintenanceModeFile () {
        return PIMCORE_CONFIGURATION_DIRECTORY . "/maintenance.xml";
    }

    /**
     * Activates the maintenance mode, this means that only
     *
     * @static
     * @param  $sessionId
     * @return void
     */
    public static function activateMaintenanceMode ($sessionId = null) {

        if(empty($sessionId)) {
            $sessionId = session_id();
        }
        
        if(empty($sessionId)) {
            throw new Exception("It's not possible to activate the maintenance mode without a session-id");
        }

        $config = new Zend_Config(array(
               "sessionId" => $sessionId
        ), true);

        $writer = new Zend_Config_Writer_Xml(array(
              "config" => $config,
              "filename" => self::getMaintenanceModeFile()
        ));
        $writer->write();
        chmod(self::getMaintenanceModeFile(), 0777); // so it can be removed also via FTP, ...
    }

    /**
     * @static
     * @return void
     */
    public static function deactivateMaintenanceMode () {
        unlink(self::getMaintenanceModeFile());
    }

    /**
     * @static
     * @return bool
     */
    public static function isInMaintenanceMode() {
        $file = Pimcore_Tool_Admin::getMaintenanceModeFile();

        if(is_file($file)) {
            $conf = new Zend_Config_Xml($file);
            if($conf->sessionId) {
                return true;
            } else {
                @unlink($file);
            }
        }

        return false;
    }

    /**
     * @static
     * @return User
     */
    public static function getCurrentUser () {

        if(Zend_Registry::isRegistered("pimcore_admin_user")) {
            $user = Zend_Registry::get("pimcore_admin_user");
            return $user;
        }

        return null;
    }
}
