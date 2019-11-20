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

use Pimcore\Image\ImageOptimizerInterface;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Tool\TmpStore;
use Psr\Log\LoggerInterface;

final class ImageOptimizeTask implements TaskInterface
{
    /**
     * @var ImageOptimizerInterface
     */
    private $optimizer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ImageOptimizerInterface $optimizer
     * @param LoggerInterface         $logger
     */
    public function __construct(ImageOptimizerInterface $optimizer, LoggerInterface $logger)
    {
        $this->optimizer = $optimizer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $ids = TmpStore::getIdsByTag('image-optimize-queue');

        // id = path of image relative to PIMCORE_TEMPORARY_DIRECTORY
        foreach ($ids as $id) {
            $tmpStore = TmpStore::get($id);

            if ($tmpStore && $tmpStore->getData()) {
                $file = PIMCORE_TEMPORARY_DIRECTORY.'/'.$tmpStore->getData();
                if (file_exists($file)) {
                    $originalFilesize = filesize($file);
                    $this->optimizer->optimizeImage($file);

                    $this->logger->debug('Optimized image: '.$file.' saved '.formatBytes($originalFilesize - filesize($file)));
                } else {
                    $this->logger->debug('Skip optimizing of '.$file." because it doesn't exist anymore");
                }
            }

            TmpStore::delete($id);
        }
    }
}
