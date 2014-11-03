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

namespace Pimcore\Model\Tool\CustomReport;

use Pimcore\Model;

class Config {

    /**
     * @var string
     */
    public $name = "";

    /**
     * @var string
     */
    public $sql = "";

    /**
     * @var string[]
     */
    public $dataSourceConfig = array();

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
     * @var string
     */
    public $chartType;

    /**
     * @var string
     */
    public $pieColumn;

    /**
     * @var string
     */
    public $pieLabelColumn;

    /**
     * @var string
     */
    public $xAxis;

    /**
     * @var string|array
     */
    public $yAxis;

    /**
     * @param $name
     * @return Model\Tool\CustomReport\Config
     * @throws \Exception
     */
    public static function getByName ($name) {
        $code = new self();
        $code->setName($name);
        if(!$code->load()) {
            throw new \Exception("sql report definition : " . $name . " does not exist");
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
            \Pimcore\File::mkdir($dir);
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

        if($arrayConfig["dataSourceConfig"]) {
            $configArray = array();
            foreach($arrayConfig["dataSourceConfig"] as $config) {
                $configArray[] = json_encode($config);
            }
            $arrayConfig["dataSourceConfig"] = array("dataSourceConfig" => $configArray);
        } else {
            $arrayConfig["dataSourceConfig"] = array("dataSourceConfig" => array());
        }

        $items = $arrayConfig["yAxis"];
        $arrayConfig["yAxis"] = array("yAxis" => $items);

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

        if(array_key_exists("columnConfiguration",$configArray) && is_array($configArray["columnConfiguration"]["columnConfiguration"])) {
            if(array_key_exists("method",$configArray["columnConfiguration"]["columnConfiguration"])) {
                $configArray["columnConfiguration"] = array($configArray["columnConfiguration"]["columnConfiguration"]);
            } else {
                $configArray["columnConfiguration"] = $configArray["columnConfiguration"]["columnConfiguration"];
            }
        } else {
            $configArray["columnConfiguration"] = array("columnConfiguration" => array());
        }

        if(array_key_exists("dataSourceConfig",$configArray) && is_array($configArray["dataSourceConfig"])) {
            $dataSourceConfig = array();
            foreach($configArray["dataSourceConfig"] as $c) {
                if($c) {
                    $dataSourceConfig[] = json_decode($c);
                }
            }

            $configArray["dataSourceConfig"] = $dataSourceConfig;
        } else {
            $configArray["dataSourceConfig"] = array();
        }

        if(array_key_exists("yAxis",$configArray) && is_array($configArray["yAxis"])) {
            if(!is_array($configArray["yAxis"]["yAxis"])) {
                $configArray["yAxis"] = array($configArray["yAxis"]["yAxis"]);
            } else {
                $configArray["yAxis"] = $configArray["yAxis"]["yAxis"];
            }
        }

        // to preserve compatibility to older sql reports
        if($configArray["sql"] && empty($configArray["dataSourceConfig"])) {
            $legacy = new \stdClass();
            $legacy->type = "sql";
            $legacy->sql = $configArray["sql"];
            $configArray["dataSourceConfig"][] = $legacy;
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
     * @return array
     */
    public static function getReportsList () {
        $dir = Model\Tool\CustomReport\Config::getWorkingDir();

        $reports = array();
        $files = scandir($dir);
        foreach ($files as $file) {
            if(strpos($file, ".xml")) {
                $name = str_replace(".xml", "", $file);
                $reports[] = array(
                    "id" => $name,
                    "text" => $name
                );
            }
        }

        return $reports;

    }

    /**
     * @param $configuration
     * @param null $fullConfig
     * @return mixed
     */
    public static function getAdapter($configuration, $fullConfig = null) {

        $type = $configuration->type ? ucfirst($configuration->type) : 'Sql';
        $adapter = "\\Pimcore\\Model\\Tool\\CustomReport\\Adapter\\{$type}";
        return new $adapter($configuration, $fullConfig);
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


    /**
     * @param \string[] $dataSourceConfig
     */
    public function setDataSourceConfig($dataSourceConfig)
    {
        $this->dataSourceConfig = $dataSourceConfig;
    }

    /**
     * @return \string[]
     */
    public function getDataSourceConfig()
    {
        return $this->dataSourceConfig;
    }

    /**
     * @param string $chartType
     */
    public function setChartType($chartType)
    {
        $this->chartType = $chartType;
    }

    /**
     * @return string
     */
    public function getChartType()
    {
        return $this->chartType;
    }

    /**
     * @param string $pieColumn
     */
    public function setPieColumn($pieColumn)
    {
        $this->pieColumn = $pieColumn;
    }

    /**
     * @return string
     */
    public function getPieColumn()
    {
        return $this->pieColumn;
    }

    /**
     * @param string $xAxis
     */
    public function setXAxis($xAxis)
    {
        $this->xAxis = $xAxis;
    }

    /**
     * @return string
     */
    public function getXAxis()
    {
        return $this->xAxis;
    }

    /**
     * @param array|string $yAxis
     */
    public function setYAxis($yAxis)
    {
        $this->yAxis = $yAxis;
    }

    /**
     * @return array|string
     */
    public function getYAxis()
    {
        return $this->yAxis;
    }

    /**
     * @param string $pieLabelColumn
     */
    public function setPieLabelColumn($pieLabelColumn)
    {
        $this->pieLabelColumn = $pieLabelColumn;
    }

    /**
     * @return string
     */
    public function getPieLabelColumn()
    {
        return $this->pieLabelColumn;
    }

}