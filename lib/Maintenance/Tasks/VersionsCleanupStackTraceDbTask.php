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

final class VersionsCleanupStackTraceDbTask implements TaskInterface
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
        $list = new Version\Listing();
        $list->setCondition('date < ' . (time() - 86400 * 7) . ' AND stackTrace IS NOT NULL');
        $list->setOrderKey('date');
        $list->setOrder('DESC');

        $total = $list->getTotalCount();
        $perIteration = 500;

        for ($i = 0; $i < (ceil($total / $perIteration)); $i++) {
            $list->setLimit($perIteration);
            $list->setOffset($i * $perIteration);
            $versions = $list->load();

            foreach ($versions as $version) {
                try {
                    $version->setGenerateStackTrace(false);
                    $version->setStackTrace(null);
                    $version->getDao()->save();
                } catch (\Exception $e) {
                    $this->logger->debug('Unable to cleanup stack trace for version ' . $version->getId() . ', reason: ' . $e->getMessage());
                }
            }
            \Pimcore::collectGarbage();
        }
    }
}
