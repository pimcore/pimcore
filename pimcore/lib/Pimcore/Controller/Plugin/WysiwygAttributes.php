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

class Pimcore_Controller_Plugin_WysiwygAttributes extends Zend_Controller_Plugin_Abstract {


    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        // removes the non-valid html attributes which are used by the wysiwyg editor for ID based linking        
        $body = $this->getResponse()->getBody();
        $body = preg_replace("/ pimcore_(id|type|disable_thumbnail)=\\\"([0-9a-z]+)\\\"/","",$body);
        $this->getResponse()->setBody($body);
    }
}

