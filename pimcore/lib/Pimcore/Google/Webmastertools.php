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

namespace Pimcore\Google;

use Pimcore\Config;
use Pimcore\Model\Site;

class Webmastertools {

    /**
     * @var array
     */
    public static $stack = array();

    /**
     * @param Site $site
     * @return bool
     */
    public static function isConfigured (Site $site = null) {
        if(self::getSiteConfig($site)) {
            return true;
        }
        return false;
    }

    /**
     * @param null $site
     * @return bool
     */
    public static function getSiteConfig ($site = null) {
        
        $siteKey = \Pimcore\Tool\Frontend::getSiteKey($site);
        
        if(Config::getReportConfig()->webmastertools->sites->$siteKey->verification) {
            return Config::getReportConfig()->webmastertools->sites->$siteKey;
        }
        return false;
    }
}
