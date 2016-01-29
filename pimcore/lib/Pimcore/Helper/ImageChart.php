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

namespace Pimcore\Helper;

class ImageChart
{

    /**
     * @var string
     */
    public static $serviceUrl = "https://chart.googleapis.com/chart";

    /**
     * @param $data
     * @param string $parameters
     * @return string
     */
    public static function lineSmall($data, $parameters="")
    {
        return self::$serviceUrl . "?cht=lc&chs=150x40&chd=t:" . implode(",", $data) . "&chds=" . min($data) . "," . max($data) . "&" . $parameters;
    }
}
