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

abstract class Translation_Abstract extends Pimcore_Model_Abstract {

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
     * @param string $key
     * @return Translation
     */
    public static abstract function getByKey($id);

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
        $this->key = $key;
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
        $this->date = $date;
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
    public function clearDependedCache () {
        Pimcore_Model_Cache::clearTags(array("translator","translate"));
    }
}
