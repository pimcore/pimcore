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

namespace Pimcore\Tool;

use Pimcore\File;

/**
 * @deprecated
 */
class Archive
{
    /**
     * @param string $sourceDir
     * @param string $destinationFile
     * @param array $excludeFilePattern
     * @param array $options
     *
     * @return \ZipArchive
     *
     * @throws \Exception
     */
    public static function createZip($sourceDir, $destinationFile, $excludeFilePattern = [], $options = [])
    {
        @trigger_error(sprintf('Method "%s" is deprecated since v6.8 and will be removed in 7. ', __METHOD__), E_USER_DEPRECATED);

        list($sourceDir, $destinationFile, $items) = self::prepareArchive($sourceDir, $destinationFile);
        $mode = $options['mode'];

        if (!$mode) {
            $mode = (is_file($destinationFile)) ? \ZipArchive::OVERWRITE : \ZipArchive::CREATE;
        }

        if (substr($sourceDir, -1, 1) != DIRECTORY_SEPARATOR) {
            $sourceDir .= DIRECTORY_SEPARATOR;
        }

        if (is_dir($sourceDir) && is_readable($sourceDir)) {
            $items = rscandir($sourceDir);
        } else {
            throw new \Exception("$sourceDir doesn't exits or is not readable!");
        }

        if (!$destinationFile || !is_string($destinationFile)) {
            throw new \Exception('No destinationFile provided!');
        } else {
            @unlink($destinationFile);
        }

        $destinationDir = dirname($destinationFile);
        if (!is_dir($destinationDir)) {
            File::mkdir($destinationDir);
        }

        $zip = new \ZipArchive();
        $opened = $zip->open($destinationFile, $mode);

        if ($opened !== true) {
            throw new \Exception("Couldn't open archive file. Error: " . $opened);
        }
        foreach ($items as $item) {
            $zipPath = str_replace($sourceDir, '', $item);

            foreach ($excludeFilePattern as $excludePattern) {
                if (preg_match($excludePattern, $zipPath)) {
                    continue 2;
                }
            }

            if (is_dir($item)) {
                $zip->addEmptyDir($zipPath);
            } elseif (is_file($item)) {
                $zip->addFile($item, $zipPath);
            }
        }

        if (!$zip->close()) {
            throw new \Exception("Couldn't close zip file!");
        }

        return $zip;
    }

    /**
     * @param string $sourceDir
     * @param string $destinationFile
     * @param array $excludeFilePattern
     * @param array $options
     *
     * @return \Phar
     *
     * @throws \Exception
     */
    public static function createPhar($sourceDir, $destinationFile, $excludeFilePattern = [], $options = [])
    {
        @trigger_error(sprintf('Method "%s" is deprecated since v6.8 and will be removed in 7. ', __METHOD__), E_USER_DEPRECATED);

        list($sourceDir, $destinationFile, $items) = self::prepareArchive($sourceDir, $destinationFile);

        $alias = $options['alias'] ? $options['alias'] : 'archive.phar';

        $phar = new \Phar($destinationFile, 0, $alias);
        if ($options['compress']) {
            $phar = $phar->convertToExecutable(\Phar::TAR, \Phar::GZ);
        }

        foreach ($items as $item) {
            $zipPath = str_replace($sourceDir, '', $item);

            foreach ((array)$excludeFilePattern as $excludePattern) {
                if (preg_match($excludePattern, $zipPath)) {
                    continue 2;
                }
            }

            if (is_dir($item)) {
                $phar->addEmptyDir($zipPath);
            } elseif (is_file($item)) {
                $phar->addFile($item, $zipPath);
            }
        }

        if ($metaData = $options['metaData']) {
            $phar->setMetadata($metaData);
        }
        $phar->stopBuffering();

        return $phar;
    }

    /**
     * @param string $sourceDir
     * @param string $destinationFile
     *
     * @return array
     *
     * @throws \Exception
     */
    protected static function prepareArchive($sourceDir, $destinationFile)
    {
        if (substr($sourceDir, -1, 1) != DIRECTORY_SEPARATOR) {
            $sourceDir .= DIRECTORY_SEPARATOR;
        }

        if (is_dir($sourceDir) && is_readable($sourceDir)) {
            $items = rscandir($sourceDir);
        } else {
            throw new \Exception("$sourceDir doesn't exits or is not readable!");
        }

        if (!$destinationFile || !is_string($destinationFile)) {
            throw new \Exception('No destinationFile provided!');
        } else {
            @unlink($destinationFile);
        }

        $destinationDir = dirname($destinationFile);
        if (!is_dir($destinationDir)) {
            File::mkdir($destinationDir);
        }

        return [$sourceDir, $destinationFile, $items];
    }
}
