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
        return $this->getView()->getParam($key);
    }

    public function getAllParams () {
        return $this->getView()->getAllParams();
    }

    public function _getParam($key) {
        return $this->getParam($key);
    }

    public function _getAllParams () {
        return $this->getAllParams();
    }
}
