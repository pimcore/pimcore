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

namespace Pimcore;

use Pimcore\Image\Adapter;

class Image
{
    /**
     * @return null|Adapter\GD|Adapter\Imagick
     * @throws \Exception
     */
    public static function getInstance()
    {
        $adapter = \Pimcore::getDiContainer()->make(Image\Adapter::class);

        return $adapter;
    }

    /**
     * @return null|Adapter\GD|Adapter\Imagick
     * @throws \Exception
     */
    public static function create()
    {
        try {
            if (extension_loaded("imagick")) {
                return new Adapter\Imagick();
            } else {
                return new Adapter\GD();
            }
        } catch (\Exception $e) {
            Logger::crit("Unable to load image extensions: " . $e->getMessage());
            throw $e;
        }
    }
}
