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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\Newsletter;

use Pimcore\Model;

class Config {

    /**
     * @var string
     */
    public $name = "";

    /**
     * @var string
     */
    public $description = "";

    /**
     * @var int
     */
    public $document;

    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $objectFilterSQL;

    /**
     * @var string
     */
    public $personas;

    /**
     * @var string
     */
    public $testEmailAddress;

    /**
     * @var bool
     */
    public $googleAnalytics = true;

    /**
     * @param $name
     * @return Config
     * @throws \Exception
     */
    public static function getByName ($name) {
        $letter = new self();
        $letter->setName($name);
        if(!$letter->load()) {
            throw new \Exception("newsletter definition : " . $name . " does not exist");
        }

        return $letter;
    }

    /**
     * @static
     * @return string
     */
    public static function getWorkingDir () {
        $dir = PIMCORE_CONFIGURATION_DIRECTORY . "/newsletter";
        if(!is_dir($dir)) {
            \Pimcore\File::mkdir($dir);
        }

        return $dir;
    }

    /**
     * @return string
     */
    public function getPidFile() {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . "/newsletter__" . $this->getName() . ".pid";
    }

    /**
     * @return void
     */
    public function save () {

        $arrayConfig = object2array($this);

        $config = new \Zend_Config($arrayConfig);
        $writer = new \Zend_Config_Writer_Xml(array(
            "config" => $config,
            "filename" => $this->getConfigFile()
        ));
        $writer->write();

        return true;
    }

    /**
     * @return void
     */
    public function load () {

        $configXml = new \Zend_Config_Xml($this->getConfigFile());
        $configArray = $configXml->toArray();

        foreach ($configArray as $key => $value) {
            $setter = "set" . ucfirst($key);
            if(method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }

        return true;
    }

    /**
     * @return void
     */
    public function delete() {
        if(is_file($this->getConfigFile())) {
            unlink($this->getConfigFile());
        }
    }

    /**
     * @return string
     */
    protected function getConfigFile () {
        return self::getWorkingDir() . "/" . $this->getName() . ".xml";
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return int
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param $googleAnalytics
     * @return $this
     */
    public function setGoogleAnalytics($googleAnalytics)
    {
        $this->googleAnalytics = (bool) $googleAnalytics;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getGoogleAnalytics()
    {
        return $this->googleAnalytics;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $objectFilterSQL
     */
    public function setObjectFilterSQL($objectFilterSQL)
    {
        $this->objectFilterSQL = $objectFilterSQL;
    }

    /**
     * @return string
     */
    public function getObjectFilterSQL()
    {
        return $this->objectFilterSQL;
    }

    /**
     * @param string $testEmailAddress
     */
    public function setTestEmailAddress($testEmailAddress)
    {
        $this->testEmailAddress = $testEmailAddress;
    }

    /**
     * @return string
     */
    public function getTestEmailAddress()
    {
        return $this->testEmailAddress;
    }

    /**
     * @param string $personas
     */
    public function setPersonas($personas)
    {
        $this->personas = $personas;
    }

    /**
     * @return string
     */
    public function getPersonas()
    {
        return $this->personas;
    }
}