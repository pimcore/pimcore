<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
    public static function getInstance(?string $adapter = null): ?Document\Adapter
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
