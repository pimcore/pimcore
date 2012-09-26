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

class Pimcore_Controller_Action_Helper_Json extends Zend_Controller_Action_Helper_Json {

    public function direct($data, $sendNow = true, $keepLayouts = false) {

        // hack for FCGI because ZF doesn't care of duplicate headers
        $this->getResponse()->clearHeader("Content-Type");

        $this->suppressExit = !$sendNow;

        $d = $this->sendJson($data, $keepLayouts);
        return $d;
    }

}
