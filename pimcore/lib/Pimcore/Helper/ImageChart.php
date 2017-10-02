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

namespace Pimcore\Helper;

class ImageChart
{
    /**
     * @var string
     */
    public static $serviceUrl = 'https://chart.googleapis.com/chart';

    /**
     * @param $data
     * @param string $parameters
     *
     * @return string
     */
    public static function lineSmall($data, $parameters='')
    {
        return self::$serviceUrl . '?cht=lc&chs=150x40&chd=t:' . implode(',', $data) . '&chds=' . min($data) . ',' . max($data) . '&' . $parameters;
    }
}
