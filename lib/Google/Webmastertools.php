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

namespace Pimcore\Google;

use Pimcore\Config;
use Pimcore\Model\Site;

class Webmastertools
{
    /**
     * @var array
     */
    public static $stack = [];

    /**
     * @param Site $site
     *
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
     * @param Site|null $site
     *
     * @return bool
     */
    public static function getSiteConfig($site = null)
    {
        $siteKey = \Pimcore\Tool\Frontend::getSiteKey($site);
        $config = Config::getReportConfig()->get('webmastertools');

        if (is_null($config)) {
            return false;
        }

        if ($config->sites->$siteKey->verification) {
            return $config->sites->$siteKey;
        }

        return false;
    }
}
