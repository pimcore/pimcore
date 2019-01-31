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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\File;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Tool;
use Psr\Log\LoggerInterface;

final class DownloadMaxMinDbTask implements TaskInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
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

                $this->logger->info('Updated MaxMind GeoIP2 Database in: ' . $geoDbFile);
            } else {
                $this->logger->error('Failed to update MaxMind GeoIP2, size is under about 1M');
            }
        } else {
            $this->logger->debug('MayMind GeoIP2 Download skipped, everything up to date, last update: ' . date('m/d/Y H:i', $filemtime));
        }
    }
}
