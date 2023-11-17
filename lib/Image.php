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

namespace Pimcore;

use Pimcore;
use Pimcore\Image\Adapter;

final class Image
{
    /**
     *
     * @throws \Exception
     */
    public static function getInstance(): Adapter\GD|Adapter\Imagick|null
    {
        //@TODO should be configured on the container
        $adapter = self::create();

        return $adapter;
    }

    /**
     *
     * @throws \Exception
     *
     * @internal
     */
    public static function create(): Adapter\GD|Adapter\Imagick|null
    {
        try {
            if (extension_loaded('imagick')) {
                return Pimcore::getContainer()->get(Adapter\Imagick::class);
            } else {
                return Pimcore::getContainer()->get(Adapter\GD::class);
            }
        } catch (\Exception $e) {
            Logger::crit('Unable to load image extensions: ' . $e->getMessage());

            throw $e;
        }
    }
}
