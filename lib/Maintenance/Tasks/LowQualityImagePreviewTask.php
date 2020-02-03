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
use Pimcore\Model\Asset;
use Pimcore\Model\Tool\Lock;
use Psr\Log\LoggerInterface;

final class LowQualityImagePreviewTask implements TaskInterface
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
        $lockId = self::class;
        if (!Lock::isLocked($lockId, 86400 * 2) && date('H') <= 4) {
            // execution should be only sometime between 0:00 and 4:59 -> less load expected
            Lock::lock($lockId);
            $this->logger->debug('Execute low quality image preview generation');

            $listing = new Asset\Listing();
            $listing->setCondition("type = 'image'");
            $listing->setOrderKey('id');
            $listing->setOrder('DESC');

            $total = $listing->getTotalCount();
            $perLoop = 10;

            for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
                $listing->setLimit($perLoop);
                $listing->setOffset($i * $perLoop);

                /** @var Asset\Image[] $images */
                $images = $listing->load();
                foreach ($images as $image) {
                    if (!file_exists($image->getLowQualityPreviewFileSystemPath())) {
                        try {
                            $this->logger->debug(sprintf('Generate LQIP for asset %s', $image->getId()));
                            $image->generateLowQualityPreview();
                        } catch (\Exception $e) {
                            $this->logger->error($e);
                        }
                    }
                }
                \Pimcore::collectGarbage();
            }
        } else {
            $this->logger->debug('Skip low quality image preview execution, was done within the last 24 hours');
        }
    }
}
