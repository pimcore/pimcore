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

use Doctrine\DBAL\Exception;
use Pimcore\Bundle\AdminBundle\Helper\QueryParams;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
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
     * @return array
     *
     * @throws Exception
     */
    public function searchData(int $id, string $firstname, string $lastname, string $email, int $start, int $limit, string $sort = null): array
    {
        if (empty($id) && empty($firstname) && empty($lastname) && empty($email)) {
            return ['data' => [], 'success' => true, 'total' => 0];
        }

        //TODO: add orWhere only if field if set!
        $db = \Pimcore\Db::get();
        $queryBuilder = $db->createQueryBuilder();
        $query = $queryBuilder
            ->select('assets.id')
            ->from('assets')
            ->leftJoin('assets', 'assets_metadata', 'metadata', 'assets.id = metadata.cid')
            ->where('assets.id = :id')
            ->setParameter('id', $id)
            ->setFirstResult($start)
            ->setMaxResults($limit);

        if (!empty($firstname)) {
            $query
                ->orWhere(
                    $queryBuilder->expr()->like('metadata.data', ':firstname')
                )
                ->setParameter('firstname', ('%'.$firstname.'%'));
        }

        if (!empty($lastname)) {
            $query
                ->orWhere(
                    $queryBuilder->expr()->like('metadata.data', ':lastname')
                )
                ->setParameter('lastname', ('%'.$lastname.'%'));
        }

        if (!empty($email)) {
            $query
                ->orWhere(
                    $queryBuilder->expr()->like('metadata.data', ':email')
                )
                ->setParameter('email', ('%'.$email.'%'));
        }

        $sortingSettings = QueryParams::extractSortingSettings(['sort' => $sort]);
        if ($sortingSettings['orderKey']) {
            // we need a special mapping for classname as this is stored in subtype column
            $sortMapping = [
                'type' => 'subtype',
            ];

            $sort = $sortingSettings['orderKey'];
            if (array_key_exists($sortingSettings['orderKey'], $sortMapping)) {
                $sort = $sortMapping[$sortingSettings['orderKey']];
            }

            $order = $sortingSettings['order'] ?? null;

            $query->orderBy($sort, $order);
        }

        $query = $query->executeQuery();

        $elements = [];
        if ($query->rowCount() > 0) {
            foreach ($query->fetchAllAssociative() as $hit) {
                $element = Element\Service::getElementById('asset', $hit['id']);

                if ($element instanceof Asset) {
                    $data = Asset\Service::gridAssetData($element);
                    $data['permissions'] = $element->getUserPermissions();
                    $elements[] = $data;
                }
            }
        }

        return ['data' => $elements, 'success' => true, 'total' => $query->rowCount()];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortPriority(): int
    {
        return 20;
    }
}
