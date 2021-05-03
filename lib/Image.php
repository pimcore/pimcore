<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Pimcore;
use Pimcore\Image\Adapter;

final class Image
{
    /**
     * @return null|Adapter\GD|Adapter\Imagick
     *
     * @throws \Exception
     */
    public static function getInstance()
    {
        //@TODO should be configured on the container
        $adapter = self::create();

        return $adapter;
    }

    /**
     * @return null|Adapter\GD|Adapter\Imagick
     *
     * @throws \Exception
     *
     * @internal
     */
    public static function create()
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
