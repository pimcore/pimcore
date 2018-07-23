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
use Pimcore\Composer\Config\ConfigMerger;
use Pimcore\FeatureToggles\Features\DevMode;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Process\Process;

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

    public static function updateMaxmindDb()
    {
        $downloadUrl = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';
        $geoDbFile = PIMCORE_CONFIGURATION_DIRECTORY . '/GeoLite2-City.mmdb';
        $geoDbFileGz = $geoDbFile . '.gz';

        $firstTuesdayOfMonth = strtotime(date('F') . ' 2013 tuesday');
        $filemtime = 0;
        if (file_exists($geoDbFile)) {
            $filemtime = filemtime($geoDbFile);
        }

        // update if file is older than 30 days, or if it is the first tuesday of the month
        if ($filemtime < (time() - 30 * 86400) || (date('m/d/Y') == date('m/d/Y', $firstTuesdayOfMonth) && $filemtime < time() - 86400)) {
            $data = Tool::getHttpData($downloadUrl);
            if (strlen($data) > 1000000) {
                File::put($geoDbFileGz, $data);

                @unlink($geoDbFile);

                $sfp = gzopen($geoDbFileGz, 'rb');
                $fp = fopen($geoDbFile, 'w');

                while ($string = gzread($sfp, 4096)) {
                    fwrite($fp, $string, strlen($string));
                }
                gzclose($sfp);
                fclose($fp);

                unlink($geoDbFileGz);

                Logger::info('Updated MaxMind GeoIP2 Database in: ' . $geoDbFile);
            } else {
                Logger::err('Failed to update MaxMind GeoIP2, size is under about 1M');
            }
        } else {
            Logger::debug('MayMind GeoIP2 Download skipped, everything up to date, last update: ' . date('m/d/Y H:i', $filemtime));
        }
    }
}
