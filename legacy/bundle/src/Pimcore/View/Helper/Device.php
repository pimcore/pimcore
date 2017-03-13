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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\View\Helper;

use Pimcore\Tool\DeviceDetector;

class Device extends \Zend_View_Helper_Abstract
{

    /**
     * @var DeviceDetector
     */
    public static $_controller;

    /**
     * @param null $default
     * @return DeviceDetector
     */
    public function device($default = null)
    {
        return DeviceDetector::getInstance($default);
    }
}
