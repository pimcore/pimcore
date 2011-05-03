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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Document_Tag_Area_Abstract {
    
    protected $view;
    protected $config;
    protected $params = array();
    
    public function setView ($view) {
        $this->view = $view;
    }
    
    public function getView() {
        return $this->view;
    }
    
    public function setConfig ($config) {
        $this->config = $config;
    }
    
    public function getConfig() {
        return $this->config;
    }

    public function getParam($key) {
        if(array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }
        return;
    }

    public function getAllParams () {
        return $this->params;
    }

    public function _getParam($key) {
        return $this->getParam($key);
    }

    public function _getAllParams () {
        return $this->getAllParams();
    }

    public function addParam ($key, $value) {
        $this->params[$key] = $value;
    }

    public function setParams ($params) {
        $this->params = $params;
    }
}
