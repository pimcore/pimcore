<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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