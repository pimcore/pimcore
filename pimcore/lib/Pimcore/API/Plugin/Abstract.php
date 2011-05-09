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

class Pimcore_API_Plugin_Abstract extends Pimcore_API_Abstract {

    private $jsPaths;
    private $cssPaths;

    /**
     *
     * @param string $language
     * @return string $languageFile for the specified language relative to plugin directory
     */
    public static function getTranslationFile($language) {
        return null;
    }

    protected static function getDb() {
        $db = Pimcore_Resource::getConnection();
        return $db;
    }

    function getJsPaths() {
        return $this->jsPaths;
    }


    function getCssPaths() {
        return $this->cssPaths;
    }

    function __construct($jsPaths = null, $cssPaths = null) {
        if (!empty($jsPaths))
            $this->jsPaths = $jsPaths;
        if (!empty($cssPaths))
            $this->cssPaths = $cssPaths;

    }

    public static function getJsClassName() {
        return "";
    }

    public static function needsReloadAfterInstall() {
        return false;
    }

    /**
     * @return boolean $readyForInstall
     */
    public static function readyForInstall() {
        return true;
    }

    /**
     * this method allows the plugin to show status messages in pimcore plugin settings 
     *
     * @static
     * @return string
     */
    public static function getPluginState(){
        return "";
    }

    /**
     * absolute path to the folder holding plugin translation files
     * @static
     * @abstract
     * @return string
     */
    public static function getTranslationFileDirectory(){
        return null;
    }


}