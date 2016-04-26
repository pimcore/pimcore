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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Pimcore\Tool;
use Pimcore\Image\Adapter;

class Image
{

    /**
     * @var string
     */
    protected static $defaultAdapter = null;

    /**
     * @param null $adapter
     * @return null|Adapter\GD|Adapter\Imagick
     * @throws \Exception
     */
    public static function getInstance($adapter = null)
    {

        // use the default adapter if set manually (!= null) and no specify adapter is given
        if (!$adapter && self::$defaultAdapter) {
            $adapter = self::$defaultAdapter;
        }

        try {
            if ($adapter) {
                $adapterClass = "\\Pimcore\\Image\\Adapter\\" . $adapter;
                if (Tool::classExists($adapterClass)) {
                    return new $adapterClass();
                } elseif (Tool::classExists($adapter)) {
                    return new $adapter();
                } else {
                    throw new \Exception("Image-transform adapter `" . $adapter . "´ does not exist.");
                }
            } else {
                if (extension_loaded("imagick")) {
                    return new Adapter\Imagick();
                } else {
                    return new Adapter\GD();
                }
            }
        } catch (\Exception $e) {
            \Logger::crit("Unable to load image extensions: " . $e->getMessage());
            throw $e;
        }

        return null;
    }

    /**
     * @param $adapter
     */
    public static function setDefaultAdapter($adapter)
    {
        self::$defaultAdapter = $adapter;
    }
}
