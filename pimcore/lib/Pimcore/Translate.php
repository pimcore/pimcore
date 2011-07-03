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

class Pimcore_Translate extends Zend_Translate_Adapter {


    /**
     * Translation data
     * @var array
     */
    protected $_translate = array();
    

    public function __construct($locale) {

        if (!$locale instanceof Zend_Locale) {
            $locale = new Zend_Locale($locale);
        }

        $locale = (string) $locale;

        parent::__construct(array(
            "locale" => $locale,
            "content" => array("__pimcore_dummy" => "only_a_dummy")
        ));
    }

    protected function _loadTranslationData($data = null, $locale, array $options = array()) {
        
        $list = new Translation_Website_List();
        $list->load();

        foreach ($list->getTranslations() as $translation) {
            if($translation instanceof Translation_Abstract) {
                foreach ($translation->getTranslations() as $language => $text) {
                    $this->_translate[$language][$translation->getKey()] = Pimcore_Tool_Text::removeLineBreaks($text);
                }
            }
        }

        $availableLanguages = (array) Pimcore_Tool::getValidLanguages();
        foreach ($availableLanguages as $language) {
            if(!array_key_exists($language,$this->_translate) || empty($this->_translate[$language])) {
                $this->_translate[$language] = array("__pimcore_dummy" => "only_a_dummy");
            }
        }

        return $this->_translate;
    }

    public function toString() {
        // pseudo is needed by the interface but not by the application
        return "Array";
    }


    public function translate($messageId, $locale = null) {


        // the maximum length of message-id's is 255
        if(strlen($messageId) > 255) {
            throw new Exception("Pimcore_Translate: Message ID's longer than 255 characters are invalid!");
        }

        if ($locale === null) {
            $locale = $this->_options['locale'];
        }
        
        if(empty($this->_translate[$locale])) {
            $this->_loadTranslationData(null,$locale);
        }
        
        if (!empty($this->_translate[$locale][$messageId])) {
            // return original translation
            return $this->_translate[$locale][$messageId];
        }
        else {
            // check if there is a translation in a lower step
            $keyParts = explode(".", $messageId);

            if (count($keyParts) > 1) {
                krsort($keyParts);
                $keysLoop = array();

                foreach ($keyParts as $key) {
                    array_unshift($keysLoop, $key);
                    $tmpKey = implode(".", $keysLoop);
                    if (!empty($this->_translate[$locale][$tmpKey])) {
                        return $this->_translate[$locale][$tmpKey];
                    }
                }
            }

            $this->createEmptyTranslation($locale, $messageId);
            return $messageId;
        }


        $this->createEmptyTranslation($locale, $messageId);

        // no translation found, return original
        return $messageId;
    }

    private function createEmptyTranslation($locale, $messageId) {

        // don't create translation if it's just empty
        if(array_key_exists($messageId, $this->_translate[$locale])) {
            return;
        }

        // no translation found create key
        if (Pimcore_Tool::isValidLanguage($locale)) {
            try {
                $t = Translation_Website::getByKey($messageId);
            }
            catch (Exception $e) {
                $t = new Translation_Website();
                $t->setKey($messageId);
            }

            $t->addTranslation($locale, "");
            $t->save();
        }
    }
}
