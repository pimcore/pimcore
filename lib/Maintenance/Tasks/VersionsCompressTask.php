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

use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;

final class VersionsCompressTask implements TaskInterface
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
        $perIteration = 100;
        $alreadyCompressedCounter = 0;

        $list = new Version\Listing();
        $list->setCondition('date < ' . (time() - 86400 * 30));
        $list->setOrderKey('date');
        $list->setOrder('DESC');
        $list->setLimit($perIteration);

        $total = $list->getTotalCount();
        $iterations = ceil($total / $perIteration);

        for ($i = 0; $i < $iterations; $i++) {
            $this->logger->debug('iteration ' . ($i + 1) . ' of ' . $iterations);

            $list->setOffset($i * $perIteration);

            $versions = $list->load();

            foreach ($versions as $version) {
                if (file_exists($version->getFilePath())) {
                    gzcompressfile($version->getFilePath(), 9);
                    @unlink($version->getFilePath());

                    $alreadyCompressedCounter = 0;

                    $this->logger->debug('version compressed:' . $version->getFilePath());
                    $this->logger->debug('Waiting 1 sec to not kill the server...');
                    sleep(1);
                } else {
                    $alreadyCompressedCounter++;
                }
            }

            \Pimcore::collectGarbage();

            // check here how many already compressed versions we've found so far, if over 100 skip here
            // this is necessary to keep the load on the system low
            // is would be very unusual that older versions are not already compressed, so we assume that only new
            // versions need to be compressed, that's not perfect but a compromise we can (hopefully) live with.
            if ($alreadyCompressedCounter > 100) {
                $this->logger->debug('Over ' . $alreadyCompressedCounter . " versions were already compressed before, it doesn't seem that there are still uncompressed versions in the past, skip...");

                return;
            }
        }
    }
}
