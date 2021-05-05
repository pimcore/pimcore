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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Search\Backend\Data;

/**
 * @internal
 */
class DataObjects extends Elements implements DataProviderInterface
{
    /**
     * @var array
     */
    protected $exportIds = [];

    /**
     * @var array
     */
    protected $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'dataObjects';
    }

    /**
     * {@inheritdoc}
     */
    public function getJsClassName(): string
    {
        return 'pimcore.settings.gdpr.dataproviders.dataObjects';
    }

    /**
     * Exports data of given object as json including all references that are configured to be included
     *
     * @param AbstractObject $object
     *
     * @return array
     */
    public function doExportData(AbstractObject $object): array
    {
        $this->exportIds = [];

        $this->fillIds($object);

        $exportResult = [];

        if (!empty($this->exportIds['object'])) {
            foreach (array_keys($this->exportIds['object']) as $id) {
                $object = AbstractObject::getById($id);
                $exportResult[] = Exporter::exportObject($object);
            }
        }
        if (!empty($this->exportIds['image'])) {
            foreach (array_keys($this->exportIds['image']) as $id) {
                $theAsset = Asset::getById($id);
                $exportResult[] = Exporter::exportAsset($theAsset);
            }
        }

        return $exportResult;
    }

    protected function fillIds(ElementInterface $element)
    {
        $this->exportIds[$element->getType()][$element->getId()] = true;

        if ($element instanceof Concrete) {
            $subFields = $this->config['classes'][$element->getClass()->getName()]['includedRelations'] ?? [];
            if ($subFields) {
                foreach ($subFields as $field) {
                    $getter = 'get' . ucfirst($field);

                    $subElements = $element->$getter();

                    if ($subElements) {
                        if (!is_array($subElements)) {
                            $subElements = [$subElements];
                        }

                        foreach ($subElements as $subElement) {
                            if ($subElement instanceof ObjectMetadata) {
                                $subElement = $subElement->getObject();
                            } elseif ($subElement instanceof ElementMetadata) {
                                $subElement = $subElement->getElement();
                            }

                            $this->fillIds($subElement);
                        }
                    }
                }
            }
        }
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
     */
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

        $conditionParts[] = '( maintype = "object" AND type IN ("object", "variant") )';

        $classnames = [];
        if ($this->config['classes']) {
            foreach ($this->config['classes'] as $classname => $classConfig) {
                if ($classConfig['include'] == true) {
                    $classnames[] = $classname;
                }
            }
        }

        if (is_array($classnames) and !empty($classnames[0])) {
            $conditionClassnameParts = [];
            foreach ($classnames as $classname) {
                $conditionClassnameParts[] = $db->quote($classname);
            }
            $conditionParts[] = '( subtype IN (' . implode(',', $conditionClassnameParts) . ') )';
        }

        $condition = implode(' AND ', $conditionParts);
        $searcherList->setCondition($condition);

        $searcherList->setOffset($offset);
        $searcherList->setLimit($limit);

        $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(['sort' => $sort]);
        if ($sortingSettings['orderKey']) {
            // we need a special mapping for classname as this is stored in subtype column
            $sortMapping = [
                'classname' => 'subtype',
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
            if ($element instanceof Concrete) {
                $data = \Pimcore\Model\DataObject\Service::gridObjectData($element);
                $data['__gdprIsDeletable'] = $this->config['classes'][$element->getClassName()]['allowDelete'] ?? false;
                $elements[] = $data;
            }
        }

        $totalMatches = $searcherList->getTotalCount();

        return ['data' => $elements, 'success' => true, 'total' => $totalMatches];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortPriority(): int
    {
        return 10;
    }
}
