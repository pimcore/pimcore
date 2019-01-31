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

use Pimcore\Cache\Symfony\CacheClearer;

class Update
{
    public static function clearSymfonyCaches()
    {
        // clear symfony cache
        $symfonyCacheClearer = new CacheClearer();
        foreach (array_unique(['dev', 'prod', \Pimcore::getKernel()->getEnvironment()]) as $env) {
            $symfonyCacheClearer->clear($env, [
                // warmup will break the request as it will try to re-declare the appDevDebugProjectContainerUrlMatcher class
                'no-warmup' => true
            ]);
        }
    }

    public static function clearOPCaches()
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    public static function cleanup()
    {

        // remove database tmp table
        $db = Db::get();
        $db->query('DROP TABLE IF EXISTS `' . self::$tmpTable . '`');

        //delete tmp data
        recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/update', true);
    }
}
