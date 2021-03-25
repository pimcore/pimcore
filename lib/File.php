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

namespace Pimcore;

class File
{
    /**
     * @var int
     */
    public static $defaultMode = 0664;

    /**
     * @var array
     */
    private static $isIncludeableCache = [];

    /**
     * @var null|resource
     */
    protected static $context = null;

    /**
     * @param string $name
     *
     * @return string
     */
    public static function getFileExtension($name)
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
     */
    public static function getValidFilename($tmpFilename, $language = null, $replacement = '-')
    {
        $tmpFilename = \Pimcore\Tool\Transliteration::toASCII($tmpFilename, $language);
        $tmpFilename = strtolower($tmpFilename);
        $tmpFilename = preg_replace('/[^a-z0-9\-\.~_]+/', $replacement, $tmpFilename);

        // keys shouldn't start with a "." (=hidden file) *nix operating systems
        // keys shouldn't end with a "." - Windows issue: filesystem API trims automatically . at the end of a folder name (no warning ... et al)
        $tmpFilename = trim($tmpFilename, '. ');

        return $tmpFilename;
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    public static function isIncludeable($filename)
    {
        if (array_key_exists($filename, self::$isIncludeableCache)) {
            return self::$isIncludeableCache[$filename];
        }

        $isIncludeAble = false;

        // use stream_resolve_include_path if PHP is >= 5.3.2 because the performance is better
        if (function_exists('stream_resolve_include_path')) {
            if ($include = stream_resolve_include_path($filename)) {
                if (@is_readable($include)) {
                    $isIncludeAble = true;
                }
            }
        }

        // add to store
        self::$isIncludeableCache[$filename] = $isIncludeAble;

        return $isIncludeAble;
    }

    /**
     * @param int $mode
     */
    public static function setDefaultMode($mode)
    {
        self::$defaultMode = $mode;
    }

    /**
     * @return int
     */
    public static function getDefaultMode()
    {
        return self::$defaultMode;
    }

    /**
     * @param string $path
     * @param mixed $data
     *
     * @return int
     */
    public static function put($path, $data)
    {
        if (!is_dir(dirname($path))) {
            self::mkdir(dirname($path));
        }

        $return = file_put_contents($path, $data, null, self::getContext());
        @chmod($path, self::$defaultMode);

        return $return;
    }

    /**
     * @param string $path
     * @param mixed $data
     */
    public static function putPhpFile($path, $data)
    {
        self::put($path, $data);

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * @param string $path
     * @param int|null $mode
     * @param bool $recursive
     *
     * @return bool
     */
    public static function mkdir($path, $mode = 0775, $recursive = true)
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

    /**
     * @param string $oldPath
     * @param string $newPath
     *
     * @return bool
     */
    public static function rename($oldPath, $newPath)
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
    public static function setContext($context)
    {
        self::$context = $context;
    }
}
