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
       $languageFile = PIMCORE_WEBSITE_PATH."/var/config/texts/" . $language . ".csv";
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
        $languageDirs = array(PIMCORE_PATH . "/config/texts/",PIMCORE_WEBSITE_PATH."/var/config/texts/");
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
            $scriptContent = JSMin::minify($scriptContent);
            file_put_contents($scriptPath, $scriptContent);
        }

        return str_replace(PIMCORE_DOCUMENT_ROOT,"",$scriptPath);
    }

    public static function getMinimizedStylesheetPath ($stylesheetContent) {
        $stylesheetPath = PIMCORE_TEMPORARY_DIRECTORY."/minified_css_core_".md5($stylesheetContent).".css";

        if(!is_file($stylesheetPath)) {
            $stylesheetContent = Minify_CSS::minify($stylesheetContent);

            // put minified contents into one single file
            file_put_contents($stylesheetPath, $stylesheetContent);
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
        for ($i=0; $i<10; $i++) {
            $sample .= implode("", array_slice(file($file), 0, 11)); // grab 20 lines
        }

        $sniffer = new Csv_AutoDetect();
        $dialect = $sniffer->detect($sample);

        return $dialect;
    }

    /**
     * Activates the maintenance mode, this means that only
     *
     * @static
     * @param  $sessionId
     * @return void
     */
    public static function maintenanceModeActivate ($sessionId) {

    }

    /*
     *
     */
    public static function maintenanceModeDeactivate () {

    }
}
