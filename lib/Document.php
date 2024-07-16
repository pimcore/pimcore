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

use Exception;

/**
 * @internal
 */
class Document
{
    /**
     * Singleton for Pimcore\Document
     *
     *
     *
     * @throws Exception
     */
    public static function getInstance(string $adapter = null): ?Document\Adapter
    {
        try {
            if ($adapter) {
                $adapterClass = '\\Pimcore\\Document\\Adapter\\' . $adapter;
                if (Tool::classExists($adapterClass)) {
                    return new $adapterClass();
                } else {
                    throw new Exception('document-transcode adapter `' . $adapter . '´ does not exist.');
                }
            } else {
                if ($adapter = self::getDefaultAdapter()) {
                    return $adapter;
                }
            }
        } catch (Exception $e) {
            Logger::crit('Unable to load document adapter: ' . $e->getMessage());

            throw $e;
        }

        return null;
    }

    /**
     * Checks if adapter is available.
     *
     */
    public static function isAvailable(): bool
    {
        if (self::getDefaultAdapter()) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a file type is supported by the adapter.
     *
     *
     */
    public static function isFileTypeSupported(string $filetype): bool
    {
        if ($adapter = self::getDefaultAdapter()) {
            return $adapter->isFileTypeSupported($filetype);
        }

        return false;
    }

    /**
     * Returns adapter class if exists or false if doesn't exist
     *
     */
    public static function getDefaultAdapter(): ?Document\Adapter
    {
        $adapters = ['Gotenberg', 'LibreOffice', 'Ghostscript'];

        foreach ($adapters as $adapter) {
            $adapterClass = '\\Pimcore\\Document\\Adapter\\' . $adapter;
            if (Tool::classExists($adapterClass)) {
                try {
                    $adapter = new $adapterClass();
                    if ($adapter->isAvailable()) {
                        return $adapter;
                    }
                } catch (Exception $e) {
                    Logger::warning((string) $e);
                }
            }
        }

        return null;
    }
}
