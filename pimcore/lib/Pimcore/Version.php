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

class Pimcore_Version {

    public static $version = "1.4.10";

    public static $revision = 2819;

    public static $svnInfo = array (
  'Path' => 'trunk',
  'URL' => 'http',
  'Repository Root' => 'http',
  'Repository UUID' => '1f8fe7d8-47f0-464c-8d0a-336f4953ab05',
  'Revision' => '3336',
  'Node Kind' => 'directory',
  'Last Changed Author' => 'brusch',
  'Last Changed Rev' => '3336',
  'Last Changed Date' => '2013-07-23 20',
);

    public static function getVersion() {
        return self::$version;
    }

    public static function getRevision()
    {
        return self::$revision;
    }

    public static function getSvnInfo()
    {
        return self::$svnInfo;
    }
}
