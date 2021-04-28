<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

use Pimcore\Cache;
use Pimcore\Cache\Runtime;
use Pimcore\Event\Model\TranslationEvent;
use Pimcore\Event\TranslationEvents;
use Pimcore\File;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\Translation\Dao getDao()
 */
final class Translation extends AbstractModel
{
    const DOMAIN_DEFAULT = 'messages';
    const DOMAIN_ADMIN = 'admin';

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string[]
     */
    protected $translations;

    /**
     * @var int
     */
    protected $creationDate;

    /**
     * @var int
     */
    protected $modificationDate;

    /**
     * @var string
     */
    protected $domain = self::DOMAIN_DEFAULT;

    /**
     * @var string
     */
    protected $type;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public static function IsAValidLanguage(string $domain, string $locale): bool
    {
        return in_array($locale, (array)static::getValidLanguages($domain));
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
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @internal
     *
     * @param string $domain
     *
     * @return array
     */
    public static function getValidLanguages(string $domain = self::DOMAIN_DEFAULT): array
    {
        if ($domain == self::DOMAIN_ADMIN) {
            return \Pimcore\Tool\Admin::getLanguages();
        }

        return Tool::getValidLanguages();
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

    /**
     * @internal
     */
    public static function clearDependentCache()
    {
        Cache::clearTags(['translator', 'translate']);
    }

    /**
     * @param string $id
     * @param string $domain
     * @param bool $create
     * @param bool $returnIdIfEmpty
     *
     * @return static|null
     *
     * @throws \Exception
     */
    public static function getByKey(string $id, $domain = self::DOMAIN_DEFAULT, $create = false, $returnIdIfEmpty = false)
    {
        $cacheKey = 'translation_' . $id;
        if (Runtime::isRegistered($cacheKey)) {
            return Runtime::get($cacheKey);
        }

        $translation = new static();
        $translation->setDomain($domain);
        $idOriginal = $id;
        $languages = static::getValidLanguages($domain);

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
        Runtime::set($cacheKey, $translation);

        return $translation;
    }

    /**
     * @param string $id
     * @param string $domain
     * @param bool $create - creates an empty translation entry if the key doesn't exists
     * @param bool $returnIdIfEmpty - returns $id if no translation is available
     * @param string|null $language
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public static function getByKeyLocalized(string $id, $domain = self::DOMAIN_DEFAULT, $create = false, $returnIdIfEmpty = false, $language = null)
    {
        $args = func_get_args();
        $domain = $args[1] ?? self::DOMAIN_DEFAULT;
        $create = $args[2] ?? false;
        $returnIdIfEmpty = $args[3] ?? false;
        $language = $args[4] ?? null;

        if ($domain == self::DOMAIN_ADMIN) {
            if ($user = Tool\Admin::getCurrentUser()) {
                $language = $user->getLanguage();
            } elseif ($user = Tool\Authentication::authenticateSession()) {
                $language = $user->getLanguage();
            }

            if (!$language) {
                $language = \Pimcore::getContainer()->get('pimcore.locale')->findLocale();
            }

            if (!in_array($language, Tool\Admin::getLanguages())) {
                $config = \Pimcore\Config::getSystemConfiguration('general');
                $language = $config['language'] ?? null;
            }
        }

        if (!$language) {
            $language = \Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();
            if (!$language) {
                return null;
            }
        }

        $translationItem = self::getByKey($id, $domain, $create, $returnIdIfEmpty);
        if ($translationItem instanceof self) {
            return $translationItem->getTranslation($language);
        }

        return null;
    }

    /**
     * @param string $domain
     *
     * @return bool
     */
    public static function isAValidDomain(string $domain): bool
    {
        $translation = new static();

        return $translation->getDao()->isAValidDomain($domain);
    }

    public function save()
    {
        \Pimcore::getEventDispatcher()->dispatch(new TranslationEvent($this), TranslationEvents::PRE_SAVE);

        if (!$this->getCreationDate()) {
            $this->setCreationDate(time());
        }

        if (!$this->getModificationDate()) {
            $this->setModificationDate(time());
        }

        $this->getDao()->save();

        \Pimcore::getEventDispatcher()->dispatch(new TranslationEvent($this), TranslationEvents::POST_SAVE);

        self::clearDependentCache();
    }

    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(new TranslationEvent($this), TranslationEvents::PRE_DELETE);

        $this->getDao()->delete();
        self::clearDependentCache();

        \Pimcore::getEventDispatcher()->dispatch(new TranslationEvent($this), TranslationEvents::POST_DELETE);
    }

    /**
     * Imports translations from a csv file
     * The CSV file has to have the same format as an Pimcore translation-export-file
     *
     * @internal
     *
     * @param string $file - path to the csv file
     * @param string $domain
     * @param bool $replaceExistingTranslations
     * @param array|null $languages
     * @param array|null $dialect
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public static function importTranslationsFromFile(string $file, $domain = self::DOMAIN_DEFAULT, $replaceExistingTranslations = true, $languages = null, $dialect = null)
    {
        $delta = [];

        if (is_readable($file)) {
            if (!$languages || empty($languages) || !is_array($languages)) {
                $languages = static::getValidLanguages($domain);
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
                        $t = static::getByKey($textKey, $domain, true);
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
                static::clearDependentCache();
            } else {
                throw new \Exception('less than 2 rows of data - nothing to import');
            }
        } else {
            throw new \Exception("$file is not readable");
        }

        return $delta;
    }
}
