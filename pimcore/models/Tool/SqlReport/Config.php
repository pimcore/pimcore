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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Tool_SqlReport_Config {

    /**
     * @var string
     */
    public $name = "";

    /**
     * @var string
     */
    public $sql = "";

    /**
     * @var array
     */
    public $columnConfiguration = array();

    /**
     * @var string
     */
    public $niceName = "";

    /**
     * @var string
     */
    public $group = "";

    /**
     * @var string
     */
    public $groupIconClass = "";

    /**
     * @var string
     */
    public $iconClass = "";

    /**
     * @var bool
     */
    public $menuShortcut;

    /**
     * @param $name
     * @return Tool_SqlReport_Config
     * @throws Exception
     */
    public static function getByName ($name) {
        $code = new self();
        $code->setName($name);
        if(!$code->load()) {
            throw new Exception("sql report definition : " . $name . " does not exist");
        }

        return $code;
    }

    /**
     * @static
     * @return string
     */
    public static function getWorkingDir () {
        $dir = PIMCORE_CONFIGURATION_DIRECTORY . "/sqlreport";
        if(!is_dir($dir)) {
            mkdir($dir);
        }

        return $dir;
    }


    /**
     * @return void
     */
    public function save () {

        $arrayConfig = object2array($this);
        $items = $arrayConfig["columnConfiguration"];
        $arrayConfig["columnConfiguration"] = array("columnConfiguration" => $items);

        $config = new Zend_Config($arrayConfig);
        $writer = new Zend_Config_Writer_Xml(array(
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

        $configXml = new Zend_Config_Xml($this->getConfigFile());
        $configArray = $configXml->toArray();

        if(array_key_exists("columnConfiguration",$configArray) && is_array($configArray["columnConfiguration"]["columnConfiguration"])) {
            if(array_key_exists("method",$configArray["columnConfiguration"]["columnConfiguration"])) {
                $configArray["columnConfiguration"] = array($configArray["columnConfiguration"]["columnConfiguration"]);
            } else {
                $configArray["columnConfiguration"] = $configArray["columnConfiguration"]["columnConfiguration"];
            }
        } else {
            $configArray["columnConfiguration"] = array("columnConfiguration" => array());
        }

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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $sql
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param array $columnConfiguration
     */
    public function setColumnConfiguration($columnConfiguration)
    {
        $this->columnConfiguration = $columnConfiguration;
    }

    /**
     * @return array
     */
    public function getColumnConfiguration()
    {
        return $this->columnConfiguration;
    }

    /**
     * @param string $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $groupIconClass
     */
    public function setGroupIconClass($groupIconClass)
    {
        $this->groupIconClass = $groupIconClass;
    }

    /**
     * @return string
     */
    public function getGroupIconClass()
    {
        return $this->groupIconClass;
    }

    /**
     * @param string $iconClass
     */
    public function setIconClass($iconClass)
    {
        $this->iconClass = $iconClass;
    }

    /**
     * @return string
     */
    public function getIconClass()
    {
        return $this->iconClass;
    }

    /**
     * @param string $niceName
     */
    public function setNiceName($niceName)
    {
        $this->niceName = $niceName;
    }

    /**
     * @return string
     */
    public function getNiceName()
    {
        return $this->niceName;
    }

    /**
     * @param boolean $menuShortcut
     */
    public function setMenuShortcut($menuShortcut)
    {
        $this->menuShortcut = (bool) $menuShortcut;
    }

    /**
     * @return boolean
     */
    public function getMenuShortcut()
    {
        return $this->menuShortcut;
    }


}