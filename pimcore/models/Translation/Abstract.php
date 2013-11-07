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
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;

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
        return $this;
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
        return $this;
    }

    /**
     * @return integer
     * @deprecated use getCreationDate or getModificationDate instead
     */
    public function getDate() {
        return $this->getModificationDate();
    }

    /**
     * @param integer $date
     * @deprecated use setCreationDate or setModificationDate instead
     */
    public function setDate($date) {
        $this->setModificationDate($date);
        return $this;
    }

    public function getCreationDate(){
        return $this->creationDate;
    }

    /**
     * @param integer $date
     */
    public function setCreationDate($date){
        $this->creationDate = (int) $date;
        return $this;
    }

    public function getModificationDate(){
        return $this->modificationDate;
    }

    /**
     * @param integer $date
     */
    public function setModificationDate($date){
        $this->modificationDate = (int) $date;
        return $this;
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
    public static function clearDependentCache () {
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
         $cacheKey = "translation_" . $id;
         if(Zend_Registry::isRegistered($cacheKey)) {
            return Zend_Registry::get($cacheKey);
         }

         $translation = new static();

         $idOriginal = $id;
         $id = mb_strtolower($id);

         try {
             $translation->getResource()->getByKey(self::getValidTranslationKey($id));
         } catch (Exception $e) {
             if (!$create) {
                 throw new Exception($e->getMessage());
             } else {
                 $translation->setKey($id);
                 $translation->setCreationDate(time());
                 $translation->setModificationDate(time());

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
                 $translations[$key] = $value ? : $idOriginal;
             }
             $translation->setTranslations($translations);
         }

         // add to key cache
         Zend_Registry::set($cacheKey, $translation);

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
     public static function getByKeyLocalized($id, $create = false, $returnIdIfEmpty = false, $language = null)
     {
         if(!$language) {
             try {
                 $language = (string) Zend_Registry::get('Zend_Locale');
             } catch (Exception $e) {
                 throw new Exception("Couldn't determine current language.");
             }
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
            $tmpData = Pimcore_Tool_Text::convertToUTF8($tmpData);

            //store data for further usage
            $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_translations";
            Pimcore_File::put($importFile, $tmpData);

            $importFileOriginal = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_translations_original";
            Pimcore_File::put($importFileOriginal, $tmpData);

            // determine csv type
            $dialect = Pimcore_Tool_Admin::determineCsvDialect(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_translations_original");
            //read data
            if (($handle = fopen(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_translations", "r")) !== FALSE) {
                while (($rowData = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar)) !== false) {
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
                            if (in_array($key, $languages)) {
                                if($replaceExistingTranslations){
                                    $t->addTranslation($key, $value);
                                }else{
                                    if(!$t->getTranslation($key)){
                                        $t->addTranslation($key, $value);
                                    }
                                }
                            }
                        }
                        if($keyValueArray['creationDate']){
                            $t->setCreationDate($keyValueArray['creationDate']);
                        }
                        $t->setModificationDate(time()); //ignore modificationDate from file
                        $t->save();
                    }
                }
                Translation_Abstract::clearDependentCache();
            } else {
                throw new Exception("less than 2 rows of data - nothing to import");
            }
        }else{
            throw new Exception("$file is not readable");
        }
    }


    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($data)
    {
        foreach($data as $key => $value){
            $setter = 'set' . ucfirst($key);
            $this->$setter($value);
        }
    }

    public function getForWebserviceExport(){
        $data = get_object_vars($this);
        unset($data['resource']);
        return $data;
    }
}
