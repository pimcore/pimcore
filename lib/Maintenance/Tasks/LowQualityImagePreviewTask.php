<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Maintenance\Tasks;

use Exception;
use Pimcore;
use Pimcore\Config;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Asset;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\Lock\LockFactory;

/**
 * @internal
 */
class LowQualityImagePreviewTask implements TaskInterface
{
    /**
     * The lock key already changes with the night, so the TTL only has to be long enough for the
     * lock to outlive the night it was acquired in.
     */
    private const LOCK_TTL = 86400;

    public function __construct(
        private LoggerInterface $logger,
        private LockFactory $lockFactory,
        private ClockInterface $clock = new NativeClock(),
    ) {
    }

    public function execute(): void
    {
        $isLowQualityPreviewEnabled = Config::getSystemConfiguration('assets')['image']['low_quality_image_preview']['enabled'];
        if (!$isLowQualityPreviewEnabled) {
            return;
        }

        $now = $this->clock->now();
        // execution should be only sometime between 0:00 and 4:59 -> less load expected
        if ((int) $now->format('H') > 4) {
            $this->logger->debug('Skip low quality image preview execution, runs only between 0:00 and 4:59');

            return;
        }

        // one run per night: the lock is keyed by the night and is never released, neither explicitly
        // nor when the lock object is destroyed, so it outlives the worker that acquired it. A fresh
        // lock object per run makes sure a worker that keeps this task instance alive cannot simply
        // re-acquire its own lock on the next maintenance cycle either.
        $lock = $this->lockFactory->createLock(self::class . ':' . $now->format('Y-m-d'), self::LOCK_TTL, false);
        if (!$lock->acquire()) {
            $this->logger->debug('Skip low quality image preview execution, was already done tonight');

            return;
        }

        $this->logger->debug('Execute low quality image preview generation');

        $listing = new Asset\Listing();
        $listing->setCondition("`type` = 'image'");
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
                if (!$image->getLowQualityPreviewDataUri()) {
                    try {
                        $this->logger->debug(sprintf('Generate LQIP for asset %s', $image->getId()));
                        $image->generateLowQualityPreview();
                    } catch (Exception $e) {
                        $this->logger->error((string) $e);
                    }
                }
            }
            Pimcore::collectGarbage();
            Pimcore::deleteTemporaryFiles();
        }
    }
}
