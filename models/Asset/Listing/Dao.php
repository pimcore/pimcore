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

namespace Pimcore\Model\Asset\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Model;
use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;

/**
 * @internal
 *
 * @property \Pimcore\Model\Asset\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    /**
     * Get the assets from database
     *
     */
    public function load(): array
    {
        $assets = [];

        $queryBuilder = $this->getQueryBuilder('assets.id', 'assets.type');
        $assetsData = $this->db->fetchAllAssociative($queryBuilder->getSQL(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        foreach ($assetsData as $assetData) {
            if ($assetData['type']) {
                if ($asset = Model\Asset::getById($assetData['id'])) {
                    $assets[] = $asset;
                }
            }
        }

        $this->model->setAssets($assets);

        return $assets;
    }

    /**
     * @param string|string[]|null $columns
     *
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from('assets');

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Loads a list of document IDs for the specified parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList(): array
    {
        $queryBuilder = $this->getQueryBuilder('assets.id');
        $assetIds = $this->db->fetchFirstColumn($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        return array_map('intval', $assetIds);
    }

    public function getCount(): int
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getAssets());
        } else {
            $idList = $this->loadIdList();

            return count($idList);
        }
    }

    public function getTotalCount(): int
    {
        $identifierColumn = 'assets.id';
        $queryBuilder = $this->getQueryBuilder();
        $this->prepareQueryBuilderForTotalCount($queryBuilder, $identifierColumn);

        if (
            $this->isQueryBuilderPartInUse($queryBuilder, 'groupBy') ||
            $this->isQueryBuilderPartInUse($queryBuilder, 'having')) {
            if (!$this->isQueryBuilderPartInUse($queryBuilder, 'select')) {
                $queryBuilder->select($identifierColumn);
            }

            return (int)$this->db->fetchOne('SELECT COUNT(*)  FROM (' . $queryBuilder->getSQL() . ') as XYZ');
        }

        return (int)$this->db->fetchOne(
            $queryBuilder->getSql(),
            $queryBuilder->getParameters(),
            $queryBuilder->getParameterTypes()
        );
    }
}
