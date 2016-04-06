<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in 
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller\Plugin;

class WysiwygAttributes extends \Zend_Controller_Plugin_Abstract
{

    /**
     *
     */
    public function dispatchLoopShutdown()
    {
        if (!\Pimcore\Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        // removes the non-valid html attributes which are used by the wysiwyg editor for ID based linking        
        $body = $this->getResponse()->getBody();
        $body = preg_replace("/ pimcore_(id|type|disable_thumbnail)=\\\"([0-9a-z]+)\\\"/", "", $body);
        $this->getResponse()->setBody($body);
    }
}
