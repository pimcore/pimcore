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
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;

final class AssetDocumentConversionTask implements TaskInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AssetDocumentConversionTask constructor.
     *
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
        $ids = TmpStore::getIdsByTag('asset-document-conversion');

        // id = path of image relative to PIMCORE_TEMPORARY_DIRECTORY
        foreach ($ids as $id) {
            $item = TmpStore::get($id);
            $asset = Asset::getById($item->getData());

            try {
                if ($asset instanceof Asset\Document) {
                    $this->logger->debug(sprintf('Processing document with ID %s | Path: %s', $asset->getId(), $asset->getFullPath()));
                    $asset->processPageCount();
                    Version::disable();
                    $asset->save();
                    Version::enable();
                }
            } catch (\Throwable $e) {
                $this->logger->debug(sprintf('Processing document with ID %s failed', $asset->getId()));
                $this->logger->debug($e);
            }

            TmpStore::delete($id);
        }
    }
}
