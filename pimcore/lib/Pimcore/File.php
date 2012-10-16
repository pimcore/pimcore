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

class Pimcore_File {

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
        
        $tmpFilename = Pimcore_Tool_Transliteration::toASCII($tmpFilename);
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
}
