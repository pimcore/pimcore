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
 * @category   Pimcore
 * @package    Translation
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Translation_Admin extends Translation_Abstract {

    /**
     * Static Helper to get the translation of the current logged in user
     *
     * @static
     * @param $id - translation key
     * @param bool $create - creates an empty translation entry if the key doesn't exists
     * @param bool $returnIdIfEmpty - returns $id if no translation is available
     * @return string
     * @throws Exception
     */
    public static function getByKeyLocalized($id, $create = false, $returnIdIfEmpty = false)
    {
        if($user = Pimcore_Tool_Admin::getCurrentUser()) {
            $language = $user->getLanguage();
        } else if ($user = Pimcore_Tool_Authentication::authenticateSession()) {
            $language = $user->getLanguage();
        } else if(Zend_Registry::isRegistered("Zend_Locale")) {
            $language = (string) Zend_Registry::get("Zend_Locale");
        }

        if(!in_array($language,Pimcore_Tool_Admin::getLanguages())){
            $config = Pimcore_Config::getSystemConfig();
            $language = $config->general->language;
        }


        return self::getByKey($id, $create, $returnIdIfEmpty)->getTranslation($language);
    }
}
