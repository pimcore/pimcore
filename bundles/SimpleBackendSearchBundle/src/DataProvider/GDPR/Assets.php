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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\DataProvider\GDPR;

use Pimcore\Bundle\AdminBundle\GDPR\DataProvider;
use Pimcore\Bundle\AdminBundle\Service\GridData;
use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\Service;

class Assets extends DataProvider\Assets
{
    public function searchData(int $id, string $firstname, string $lastname, string $email, int $start, int $limit, string $sort = null): array
    {
        if (empty($id) && empty($firstname) && empty($lastname) && empty($email)) {
            return ['data' => [], 'success' => true, 'total' => 0];
        }

        $offset = $start;
        $offset = $offset ?: 0;
        $limit = $limit ?: 50;

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
            $typesPart = ' AND `type` IN (' . implode(',', $typesList) . ')';
        }

        $conditionParts[] = '( maintype = "asset" ' . $typesPart . ')';

        $condition = implode(' AND ', $conditionParts);
        $searcherList->setCondition($condition);

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
        foreach ($hits as $hit) {
            $element = Service::getElementById($hit->getId()->getType(), $hit->getId()->getId());

            if ($element instanceof Asset) {
                $data = [];
                // TODO: remove the class_exists on pimcore 12.0
                if (class_exists(GridData\Asset::class)) {
                    $data = GridData\Asset::getData($element);
                }
                $data['permissions'] = $element->getUserPermissions();
                $elements[] = $data;
            }
        }

        $totalMatches = $searcherList->getTotalCount();

        return ['data' => $elements, 'success' => true, 'total' => $totalMatches];
    }
}
