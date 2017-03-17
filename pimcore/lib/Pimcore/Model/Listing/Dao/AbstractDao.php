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

namespace Pimcore\Model\Listing\Dao;

use Pimcore\Db\ZendCompatibility\Expression;
use Pimcore\Db\ZendCompatibility\QueryBuilder;
use Pimcore\Model;

abstract class AbstractDao extends Model\Dao\AbstractDao
{

    /**
     * @var Model\Object\Listing
     */
    protected $model;


    /**
     * @return string
     */
    protected function getOrder()
    {
        $orderKey = $this->model->getOrderKey();
        $order = $this->model->getOrder();

        if (!empty($order) || !empty($orderKey)) {
            $c = 0;
            $lastOrder = $order[0];
            $parts = [];

            if (is_array($orderKey)) {
                foreach ($orderKey as $key) {
                    if ($order[$c]) {
                        $lastOrder = $order[$c];
                    }

                    $parts[] = $key . " " . $lastOrder;

                    $c++;
                }
            }

            if (!empty($parts)) {
                return " ORDER BY " . implode(", ", $parts);
            }
        }

        return "";
    }

    /**
     * @return string
     */
    protected function getGroupBy()
    {
        if ($this->model->getGroupBy()) {
            return " GROUP BY " . $this->model->getGroupBy();
        }

        return "";
    }

    /**
     * @return string
     */
    protected function getOffsetLimit()
    {
        if ($limit = $this->model->getLimit() and $offset = $this->model->getOffset()) {
            return " LIMIT " . $offset . "," . $limit;
        }

        if ($limit = $this->model->getLimit()) {
            return " LIMIT " . $limit;
        }

        return "";
    }

    /**
     * @return string
     */
    protected function getCondition()
    {
        if ($cond = $this->model->getCondition()) {
            return " WHERE " . $cond . " ";
        }

        return "";
    }


    /**
     * @param QueryBuilder $select
     *
     * @return $this
     */
    protected function addOrder(QueryBuilder $select)
    {
        $orderKey = $this->model->getOrderKey();
        $order = $this->model->getOrder();

        if (!empty($order) || !empty($orderKey)) {
            $c = 0;
            $lastOrder = $order[0];
            $parts = [];

            if (is_array($orderKey)) {
                foreach ($orderKey as $key) {
                    if ($order[$c]) {
                        $lastOrder = $order[$c];
                    }

                    $parts[] = $key . " " . $lastOrder;

                    $c++;
                }
            }

            if (!empty($parts)) {
                $select->order(new Expression(implode(", ", $parts)));
            }
        }
    }


    /**
     * @param QueryBuilder $select
     * @return $this
     * @internal param $QueryBuilder
     *
     */
    protected function addGroupBy(QueryBuilder $select)
    {
        $groupBy = $this->model->getGroupBy();
        if ($groupBy) {
            $select->group($groupBy);
        }

        return $this;
    }


    /**
     * @param QueryBuilder $select
     *
     * @return $this
     */
    protected function addLimit(QueryBuilder $select)
    {
        $select->limit($this->model->getLimit(), $this->model->getOffset());

        return $this;
    }


    /**
     * @param QueryBuilder $select
     *
     * @return $this
     */
    protected function addConditions(QueryBuilder $select)
    {
        $condition = $this->model->getCondition();

        if ($condition) {
            $select->where($condition);
        }

        return $this;
    }
}
