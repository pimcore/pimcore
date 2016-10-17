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

class File
{

    /**
     * @var int
     */
    public static $defaultMode = 0775;

    /**
     * @var array
     */
    private static $isIncludeableCache = [];

    /**
     * @var null|resource
     */
    protected static $context = null;

    /**
     * @static
     * @param  $name
     * @return string
     */
    public static function getFileExtension($name)
    {
        $name = strtolower($name);
        $parts = explode(".", $name);

        if (count($parts) > 1) {
            return strtolower($parts[count($parts) - 1]);
        }

        return "";
    }

    /**
     * @static
     * @param  $tmpFilename
     * @param null $language
     * @return string
     */
    public static function getValidFilename($tmpFilename, $language = null)
    {
        $tmpFilename = \Pimcore\Tool\Transliteration::toASCII($tmpFilename, $language);
        $tmpFilename = strtolower($tmpFilename);
        $tmpFilename = preg_replace('/[^a-z0-9\-\.~_]+/', '-', $tmpFilename);

        // keys shouldn't start with a "." (=hidden file) *nix operating systems
        // keys shouldn't end with a "." - Windows issue: filesystem API trims automatically . at the end of a folder name (no warning ... et al)
        $tmpFilename = trim($tmpFilename, ". ");

        return $tmpFilename;
    }

    /**
     * @static
     * @param  $filename
     * @return bool
     */
    public static function isIncludeable($filename)
    {
        if (array_key_exists($filename, self::$isIncludeableCache)) {
            return self::$isIncludeableCache[$filename];
        }

        $isIncludeAble = false;

        // use stream_resolve_include_path if PHP is >= 5.3.2 because the performance is better
        if (function_exists("stream_resolve_include_path")) {
            if ($include = stream_resolve_include_path($filename)) {
                if (@is_readable($include)) {
                    $isIncludeAble = true;
                }
            }
        } else {
            // this is the fallback for PHP < 5.3.2
            $include_paths = explode(PATH_SEPARATOR, get_include_path());

            foreach ($include_paths as $path) {
                $include = $path.DIRECTORY_SEPARATOR.$filename;
                if (@is_file($include) && @is_readable($include)) {
                    $isIncludeAble = true;
                    break;
                }
            }
        }

        // add to store
        self::$isIncludeableCache[$filename] = $isIncludeAble;

        return $isIncludeAble;
    }

    /**
     * @param $mode
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
     * @param $path
     * @param $data
     * @return int
     */
    public static function put($path, $data)
    {
        if (!is_dir(dirname($path))) {
            self::mkdir(dirname($path));
        }

        $return = file_put_contents($path, $data, null, File::getContext());
        @chmod($path, self::$defaultMode);

        return $return;
    }

    /**
     * @param $path
     * @param $data
     */
    public static function putPhpFile($path, $data)
    {
        self::put($path, $data);

        if (function_exists("opcache_reset")) {
            opcache_reset();
        }
    }

    /**
     * @param $path
     * @param null $mode
     * @param bool $recursive
     * @return bool
     */
    public static function mkdir($path, $mode = null, $recursive = true)
    {
        if (!$mode) {
            $mode = self::$defaultMode;
        }

        $return = @mkdir($path, $mode, $recursive, self::getContext());

        return $return;
    }

    /**
     * @param $oldPath
     * @param $newPath
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
