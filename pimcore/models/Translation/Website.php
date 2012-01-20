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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Translation_Website extends Translation_Abstract
{
    /**
     * @static
     * @param $id - translation key
     * @param bool $create - creates an empty translation entry if the key doesn't exists
     * @param bool $returnIdIfEmpty - returns $id if no translation is available
     * @return Translation_Website
     */
    public static function getByKey($id, $create = false, $returnIdIfEmpty = false)
    {
        $translation = new self();

        try {
            $translation->getResource()->getByKey($id);
        } catch (Exception $e) {
            if (!$create) {
                throw new Exception($e->getMessage());
            } else {
                $translation->setKey($id);
                $translation->setDate(time());

                $translations = array();
                foreach (Pimcore_Tool::getValidLanguages() as $lang) {
                    $translations[$lang] = "";
                }
                $translation->setTranslations($translations);
                $translation->save();
            }

        }

        if ($returnIdIfEmpty) {
            $translations = $translation->getTranslations();
            foreach ($translations as $key => $value) {
                $translations[$key] = $value ? : $id;
            }
            $translation->setTranslations($translations);
        }

        return $translation;
    }

    /**
     * Static Helper to get the translation of the current locale
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
        try {
            $language = Zend_Registry::get('Zend_Locale')->getLanguage();
        } catch (Exception $e) {
            throw new Exception("Couldn't determine current language.");
        }

        return self::getByKey($id, $create, $returnIdIfEmpty)->getTranslation($language);
    }
}