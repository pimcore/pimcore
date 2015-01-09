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

namespace Pimcore\Controller\Action\Helper;

class Json extends \Zend_Controller_Action_Helper_Json {

    /**
     * @param mixed $data
     * @param bool $sendNow
     * @param bool $keepLayouts
     * @param bool $encodeData
     * @return string|void
     */
    public function direct($data, $sendNow = true, $keepLayouts = false, $encodeData = true) {

        if($encodeData) {
            $data = \Pimcore\Tool\Serialize::removeReferenceLoops($data);
        }

        // hack for FCGI because ZF doesn't care of duplicate headers
        $this->getResponse()->clearHeader("Content-Type");

        $this->suppressExit = !$sendNow;

        $d = $this->sendJson($data, $keepLayouts, $encodeData);
        return $d;
    }
}

// unfortunately we need this alias here, since ZF plugin loader isn't able to handle namespaces correctly
class_alias("Pimcore\\Controller\\Action\\Helper\\Json", "Pimcore_Controller_Action_Helper_Json");
