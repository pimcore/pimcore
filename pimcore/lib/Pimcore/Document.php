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

class Document {

    /**
     * @param null $adapter
     * @return bool|null|Document
     * @throws \Exception
     */
    public static function getInstance ($adapter = null) {
        try {
            if($adapter) {
                $adapterClass = "\\Pimcore\\Document\\Adapter\\" . $adapter;
                if(Tool::classExists($adapterClass)) {
                    return new $adapterClass();
                } else {
                    throw new \Exception("document-transcode adapter `" . $adapter . "´ does not exist.");
                }
            } else {
                if($adapter = self::getDefaultAdapter()) {
                    return $adapter;
                }
            }
        } catch (\Exception $e) {
            \Logger::crit("Unable to load document adapter: " . $e->getMessage());
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
     * @param $filetype
     * @return bool
     */
    public static function isFileTypeSupported($filetype) {
        if(self::getDefaultAdapter()) {
            if($adapter = self::getDefaultAdapter()) {
                return $adapter->isFileTypeSupported($filetype);
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public static function getDefaultAdapter () {

        $adapters = array("LibreOffice", "Ghostscript");

        foreach ($adapters as $adapter) {
            $adapterClass = "\\Pimcore\\Document\\Adapter\\" . $adapter;
            if(Tool::classExists($adapterClass)) {
                try {
                    $adapter = new $adapterClass();
                    if($adapter->isAvailable()) {
                        return $adapter;
                    }
                } catch (\Exception $e) {
                    \Logger::warning($e);
                }
            }
        }

        return null;
    }
}
