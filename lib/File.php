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

use League\Flysystem\FilesystemOperator;

class File
{
    public static int $defaultMode = 0664;

    /**
     * @var null|resource
     */
    protected static $context = null;

    private static int $defaultFlags = LOCK_EX;

    public static function getFileExtension(string $name): string
    {
        $name = strtolower($name);

        $pos = strrpos($name, '.');
        if ($pos) {
            $extension = substr($name, $pos + 1);
            if ($extension && strpos($extension, '/') === false) {
                return $extension;
            }
        }

        return '';
    }

    /**
     * Helper to get a valid filename for the filesystem, use Element\Service::getValidKey() for the use with Pimcore Elements
     *
     * @param string $tmpFilename
     * @param string|null $language
     * @param string $replacement
     *
     * @return string
     *
     *@internal
     *
     */
    public static function getValidFilename(string $tmpFilename, string $language = null, string $replacement = '-'): string
    {
        $tmpFilename = \Pimcore\Tool\Transliteration::toASCII($tmpFilename, $language);
        $tmpFilename = strtolower($tmpFilename);
        $tmpFilename = preg_replace('/[^a-z0-9\-\.~_]+/', $replacement, $tmpFilename);

        // keys shouldn't start with a "." (=hidden file) *nix operating systems
        // keys shouldn't end with a "." - Windows issue: filesystem API trims automatically . at the end of a folder name (no warning ... et al)
        $tmpFilename = trim($tmpFilename, '. ');

        return $tmpFilename;
    }

    public static function setDefaultMode(int $mode): void
    {
        self::$defaultMode = $mode;
    }

    public static function getDefaultMode(): int
    {
        return self::$defaultMode;
    }

    public static function setDefaultFlags(int $defaultFlags): void
    {
        self::$defaultFlags = $defaultFlags;
    }

    /**
     * @param string $path
     * @param mixed $data
     *
     * @return int|false
     */
    public static function put(string $path, mixed $data): bool|int
    {
        if (!is_dir(dirname($path))) {
            self::mkdir(dirname($path));
        }

        $return = file_put_contents($path, $data, self::$defaultFlags, self::getContext());
        @chmod($path, self::$defaultMode);

        return $return;
    }

    /**
     * @param string $path
     * @param string $data
     *
     * @return int|false
     *
     *@internal
     *
     */
    public static function putPhpFile(string $path, string $data): bool|int
    {
        $return = self::put($path, $data);

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        return $return;
    }

    public static function mkdir(string $path, ?int $mode = 0775, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }

        $return = true;

        $oldMask = umask(0);

        if ($recursive) {
            // we cannot use just mkdir() with recursive=true because of possible race conditions, see also
            // https://github.com/pimcore/pimcore/issues/4011

            $parts = preg_split('@(?<![\:\\\\/]|^)[\\\\/]@', $path);
            $currentPath = '';
            $lastKey = array_keys($parts)[count($parts) - 1];
            $parentPath = $parts[0];

            foreach ($parts as $key => $part) {
                $currentPath .= $part;

                if (!@is_writable($parentPath) && $key != $lastKey) {
                    // parent directories don't need to be read/writable (open_basedir restriction), see #4315
                } elseif (!is_dir($currentPath)) {
                    if (!@mkdir($currentPath, $mode, false) && !is_dir($currentPath)) {
                        // the directory was not created by either this or a concurrent process ...
                        $return = false;

                        break;
                    }
                }

                $parentPath = $currentPath;
                $currentPath .= '/';
            }
        } else {
            $return = @mkdir($path, $mode, false, self::getContext());
        }

        umask($oldMask);

        return $return;
    }

    public static function rename(string $oldPath, string $newPath): bool
    {
        if (stream_is_local($oldPath) && stream_is_local($newPath)) {
            // rename is only possible if both stream wrapper are the same
            // unfortunately it seems that there's no other alternative for stream_is_local() although it isn't
            // absolutely correct it solves the problem temporary
            $return = rename($oldPath, $newPath, self::getContext());
        } else {
            $return = recursiveCopy($oldPath, $newPath);
            recursiveDelete($oldPath);
        }

        return $return;
    }

    /**
     * @return null|resource
     */
    public static function getContext()
    {
        if (!self::$context) {
            self::$context = stream_context_create([]);
        }

        return self::$context;
    }

    /**
     * @param resource $context
     */
    public static function setContext($context): void
    {
        self::$context = $context;
    }

    public static function getLocalTempFilePath(?string $fileExtension = null): string
    {
        return sprintf('%s/temp-file-%s.%s',
            PIMCORE_SYSTEM_TEMP_DIRECTORY,
            uniqid() . '-' .  bin2hex(random_bytes(15)),
            $fileExtension ?: 'tmp'
        );
    }

    /**
     * @param FilesystemOperator $storage
     * @param string $storagePath
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public static function recursiveDeleteEmptyDirs(FilesystemOperator $storage, string $storagePath): void
    {
        if ($storagePath === '.') {
            return;
        }

        $contents = $storage->listContents($storagePath);
        $count = iterator_count($contents);
        if ($count === 0) {
            $storage->deleteDirectory($storagePath);
            $storagePath = dirname($storagePath, 1);
            self::recursiveDeleteEmptyDirs($storage, $storagePath);
        }
    }
}
