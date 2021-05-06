<?php

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

namespace Pimcore\Model\DataObject\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    /**
     * @return string
     */
    public function getTableName()
    {
        return 'objects';
    }

    /**
     * @param string|string[]|null $columns
     *
     * @return DoctrineQueryBuilder
     *
     * @throws \Exception
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from($this->getTableName());

        // apply joins
        $this->applyJoins($queryBuilder);

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Loads a list of objects for the specicifies parameters, returns an array of DataObject\AbstractObject elements
     *
     * @return array
     */
    public function load()
    {

        // load id's
        $list = $this->loadIdList();

        $objects = [];
        foreach ($list as $o_id) {
            if ($object = DataObject::getById($o_id)) {
                $objects[] = $object;
            }
        }

        $this->model->setObjects($objects);

        return $objects;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $queryBuilder = $this->getQueryBuilder();
        $this->prepareQueryBuilderForTotalCount($queryBuilder);

        $totalCount = $this->db->fetchOne((string) $queryBuilder, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return (int) $totalCount;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getObjects());
        } else {
            $idList = $this->loadIdList();

            return count($idList);
        }
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList()
    {
        $queryBuilder = $this->getQueryBuilder([sprintf('%s as o_id', $this->getTableName() . '.o_id'), sprintf('%s as o_type', $this->getTableName() . '.o_type')]);
        $objectIds = $this->db->fetchCol((string) $queryBuilder, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return array_map('intval', $objectIds);
    }

    /**
     * @param DoctrineQueryBuilder $queryBuilder
     *
     * @return $this
     */
    protected function applyJoins(DoctrineQueryBuilder $queryBuilder)
    {
        return $this;
    }
}
