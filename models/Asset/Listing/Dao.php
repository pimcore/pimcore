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
 * @category   Pimcore
 * @package    Asset
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\Listing;

use Pimcore\Db\ZendCompatibility\Expression;
use Pimcore\Db\ZendCompatibility\QueryBuilder;
use Pimcore\Model;

/**
 * @property \Pimcore\Model\Asset\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
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

        $select = $this->getQuery(['id', 'type']);
        $assetsData = $this->db->fetchAll($select, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

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
     * @return \Pimcore\Db\ZendCompatibility\QueryBuilder
     */
    public function getQuery($columns = '*')
    {
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
     * Loads a list of document IDs for the specified parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList()
    {
        $select = $this->getQuery(['id']);
        $assetIds = $this->db->fetchCol($select, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

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
        $select = $this->getQuery([new Expression('COUNT(*)')]);
        $select->reset(QueryBuilder::LIMIT_COUNT);
        $select->reset(QueryBuilder::LIMIT_OFFSET);
        $select->reset(QueryBuilder::ORDER);

        $amount = (int) $this->db->fetchOne($select, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return $amount;
    }

    /**
     * @param callable $callback
     */
    public function onCreateQuery(callable $callback)
    {
        $this->onCreateQueryCallback = $callback;
    }
}
