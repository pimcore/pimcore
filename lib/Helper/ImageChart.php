<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Helper;

/**
 * @internal
 */
final class ImageChart
{
    public static string $serviceUrl = 'https://chart.googleapis.com/chart';

    public static function lineSmall(array $data, string $parameters = ''): string
    {
        return self::$serviceUrl . '?cht=lc&chs=150x40&chd=t:' . implode(',', $data) . '&chds=' . min($data) . ',' . max($data) . '&' . $parameters;
    }
}
