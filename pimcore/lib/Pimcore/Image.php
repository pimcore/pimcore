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

namespace Pimcore;

use Pimcore\Tool; 
use Pimcore\Image\Adapter;

class Image {

    /**
     * @var string
     */
    protected static $defaultAdapter = null;

    /**
     * @param null $adapter
     * @return null|Adapter\GD|Adapter\Imagick
     * @throws \Exception
     */
    public static function getInstance ($adapter = null) {

        // use the default adapter if set manually (!= null) and no specify adapter is given
        if(!$adapter && self::$defaultAdapter) {
            $adapter = self::$defaultAdapter;
        }

        try {
            if($adapter) {
                $adapterClass = "\\Pimcore\\Image\\Adapter\\" . $adapter;
                if(Tool::classExists($adapterClass)) {
                    return new $adapterClass();
                } else if (Tool::classExists($adapter)) {
                    return new $adapter();
                } else {
                    throw new \Exception("Image-transform adapter `" . $adapter . "´ does not exist.");
                }
            } else {
                if(extension_loaded("imagick")) {
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
    public static function setDefaultAdapter($adapter) {
        self::$defaultAdapter = $adapter;
    }
}
