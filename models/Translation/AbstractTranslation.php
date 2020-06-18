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
 * @category   Pimcore
 * @package    Translation
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Translation;

use Pimcore\Event\Model\TranslationEvent;
use Pimcore\Event\TranslationEvents;
use Pimcore\File;
use Pimcore\Model;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\Translation\AbstractTranslation\Dao getDao()
 */
abstract class AbstractTranslation extends Model\AbstractModel implements TranslationInterface
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var string[]
     */
    public $translations;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @inheritDoc
     */
    public static function isValidLanguage($locale): bool
    {
        return in_array($locale, (array)static::getLanguages());
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param string[] $translations
     *
     * @return $this
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;

        return $this;
    }

    /**
     * @param int $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->setModificationDate($date);

        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $date
     *
     * @return $this
     */
    public function setCreationDate($date)
    {
        $this->creationDate = (int) $date;

        return $this;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $date
     *
     * @return $this
     */
    public function setModificationDate($date)
    {
        $this->modificationDate = (int) $date;

        return $this;
    }

    /**
     * @param string $language
     * @param string $text
     */
    public function addTranslation($language, $text)
    {
        $this->translations[$language] = $text;
    }

    /**
     * @param string $language
     *
     * @return string
     */
    public function getTranslation($language)
    {
        return $this->translations[$language];
    }

    /**
     * @param string $language
     *
     * @return bool
     */
    public function hasTranslation($language)
    {
        return isset($this->translations[$language]);
    }

    public static function clearDependentCache()
    {
        \Pimcore\Cache::clearTags(['translator', 'translate']);
    }

    /**
     * @param string $id
     * @param bool $create
     * @param bool $returnIdIfEmpty
     *
     * @return static|null
     */
    public static function getByKey($id, $create = false, $returnIdIfEmpty = false)
    {
        $cacheKey = 'translation_' . $id;
        if (\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            return \Pimcore\Cache\Runtime::get($cacheKey);
        }

        $translation = new static();
        $idOriginal = $id;
        $languages = static::getLanguages();

        try {
            $translation->getDao()->getByKey($id);
        } catch (\Exception $e) {
            if (!$create) {
                return null;
            } else {
                $translation->setKey($id);
                $translation->setCreationDate(time());
                $translation->setModificationDate(time());

                $translations = [];
                foreach ($languages as $lang) {
                    $translations[$lang] = '';
                }
                $translation->setTranslations($translations);
                $translation->save();
            }
        }

        if ($returnIdIfEmpty) {
            $translations = $translation->getTranslations();
            foreach ($languages as $language) {
                if (!array_key_exists($language, $translations) || empty($translations[$language])) {
                    $translations[$language] = $idOriginal;
                }
            }
            $translation->setTranslations($translations);
        }

        // add to key cache
        \Pimcore\Cache\Runtime::set($cacheKey, $translation);

        return $translation;
    }

    /**
     * Static Helper to get the translation of the current locale
     *
     * @static
     *
     * @param string $id - translation key
     * @param bool $create - creates an empty translation entry if the key doesn't exists
     * @param bool $returnIdIfEmpty - returns $id if no translation is available
     * @param string $language
     *
     * @return string|null
     */
    public static function getByKeyLocalized($id, $create = false, $returnIdIfEmpty = false, $language = null)
    {
        if (!$language) {
            $language = \Pimcore::getContainer()->get('pimcore.locale')->findLocale();
            if (!$language) {
                return null;
            }
        }

        $translationItem = self::getByKey($id, $create, $returnIdIfEmpty);
        if ($translationItem instanceof self) {
            return $translationItem->getTranslation($language);
        }

        return null;
    }

    public function save()
    {
        \Pimcore::getEventDispatcher()->dispatch(TranslationEvents::PRE_SAVE, new TranslationEvent($this));

        if (!$this->getCreationDate()) {
            $this->setCreationDate(time());
        }

        if (!$this->getModificationDate()) {
            $this->setModificationDate(time());
        }

        $this->getDao()->save();

        \Pimcore::getEventDispatcher()->dispatch(TranslationEvents::POST_SAVE, new TranslationEvent($this));

        self::clearDependentCache();
    }

    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(TranslationEvents::PRE_DELETE, new TranslationEvent($this));

        $this->getDao()->delete();
        self::clearDependentCache();

        \Pimcore::getEventDispatcher()->dispatch(TranslationEvents::POST_DELETE, new TranslationEvent($this));
    }

    /**
     * Imports translations from a csv file
     * The CSV file has to have the same format as an Pimcore translation-export-file
     *
     * @static
     *
     * @param string $file - path to the csv file
     * @param bool $replaceExistingTranslations
     * @param array $languages
     * @param array $dialect
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public static function importTranslationsFromFile($file, $replaceExistingTranslations = true, $languages = null, $dialect = null)
    {
        $delta = [];

        if (is_readable($file)) {
            if (!$languages || empty($languages) || !is_array($languages)) {
                $languages = static::getLanguages();
            }

            //read import data
            $tmpData = file_get_contents($file);

            //replace magic excel bytes
            $tmpData = str_replace("\xEF\xBB\xBF", '', $tmpData);

            //convert to utf-8 if needed
            $tmpData = Tool\Text::convertToUTF8($tmpData);

            //store data for further usage
            $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/import_translations';
            File::put($importFile, $tmpData);

            $importFileOriginal = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/import_translations_original';
            File::put($importFileOriginal, $tmpData);

            // determine csv type if not set
            if (empty($dialect)) {
                $dialect = Tool\Admin::determineCsvDialect(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/import_translations_original');
            }

            //read data
            $data = [];
            if (($handle = fopen(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/import_translations', 'r')) !== false) {
                while (($rowData = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar)) !== false) {
                    $data[] = $rowData;
                }
                fclose($handle);
            }

            //process translations
            if (is_array($data) and count($data) > 1) {
                $keys = $data[0];
                // remove wrong quotes in some export/import constellations
                $keys = array_map(function ($value) {
                    return trim($value, '﻿""');
                }, $keys);
                $data = array_slice($data, 1);
                foreach ($data as $row) {
                    $keyValueArray = [];
                    for ($counter = 0; $counter < count($row); $counter++) {
                        $rd = str_replace('&quot;', '"', $row[$counter]);
                        $keyValueArray[$keys[$counter]] = $rd;
                    }

                    $textKey = $keyValueArray['key'];
                    if ($textKey) {
                        $t = static::getByKey($textKey, true);
                        $dirty = false;
                        foreach ($keyValueArray as $key => $value) {
                            if (in_array($key, $languages)) {
                                $currentTranslation = $t->getTranslation($key);
                                if ($replaceExistingTranslations) {
                                    $t->addTranslation($key, $value);
                                    if ($currentTranslation != $value) {
                                        $dirty = true;
                                    }
                                } else {
                                    if (!$t->getTranslation($key)) {
                                        $t->addTranslation($key, $value);
                                        if ($currentTranslation != $value) {
                                            $dirty = true;
                                        }
                                    } elseif ($t->getTranslation($key) != $value && $value) {
                                        $delta[] =
                                            [
                                                'lg' => $key,
                                                'key' => $textKey,
                                                'text' => $t->getTranslation($key),
                                                'csv' => $value,
                                            ];
                                    }
                                }
                            }
                        }

                        if ($dirty) {
                            if (array_key_exists('creationDate', $keyValueArray) && $keyValueArray['creationDate']) {
                                $t->setCreationDate($keyValueArray['creationDate']);
                            }
                            $t->setModificationDate(time()); //ignore modificationDate from file
                            $t->save();
                        }
                    }
                }
                Model\Translation\AbstractTranslation::clearDependentCache();
            } else {
                throw new \Exception('less than 2 rows of data - nothing to import');
            }
        } else {
            throw new \Exception("$file is not readable");
        }

        return $delta;
    }

    /**
     * @deprecated
     *
     * @param array $data
     */
    public function getFromWebserviceImport($data)
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            $this->$setter($value);
        }
    }

    /**
     * @deprecated
     *
     * @return array
     */
    public function getForWebserviceExport()
    {
        $data = get_object_vars($this);
        unset($data['dao']);

        return $data;
    }
}
