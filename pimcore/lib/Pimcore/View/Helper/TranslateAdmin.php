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

class Pimcore_View_Helper_TranslateAdmin extends Zend_View_Helper_Translate {


    public function translateAdmin($key = "") {
        if ($key) {
            $locale = $_REQUEST["systemLocale"];

            if(!$locale){
                if(Zend_Registry::isRegistered("Zend_Locale")) {
                    $locale = Zend_Registry::get("Zend_Locale");
                } else {
                    $locale = new Zend_Locale("en");
                }
            }

            if ($locale) {

                $cacheKey = "translator_admin";
                if (!$translate = Pimcore_Model_Cache::load($cacheKey)) {
                    $translate = new Pimcore_Translate_Admin($locale);
                    Pimcore_Model_Cache::save($translate, $cacheKey, array("translator","translator_admin","translate"), null, 804);
                }

                $this->setTranslator($translate);
                $this->setLocale($locale);

                return call_user_func_array(array($this, "translate"), func_get_args());
            }

        }

        return $key;
    }

}

