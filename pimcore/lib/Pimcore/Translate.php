<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Pimcore\Cache;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;

class Translate extends \Zend_Translate_Adapter
{

    /**
     * Translation_Website is default for backward compatibilty other options are: Translation_Admin
     * see: Pimcore_Translate_Admin & Pimcore_Translate_Website
     * @var string
     */
    protected static $backend = "\\Pimcore\\Model\\Translation\\Website";

    /**
     * Translation data
     * @var array
     */
    protected $_translate = [];

    /**
     * @var bool
     */
    protected $isCacheable = true;


    /**
     * @param $locale
     */
    public function __construct($locale)
    {
        if (!$locale instanceof \Zend_Locale) {
            $locale = new \Zend_Locale($locale);
        }

        $locale = (string) $locale;

        parent::__construct([
            "locale" => $locale,
            "content" => ["__pimcore_dummy" => "only_a_dummy"]
        ]);
    }

    /**
     * @param null $data
     * @param $locale
     * @param array $options
     * @return array
     */
    protected function _loadTranslationData($data, $locale, array $options = [])
    {
        $locale = (string) $locale;
        $tmpKeyParts = explode("\\", self::getBackend());
        $cacheKey = "Translate_" . array_pop($tmpKeyParts) . "_data_" . $locale;

        if (!$data = Cache::load($cacheKey)) {
            $data = ["__pimcore_dummy" => "only_a_dummy"];
            $listClass = self::getBackend() . "\\Listing";
            $list = new $listClass();

            if ($list->isCacheable()) {
                $list->setCondition("language = ?", [$locale]);
                $translations = $list->loadRaw();

                foreach ($translations as $translation) {
                    $data[mb_strtolower($translation["key"])] = Tool\Text::removeLineBreaks($translation["text"]);
                }

                Cache::save($data, $cacheKey, ["translator", "translator_website", "translate"], null, 999);
                $this->isCacheable = true;
            } else {
                $this->isCacheable = false;
            }
        }

        $this->_translate[$locale] = $data;

        return $this->_translate;
    }

    /**
     * @return string
     */
    public function toString()
    {
        // pseudo is needed by the interface but not by the application
        return "Array";
    }


    /**
     * @param string|\Zend_Locale $locale
     * @return \Zend_Translate_Adapter
     * @throws \Zend_Translate_Exception
     */
    public function setLocale($locale)
    {

        // load data before calling the parent
        $l = (string) $locale;
        if (empty($this->_translate[$l])) {
            $this->_loadTranslationData(null, $l);
        }

        return parent::setLocale($locale);
    }

    /**
     * @param array|string $messageId
     * @param null $locale
     * @return array|string
     * @throws \Exception
     */
    public function translate($messageId, $locale = null)
    {
        $messageId = trim($messageId);
        $messageIdOriginal = $messageId;
        $messageId = mb_strtolower($messageId);

        // the maximum length of message-id's is 190
        if (strlen($messageId) > 190) {
            throw new \Exception("Pimcore_Translate: Message ID's longer than 190 characters are invalid!");
        }

        if ($locale === null) {
            $locale = $this->_options['locale'];
        }

        // check if the given locale is available, if not try again just the language without the region
        if (!Tool::isValidLanguage($locale)) {
            $originalLocale = new \Zend_Locale((string) $locale);
            if (Tool::isValidLanguage($originalLocale->getLanguage())) {
                $locale = $originalLocale->getLanguage();
                $this->setLocale($locale);
            }
        }

        // list isn't cacheable, just get a single item
        if (!$this->isCacheable) {
            $backend = self::getBackend();
            $translation = $backend::getByKeyLocalized($messageIdOriginal, true, true, $locale);
            if ($translation == $messageIdOriginal) {
                foreach (Tool::getFallbackLanguagesFor($locale) as $fallbackLanguage) {
                    $translation = $backend::getByKeyLocalized($messageIdOriginal, true, true, $fallbackLanguage);
                    if ($translation != $messageIdOriginal) {
                        break;
                    }
                }
            }

            return $translation;
        }

        if (empty($this->_translate[$locale])) {
            $this->_loadTranslationData(null, $locale);
        }

        if (!empty($this->_translate[$locale][$messageId])) {
            // return original translation
            return $this->_translate[$locale][$messageId];
        } else {
            // check if there is a translation in a lower step
            $keyParts = explode(".", $messageId);

            if (count($keyParts) > 1) {
                krsort($keyParts);
                $keysLoop = [];

                foreach ($keyParts as $key) {
                    array_unshift($keysLoop, $key);
                    $tmpKey = implode(".", $keysLoop);
                    if (!empty($this->_translate[$locale][$tmpKey])) {
                        return $this->_translate[$locale][$tmpKey];
                    }
                }
            }

            // check if there is a translation in a lower step (without reverting the keys)
            $tmpKey = $messageId;
            if (strrpos($tmpKey, ':')) {
                while ($tmpKey = substr($tmpKey, 0, strrpos($tmpKey, ':'))) {
                    if (!empty($this->_translate[$locale][$tmpKey])) {
                        return $this->_translate[$locale][$tmpKey];
                    }
                }
            }
        }

        // do not create a new translation if it is only empty, but do not return empty values
        if (!array_key_exists($messageId, $this->_translate[$locale])) {
            $this->createEmptyTranslation($locale, $messageIdOriginal);
        } else {
            // look for a fallback translation
            foreach (Tool::getFallbackLanguagesFor($locale) as $fallbackLanguage) {

                // check if data for fallback language is loaded, if not force it
                if (empty($this->_translate[$fallbackLanguage])) {
                    $this->_loadTranslationData(null, $fallbackLanguage);
                }

                if (!empty($this->_translate[$fallbackLanguage][$messageId])) {
                    return $this->_translate[$fallbackLanguage][$messageId];
                }
            }
        }

        // no translation found, return original
        return $messageIdOriginal;
    }

    /**
     * @param $locale
     * @param $messageId
     * @return mixed
     */
    protected function createEmptyTranslation($locale, $messageId)
    {
        $messageIdOriginal = $messageId;
        $messageId = mb_strtolower($messageId);
        $messageId = trim($messageId);

        // don't create translation if it's just empty
        if (array_key_exists($messageId, $this->_translate[$locale])) {
            return;
        }

        $class = self::getBackend();

        // no translation found create key
        if (Tool::isValidLanguage($locale)) {
            try {
                $t = $class::getByKey($messageId);
                $t->addTranslation($locale, "");
            } catch (\Exception $e) {
                $t = new $class();
                $t->setKey($messageId);

                // add all available languages
                $availableLanguages = (array) Tool::getValidLanguages();
                foreach ($availableLanguages as $language) {
                    $t->addTranslation($language, "");
                }
            }

            $t->save();
        }

        // put it into the store, otherwise when there are more calls to the same key during one process
        // the key would be inserted/updated several times, what would be redundant
        $this->_translate[$locale][$messageId] = $messageIdOriginal;
    }

    /**
     * @param string $messageId
     * @param bool $original
     * @param null $locale
     * @return bool
     */
    public function isTranslated($messageId, $original = false, $locale = null)
    {
        $messageId = mb_strtolower($messageId);

        return parent::isTranslated($messageId, $original, $locale);
    }

    /**
     * @static
     * @param $backend
     */
    public static function setBackend($backend)
    {
        static::$backend = $backend;
    }

    /**
     * @static
     * @return string
     */
    public static function getBackend()
    {
        return static::$backend;
    }
}
