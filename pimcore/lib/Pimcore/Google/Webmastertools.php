<?php 
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Google;

use Pimcore\Config;
use Pimcore\Model\Site;

class Webmastertools
{

    /**
     * @var array
     */
    public static $stack = array();

    /**
     * @param Site $site
     * @return bool
     */
    public static function isConfigured(Site $site = null)
    {
        if (self::getSiteConfig($site)) {
            return true;
        }
        return false;
    }

    /**
     * @param null $site
     * @return bool
     */
    public static function getSiteConfig($site = null)
    {
        $siteKey = \Pimcore\Tool\Frontend::getSiteKey($site);
        
        if (Config::getReportConfig()->webmastertools->sites->$siteKey->verification) {
            return Config::getReportConfig()->webmastertools->sites->$siteKey;
        }
        return false;
    }
}
