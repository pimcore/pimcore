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

abstract class Translation_Abstract extends Pimcore_Model_Abstract implements Translation_Interface {

    /**
     * @var string
     */
    public $key;

    /**
     * @var array
     */
    public $translations;

    /**
     * @var integer
     */
    public $date;

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = self::getValidTranslationKey($key);
    }

    /**
     * @return array
     */
    public function getTranslations() {
        return $this->translations;
    }

    /**
     * @param array $translations
     */
    public function setTranslations($translations) {
        $this->translations = $translations;
    }

    /**
     * @return integer
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @param integer $date
     */
    public function setDate($date) {
        $this->date = (int) $date;
    }

    /**
     * @param string $language
     * @param string $text
     */
    public function addTranslation($language, $text) {
        $this->translations[$language] = $text;
    }

    /**
     * @param  $language
     * @return array
     */
    public function getTranslation($language) {
        return $this->translations[$language];
    }

    /**
     * @return void
     */
    public static function clearDependedCache () {
        Pimcore_Model_Cache::clearTags(array("translator","translate"));
    }

    /**
     * @static
     * @param $key
     * @return string
     */
    protected static function getValidTranslationKey($key){
        return mb_strtolower($key);
    }

    /**
      * @static
      * @param $id - translation key
      * @param bool $create - creates an empty translation entry if the key doesn't exists
      * @param bool $returnIdIfEmpty - returns $id if no translation is available
      * @return Translation_Website
      */
     public static function getByKey($id, $create = false, $returnIdIfEmpty = false)
     {
         $translation = new static();

         try {
             $translation->getResource()->getByKey(self::getValidTranslationKey($id));
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
             $language = (string) Zend_Registry::get('Zend_Locale');
         } catch (Exception $e) {
             throw new Exception("Couldn't determine current language.");
         }

         return self::getByKey($id, $create, $returnIdIfEmpty)->getTranslation($language);
     }

    /**
     * Imports translations from a csv file
     * The CSV file has to have the same format as an Pimcore translation-export-file
     *
     * @static
     * @param $file - path to the csv file
     * @param bool $replaceExistingTranslations
     * @throws Exception
     */
    public static function importTranslationsFromFile($file, $replaceExistingTranslations = true, $languages = null){
        if(is_readable($file)){
            if(!$languages || empty($languages) || !is_array($languages)) {
                $languages = Pimcore_Tool::getValidLanguages();
            }

            //read import data
            $tmpData = file_get_contents($file);
            //convert to utf-8 if needed
            $encoding = Pimcore_Tool_Text::detectEncoding($tmpData);
            if ($encoding) {
                $tmpData = iconv($encoding, "UTF-8", $tmpData);
            }
            //store data for further usage
            $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_translations";
            file_put_contents($importFile, $tmpData);
            chmod($importFile, 0766);

            $importFileOriginal = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_translations_original";
            file_put_contents($importFileOriginal, $tmpData);
            chmod($importFileOriginal, 0766);

            // determine csv type
            $dialect = Pimcore_Tool_Admin::determineCsvDialect(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_translations_original");
            //read data
            if (($handle = fopen(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_translations", "r")) !== FALSE) {
                while (($rowData = fgetcsv($handle, 10000, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar)) !== false) {
                    $data[] = $rowData;
                }
                fclose($handle);
            }

            //process translations
            if (is_array($data) and count($data) > 1) {
                $keys = $data[0];
                $data = array_slice($data, 1);
                foreach ($data as $row) {

                    $keyValueArray = array();
                    for ($counter = 0; $counter < count($row); $counter++) {
                        $rd = str_replace("&quot;", '"', $row[$counter]);
                        $keyValueArray[$keys[$counter]] = $rd;
                    }

                    if ($keyValueArray["key"]) {
                        $t = static::getByKey($keyValueArray["key"],true);
                        foreach ($keyValueArray as $key => $value) {
                            if ($key != "key" && $key != "date" && in_array($key, $languages)) {
                                if($replaceExistingTranslations){
                                    $t->addTranslation($key, $value);
                                }else{
                                    if(!$t->getTranslation($key)){
                                        $t->addTranslation($key, $value);
                                    }
                                }
                            }
                        }
                        $t->save();
                    }
                }
            } else {
                throw new Exception("less than 2 rows of data - nothing to import");
            }
        }else{
            throw new Exception("$file is not readable");
        }
    }
}
