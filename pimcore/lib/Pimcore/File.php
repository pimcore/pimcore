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

class File {

    /**
     * @var int
     */
    public static $defaultMode = 0775;

    /**
     * @var array
     */
    private static $isIncludeableCache = array();

    /**
     * @static
     * @param  $name
     * @return string
     */
    public static function getFileExtension($name) {
        
        $name = strtolower($name);
        $parts = explode(".", $name);

        if(count($parts) > 1) {
            return strtolower($parts[count($parts) - 1]);
        }
        return "";
    }

    /**
     * @static
     * @param  $tmpFilename
     * @return string
     */
    public static function getValidFilename($tmpFilename) {
        
        $tmpFilename = \Pimcore\Tool\Transliteration::toASCII($tmpFilename);
        $tmpFilename = strtolower($tmpFilename);
        $tmpFilename = preg_replace('/[^a-z0-9\-\.~_]+/', '-', $tmpFilename);

        return $tmpFilename;
    }

    /**
     * @static
     * @param  $filename
     * @return bool
     */
    public static function isIncludeable($filename) {

        if(array_key_exists($filename,self::$isIncludeableCache)) {
            return self::$isIncludeableCache[$filename];
        }

        $isIncludeAble = false;

        // use stream_resolve_include_path if PHP is >= 5.3.2 because the performance is better
        if(function_exists("stream_resolve_include_path")) {
            if($include = stream_resolve_include_path($filename)) {
                if(@is_readable($include)) {
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
    public static function setDefaultMode($mode) {
        self::$defaultMode = $mode;
    }

    /**
     * @return int
     */
    public static function getDefaultMode() {
        return self::$defaultMode;
    }

    /**
     * @param $path
     * @param $data
     * @return int
     */
    public static function put ($path, $data) {

        if(!is_dir(dirname($path))) {
            self::mkdir(dirname($path));
        }

        $return = file_put_contents($path, $data);
        @chmod($path, self::$defaultMode);
        return $return;
    }

    /**
     * @param $path
     * @param null $mode
     * @param bool $recursive
     * @return bool
     */
    public static function mkdir($path, $mode = null, $recursive = true) {

        if(!$mode) {
            $mode = self::$defaultMode;
        }

        $return = @mkdir($path, 0777, $recursive);
        @chmod($path, $mode);
        return $return;
    }
}
