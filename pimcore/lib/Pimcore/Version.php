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

class Version {

    /**
     * @var string
     */
    public static $version = "3.0.6";

    /**
     * @var int
     */
    public static $revision = 3500;

    /**
     * @var array
     */
   public static $svnInfo = array (
  'Path' => 'trunk',
  'URL' => 'http',
  'Repository Root' => 'http',
  'Repository UUID' => '1f8fe7d8-47f0-464c-8d0a-336f4953ab05',
  'Revision' => '6562',
  'Node Kind' => 'directory',
  'Last Changed Author' => 'brusch',
  'Last Changed Rev' => '6562',
  'Last Changed Date' => '2015-06-03 09',
);

    /**
     * @return string
     */
    public static function getVersion() {
        return self::$version;
    }

    /**
     * @return int
     */
    public static function getRevision()
    {
        return self::$revision;
    }

    /**
     * @return array
     */
    public static function getSvnInfo()
    {
        return self::$svnInfo;
    }
}
