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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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
                return new Pimcore_Video_Adapter_Ffmpeg();
            }
        } catch (Exception $e) {
            Logger::crit("Unable to load video adapter: " . $e->getMessage());
            throw $e;
        }

        return null;
    }

    public static function isAvailable () {
        try {
            $ffmpeg = Pimcore_Video_Adapter_Ffmpeg::getFfmpegCli();
            $phpCli = Pimcore_Tool_Console::getPhpCli();
            if(!$ffmpeg || !$phpCli) {
                throw new Exception("ffmpeg is not available");
            }

            return true;
        } catch (Exception $e) {
            Logger::warning($e);
        }

        return false;
    }
}
