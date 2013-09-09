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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Pimcore_Video {

    /**
     * @static
     * @return null|Pimcore_Video_Adapter
     */
    public static function getInstance ($adapter = null) {
        try {
            if($adapter) {
                $adapterClass = "Pimcore_Video_Adapter_" . $adapter;
                if(Pimcore_Tool::classExists($adapterClass)) {
                    return new $adapterClass();
                } else {
                    throw new Exception("Video-transcode adapter `" . $adapter . "´ does not exist.");
                }
            } else {
                if($adapter = self::getDefaultAdapter()) {
                    return $adapter;
                }
            }
        } catch (Exception $e) {
            Logger::crit("Unable to load video adapter: " . $e->getMessage());
            throw $e;
        }

        return null;
    }

    /**
     * @return bool
     */
    public static function isAvailable () {
        if(self::getDefaultAdapter()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public static function getDefaultAdapter () {

        $adapters = array("Ffmpeg");

        foreach ($adapters as $adapter) {
            $adapterClass = "Pimcore_Video_Adapter_" . $adapter;
            if(Pimcore_Tool::classExists($adapterClass)) {
                try {
                    $adapter = new $adapterClass();
                    if($adapter->isAvailable()) {
                        return $adapter;
                    }
                } catch (Exception $e) {
                    Logger::warning($e);
                }
            }
        }

        return null;
    }
}
