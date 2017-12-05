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

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultihrefMetadata;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Search\Backend\Data;

class DataObjects implements DataProviderInterface
{
    /**
     * @var \Pimcore\Model\Webservice\Service
     */
    protected $service;

    /**
     * @var string[]
     */
    protected $exportIds = [];

    /**
     * @var array
     */
    protected $config = [];

    public function __construct(\Pimcore\Model\Webservice\Service $service, array $config)
    {
        $this->service = $service;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'dataObjects';
    }

    /**
     * @inheritdoc
     */
    public function getJsClassName(): string
    {
        return 'pimcore.settings.gdpr.dataproviders.dataObjects';
    }

    /**
     * @inheritdoc
     */
    public function getSortPriority(): int
    {
        return 10;
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

        foreach (array_keys($this->exportIds['object']) as $id) {
            $exportResult[] = $this->service->getObjectConcreteById($id);
        }
        if ($this->exportIds['image']) {
            foreach (array_keys($this->exportIds['image']) as $id) {
                $exportResult[] = $this->service->getAssetFileById($id);
            }
        }

        return $exportResult;
    }

    protected function fillIds(ElementInterface $element)
    {
        $this->exportIds[$element->getType()][$element->getId()] = true;

        if ($element instanceof Concrete) {
            $subFields = $this->config['classes'][$element->getClass()->getName()]['includedRelations'];
            if ($subFields) {
                foreach ($subFields as $field) {
                    $getter = 'get' . ucfirst($field);

                    $subElements = $element->$getter();

                    if ($subElements) {
                        if (!is_array($subElements)) {
                            $subElements = [$subElements];
                        }

                        foreach ($subElements as $subElement) {
                            if ($subElement instanceof ObjectMetadata || $subElement instanceof MultihrefMetadata) {
                                $subElement = $subElement->getObject();
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
                'classname' => 'subtype'
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

        $elements=[];
        foreach ($hits as $hit) {
            $element = Service::getElementById($hit->getId()->getType(), $hit->getId()->getId());
            if ($element instanceof Concrete) {
                $data = \Pimcore\Model\DataObject\Service::gridObjectData($element);
                $data['__gdprIsDeletable'] = $this->config['classes'][$element->getClassName()]['allowDelete'];
            }

            $elements[] = $data;
        }

        // only get the real total-count when the limit parameter is given otherwise use the default limit
        if ($limit) {
            $totalMatches = $searcherList->getTotalCount();
        } else {
            $totalMatches = count($elements);
        }

        return ['data' => $elements, 'success' => true, 'total' => $totalMatches];
    }

    protected function prepareQueryString($query): string
    {
        if ($query == '*') {
            $query = '';
        }

        $query = str_replace('%', '*', $query);
        $query = str_replace('@', '#', $query);
        $query = preg_replace("@([^ ])\-@", '$1 ', $query);

        return $query;
    }
}
