<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Exception;
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

    public function getTableName(): string
    {
        return 'objects';
    }

    /**
     * @param string|string[]|null $columns
     *
     * @throws Exception
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
     */
    public function load(): array
    {
        // load id's
        $list = $this->loadIdList();

        $objects = [];
        foreach ($list as $id) {
            if ($object = DataObject::getById($id)) {
                $objects[] = $object;
            }
        }

        $this->model->setObjects($objects);

        return $objects;
    }

    public function getTotalCount(): int
    {
        $identifierColumn = $this->getTableName() . '.id';
        $queryBuilder = $this->getQueryBuilder();
        $this->prepareQueryBuilderForTotalCount($queryBuilder, $identifierColumn);

        if (
            $this->isQueryBuilderPartInUse($queryBuilder, 'groupBy') ||
            $this->isQueryBuilderPartInUse($queryBuilder, 'having')
        ) {
            return (int)$this->db->fetchOne('SELECT COUNT(*)  FROM (' . $queryBuilder->getSQL() . ') as XYZ');
        }

        return (int)$this->db->fetchOne(
            $queryBuilder->getSql(),
            $queryBuilder->getParameters(),
            $queryBuilder->getParameterTypes()
        );
    }

    public function getCount(): int
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getObjects());
        }

        $idList = $this->loadIdList();

        return count($idList);
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList(): array
    {
        $queryBuilder = $this->getQueryBuilder(sprintf('%s as id', $this->getTableName() . '.id'), sprintf('%s as `type`', $this->getTableName() . '.type'));
        $objectIds = $this->db->fetchFirstColumn($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        return array_map('intval', $objectIds);
    }

    protected function applyJoins(DoctrineQueryBuilder $queryBuilder): static
    {
        return $this;
    }
}
