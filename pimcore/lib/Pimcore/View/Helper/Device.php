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

namespace Pimcore\View\Helper;

use Pimcore\Tool\DeviceDetector;

class Device extends \Zend_View_Helper_Abstract {

    /**
     * @var DeviceDetector
     */
    public static $_controller;

    /**
     * @param null $default
     * @return DeviceDetector
     */
    public function device($default = null){
        return DeviceDetector::getInstance($default);
    }
}