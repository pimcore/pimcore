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

class Pimcore_Tool_Transliteration {
    
    public static function toASCII ($value) {
        
        $translitTable = self::getTransliterationTable();
        
        $search = array();
        $replace = array();
        
        foreach ($translitTable as $s => $r) {
            $search[] = $s;
            $replace[] = $r;
        }
        
        // manually mapping
        $value = str_replace($search, $replace, $value);
        
        // then use iconv
        $value = iconv("utf-8","ASCII//IGNORE//TRANSLIT",$value);
        
        return $value;
    }

    public static function getTransliterationTable () {

        $mapping = parse_ini_file(PIMCORE_PATH . "/config/translit-utf8-ascii.ini",true);
        $translitTable = $mapping["default"];

        try {
            $locale = Zend_Registry::get("Zend_Locale");
            if($mapping[$locale->toString()]) {
                $translitTable = array_merge($mapping["default"], $mapping[$locale->toString()]);
            }
        }
        catch (Exception $e) {
            // there is no locale use default
        }

        return $translitTable;
    }
}
