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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document\Tag\Area;

use Pimcore\Model;

abstract class AbstractArea {

    /**
     * @var \Zend_View
     */
    protected $view;

    /**
     * @var \Zend_Config
     */
    protected $config;

    /**
     * @var Info
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
        return $this;
    }

    /**
     * @return \Zend_View
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
        return $this;
    }

    /**
     * @return \Zend_Config
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
        return $this;
    }

    /**
     * @param Info $brick
     * @return $this
     */
    public function setBrick($brick)
    {
        $this->brick = $brick;
        return $this;
    }

    /**
     * @return Info
     */
    public function getBrick()
    {
        return $this->brick;
    }
}
