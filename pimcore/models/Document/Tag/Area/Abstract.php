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
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Document_Tag_Area_Abstract {

    /**
     * @var Zend_View
     */
    protected $view;

    /**
     * @var Zend_Config
     */
    protected $config;

    /**
     * @var Document_Tag_Area_Info
     */
    protected $brick;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @param $view
     * @return void
     */
    public function setView ($view) {
        $this->view = $view;
    }

    /**
     * @return Zend_View
     */
    public function getView() {
        return $this->view;
    }

    /**
     * @param $config
     * @return void
     */
    public function setConfig ($config) {
        $this->config = $config;
    }

    /**
     * @return Zend_Config
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getParam($key) {
        if(array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }
        return;
    }

    /**
     * @return array
     */
    public function getAllParams () {
        return $this->params;
    }

    /**
     * @deprecated
     * @param $key
     * @return mixed
     */
    public function _getParam($key) {
        return $this->getParam($key);
    }

    /**
     * @deprecated
     * @return array
     */
    public function _getAllParams () {
        return $this->getAllParams();
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function addParam ($key, $value) {
        $this->params[$key] = $value;
    }

    /**
     * @param $params
     * @return void
     */
    public function setParams ($params) {
        $this->params = $params;
    }

    /**
     * @param \Document_Tag_Area_Info $brick
     */
    public function setBrick($brick)
    {
        $this->brick = $brick;
    }

    /**
     * @return \Document_Tag_Area_Info
     */
    public function getBrick()
    {
        return $this->brick;
    }
}
