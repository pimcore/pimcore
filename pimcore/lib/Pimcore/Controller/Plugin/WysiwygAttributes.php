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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Controller\Plugin;

class WysiwygAttributes extends \Zend_Controller_Plugin_Abstract {

    /**
     *
     */
    public function dispatchLoopShutdown() {
        
        if(!\Pimcore\Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        // removes the non-valid html attributes which are used by the wysiwyg editor for ID based linking        
        $body = $this->getResponse()->getBody();
        $body = preg_replace("/ pimcore_(id|type|disable_thumbnail)=\\\"([0-9a-z]+)\\\"/","",$body);
        $this->getResponse()->setBody($body);
    }
}

