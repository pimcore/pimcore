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
declare(strict_types=1);

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;

use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Search\Backend\Data;
use Symfony\Component\HttpFoundation\Response;

class Assets extends Elements implements DataProviderInterface
{
    /**
     * @var \Pimcore\Model\Webservice\Service
     */
    protected $service;

    /**
     * @var bool[]
     */
    protected $exportIds = [];

    /**
     * @var array
     */
    protected $config = [];

    public function __construct(\Pimcore\Model\Webservice\Service $service, array $config = null)
    {
        $this->service = $service;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'assets';
    }

    /**
     * @inheritdoc
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
    public function doExportData(Asset $asset)
    {
        $this->exportIds = [];

        $this->fillIds($asset);

        $exportResult = [];

        // Prepare File
        $file = tempnam('/tmp', 'zip');
        $zip = new \ZipArchive();
        $zip->open($file, \ZipArchive::OVERWRITE);

        foreach (array_keys($this->exportIds) as $id) {
            $theAsset = Asset::getById($id);
            $webAsset = $this->service->getAssetFileById($id);

            $resultItem = json_decode(json_encode($webAsset), true);
            unset($resultItem['data']);
            $resultItem = json_encode($resultItem, JSON_PRETTY_PRINT);

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
        $response->headers->set('Content-Length', $size);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $asset->getFilename() . '.zip"');

        return $response;
    }

    /**
     * @param ElementInterface $element
     */
    protected function fillIds(ElementInterface $element)
    {
        $this->exportIds[$element->getId()] = true;
    }

    /**
     * @param int $id
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param int $start
     * @param int $limit
     * @param string $sort
     *
     * @return array
     */
    public function searchData(int $id, string $firstname, string $lastname, string $email, int $start, int $limit, string $sort = null): array
    {
        if (empty($id) && empty($firstname) && empty($lastname) && empty($email)) {
            return ['data' => [], 'success' => true, 'total' => 0];
        }

        $offset = $start;
        $offset = $offset ? $offset : 0;
        $limit = $limit ? $limit : 50;

        $searcherList = new Data\Listing();
        $conditionParts = [];
        $db = \Pimcore\Db::get();

        //id search
        if ($id) {
            $conditionParts[] = '( MATCH (`data`,`properties`) AGAINST (+"' . $id . '" IN BOOLEAN MODE) )';
        }

        // search for firstname, lastname, email
        if ($firstname || $lastname || $email) {
            $firstname = $this->prepareQueryString($firstname);
            $lastname = $this->prepareQueryString($lastname);
            $email = $this->prepareQueryString($email);

            $queryString = ($firstname ? '+"' . $firstname . '"' : '') . ' ' . ($lastname ? '+"' . $lastname . '"' : '') . ' ' . ($email ? '+"' . $email . '"' : '');
            $conditionParts[] = '( MATCH (`data`,`properties`) AGAINST ("' . $db->quote($queryString) . '" IN BOOLEAN MODE) )';
        }

        $db = Db::get();

        $typesPart = '';
        if ($this->config['types']) {
            $typesList = [];
            foreach ($this->config['types'] as $type) {
                $typesList[] = $db->quote($type);
            }
            $typesPart = ' AND type IN (' . implode(',', $typesList) . ')';
        }

        $conditionParts[] = '( maintype = "asset" ' . $typesPart . ')';

        if (count($conditionParts) > 0) {
            $condition = implode(' AND ', $conditionParts);
            $searcherList->setCondition($condition);
        }

        $searcherList->setOffset($offset);
        $searcherList->setLimit($limit);

        $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(['sort' => $sort]);
        if ($sortingSettings['orderKey']) {
            // we need a special mapping for classname as this is stored in subtype column
            $sortMapping = [
                'type' => 'subtype',
            ];

            $sort = $sortingSettings['orderKey'];
            if (array_key_exists($sortingSettings['orderKey'], $sortMapping)) {
                $sort = $sortMapping[$sortingSettings['orderKey']];
            }
            $searcherList->setOrderKey($sort);
        }
        if ($sortingSettings['order']) {
            $searcherList->setOrder($sortingSettings['order']);
        }

        $hits = $searcherList->load();

        $elements = [];
        /** @var Data $hit */
        foreach ($hits as $hit) {
            $element = Service::getElementById($hit->getId()->getType(), $hit->getId()->getId());

            if ($element instanceof Asset) {
                $data = \Pimcore\Model\Asset\Service::gridAssetData($element);
                $data['permissions'] = $element->getUserPermissions();
                $elements[] = $data;
            }
        }

        // only get the real total-count when the limit parameter is given otherwise use the default limit
        if ($limit) {
            $totalMatches = $searcherList->getTotalCount();
        } else {
            $totalMatches = count($elements);
        }

        return ['data' => $elements, 'success' => true, 'total' => $totalMatches];
    }

    /**
     * @inheritdoc
     */
    public function getSortPriority(): int
    {
        return 20;
    }
}
