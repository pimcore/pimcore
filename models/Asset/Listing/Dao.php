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

namespace Pimcore\Model\Asset\Listing;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Db\ZendCompatibility\Expression;
use Pimcore\Db\ZendCompatibility\QueryBuilder as ZendCompatibilityQueryBuilder;
use Pimcore\Model;
use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;

/**
 * @property \Pimcore\Model\Asset\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    /**
     * @deprecated
     *
     * @var \Closure
     */
    protected $onCreateQueryCallback;

    /**
     * Get the assets from database
     *
     * @return array
     */
    public function load()
    {
        $assets = [];

        $queryBuilder = $this->getQueryBuilderCompatibility(['id', 'type']);
        $assetsData = $this->db->fetchAll((string) $queryBuilder, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

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
     * @deprecated
     *
     * @param array|string|Expression $columns
     *
     * @return ZendCompatibilityQueryBuilder
     */
    public function getQuery($columns = '*')
    {
        @trigger_error(sprintf('Using %s is deprecated and will be removed in Pimcore 10, please use getQueryBuilder() instead', __METHOD__), E_USER_DEPRECATED);

        $select = $this->db->select();
        $select->from(
            [ 'assets' ],
            $columns
        );
        $this->addConditions($select);
        $this->addOrder($select);
        $this->addLimit($select);
        $this->addGroupBy($select);

        if ($this->onCreateQueryCallback) {
            $closure = $this->onCreateQueryCallback;
            $closure($select);
        }

        return $select;
    }

    /**
     * @param string|string[]|null $columns
     *
     * @return DoctrineQueryBuilder
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
    public function loadIdList()
    {
        $queryBuilder = $this->getQueryBuilderCompatibility(['id']);
        $assetIds = $this->db->fetchCol((string) $queryBuilder, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return array_map('intval', $assetIds);
    }

    /**
     * @return int
     */
    public function getCount()
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getAssets());
        } else {
            $idList = $this->loadIdList();

            return count($idList);
        }
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $queryBuilder = $this->getQueryBuilderCompatibility();
        $this->prepareQueryBuilderForTotalCount($queryBuilder);

        $amount = (int) $this->db->fetchOne((string) $queryBuilder, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return $amount;
    }

    /**
     * @deprecated
     *
     * @param callable $callback
     */
    public function onCreateQuery(callable $callback)
    {
        @trigger_error(sprintf('Using %s is deprecated and will be removed in Pimcore 10, please use onCreateQueryBuilder() instead', __METHOD__), E_USER_DEPRECATED);
        $this->onCreateQueryCallback = $callback;
    }
}
