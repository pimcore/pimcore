<?php

namespace Pimcore\Bundle\SimpleBackendSearchBundle\DataProvider\GDPR;

use Pimcore\Model\Element\Service;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Bundle\AdminBundle\GDPR\DataProvider;
use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;

class DataObject extends DataProvider\DataObjects
{
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

        $conditionParts[] = '( maintype = "object" AND `type` IN ("object", "variant") )';

        $classnames = [];
        if ($this->config['classes']) {
            foreach ($this->config['classes'] as $classname => $classConfig) {
                if ($classConfig['include'] == true) {
                    $classnames[] = $classname;
                }
            }
        }

        if (is_array($classnames) && !empty($classnames[0])) {
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
}
