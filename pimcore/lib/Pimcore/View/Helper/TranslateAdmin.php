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

class Pimcore_View_Helper_TranslateAdmin extends Zend_View_Helper_Abstract {

    public static $_controller;

    public static function getController() {
        if (!self::$_controller) {
            self::$_controller = new Pimcore_View_Helper_TranslateAdmin_Controller();
        }

        return self::$_controller;
    }

    public function translateAdmin($key = "") {
        if(empty($key)) {
            return self::getController();
        }

        return self::getController()->translate($key);
    }

}


class Pimcore_View_Helper_TranslateAdmin_Controller {


    public function translate($key) {

        $translated = $key;

        if ($key) {
            $locale = $_REQUEST["systemLocale"];
            if ($locale) {
                try {
                    $translation = Translation_Admin::getByKey($key);
                } catch (Exception $e) {

                }

                if ($translation instanceof Translation_Admin) {
                    if($translation->getTranslation($locale)){
                        $translated =  $translation->getTranslation($locale);
                    } else {
                        if(PIMCORE_DEBUG){
                            $translated = "+".$key."+";
                        } 
                    }
                } else {
                    $t = new Translation_Admin();
                    $availableLanguages = Pimcore_Tool_Admin::getLanguages();
                    $t->setKey($key);
                    $t->setDate(time());

                    foreach ($availableLanguages as $lang) {
                        $t->addTranslation($lang, "");
                    }
                    try {
                        $t->save();
                    } catch (Exception $e) {
                        Logger::debug(get_class($this), ": could not save new translation for key [ $key ]");
                    }

                }
            }

        }

        return $translated;

    }


}
