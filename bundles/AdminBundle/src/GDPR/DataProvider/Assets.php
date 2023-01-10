<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;

use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Search\Backend\Data;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class Assets extends Elements implements DataProviderInterface
{
    /**
     * @var bool[]
     */
    protected array $exportIds = [];

    protected ?array $config = [];

    public function __construct(array $config = null)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'assets';
    }

    /**
     * {@inheritdoc}
     */
    public function getJsClassName(): string
    {
        return 'pimcore.settings.gdpr.dataproviders.assets';
    }

    /**
     * Exports data of given asset as json
     *
     * @param Asset $asset
     *
     * @return Response
     */
    public function doExportData(Asset $asset): Response
    {
        $this->exportIds = [];
        $this->exportIds[$asset->getId()] = true;

        // Prepare File
        $file = tempnam('/tmp', 'zip');
        $zip = new \ZipArchive();
        $zip->open($file, \ZipArchive::OVERWRITE);

        foreach (array_keys($this->exportIds) as $id) {
            $theAsset = Asset::getById($id);

            $resultItem = Exporter::exportAsset($theAsset);
            $resultItem = json_encode($resultItem);

            $zip->addFromString($asset->getFilename() . '.txt', $resultItem);

            if (!$theAsset instanceof Asset\Folder) {
                $zip->addFromString($theAsset->getFilename(), $theAsset->getData());
            }
        }

        $zip->close();

        $size = filesize($file);
        $content = file_get_contents($file);
        unlink($file);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', (string) $size);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $asset->getFilename() . '.zip"');

        return $response;
    }

    /**
     * @param int $id
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param int $start
     * @param int $limit
     * @param string|null $sort
     *
     * TODO: redefine - implement a "very simple" search
     *
     * @return array
     */
    public function searchData(int $id, string $firstname, string $lastname, string $email, int $start, int $limit, string $sort = null): array
    {
        //TODO: implement

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortPriority(): int
    {
        return 20;
    }
}
