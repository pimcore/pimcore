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
use Pimcore\Logger;

class Document
{

    /**
     * Singleton for Pimcore\Document
     *
     * @param null $adapter
     * @return bool|null|Document
     * @throws \Exception
     */
    public static function getInstance($adapter = null)
    {
        try {
            if ($adapter) {
                $adapterClass = "\\Pimcore\\Document\\Adapter\\" . $adapter;
                if (Tool::classExists($adapterClass)) {
                    return new $adapterClass();
                } else {
                    throw new \Exception("document-transcode adapter `" . $adapter . "´ does not exist.");
                }
            } else {
                if ($adapter = self::getDefaultAdapter()) {
                    return $adapter;
                }
            }
        } catch (\Exception $e) {
            Logger::crit("Unable to load document adapter: " . $e->getMessage());
            throw $e;
        }

        return null;
    }

    /**
     * Checks if adapter is available.
     *
     * @return bool
     */
    public static function isAvailable()
    {
        if (self::getDefaultAdapter()) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a file type is supported by the adapter.
     *
     * @param $filetype
     * @return bool
     */
    public static function isFileTypeSupported($filetype)
    {
        if (self::getDefaultAdapter()) {
            if ($adapter = self::getDefaultAdapter()) {
                return $adapter->isFileTypeSupported($filetype);
            }
        }

        return false;
    }

    /**
     * Returns adapter class if exists or false if doesn't exist
     *
     * @return bool|\Pimcore\Document\Adapter
     */
    public static function getDefaultAdapter()
    {
        $adapters = ["LibreOffice", "Ghostscript"];

        foreach ($adapters as $adapter) {
            $adapterClass = "\\Pimcore\\Document\\Adapter\\" . $adapter;
            if (Tool::classExists($adapterClass)) {
                try {
                    $adapter = new $adapterClass();
                    if ($adapter->isAvailable()) {
                        return $adapter;
                    }
                } catch (\Exception $e) {
                    Logger::warning($e);
                }
            }
        }

        return null;
    }
}
