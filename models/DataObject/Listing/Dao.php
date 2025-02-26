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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
        $queryBuilder = $this->getQueryBuilder();
        $this->prepareQueryBuilderForTotalCount($queryBuilder, $this->getTableName() . '.id');

        $totalCount = $this->db->fetchOne($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        return (int) $totalCount;
    }

    public function getCount(): int
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
