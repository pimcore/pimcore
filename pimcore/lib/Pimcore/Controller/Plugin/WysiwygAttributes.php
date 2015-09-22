<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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

