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

namespace Pimcore\Model\Listing\Dao;

use Pimcore\Model;

abstract class AbstractDao extends Model\Dao\AbstractDao
{
    /**
     * @var Model\DataObject\Listing
     */
    protected $model;

    /**
     *
     * @return array
     */
    abstract public function load();

    /**
     * @return int
     */
    abstract public function getTotalCount();

    /**
     * @return string
     */
    protected function getOrder()
    {
        $orderKey = $this->model->getOrderKey();
        $order = $this->model->getOrder();

        if (!empty($order) || !empty($orderKey)) {
            $c = 0;
            $lastOrder = $order[0] ?? null;
            $parts = [];

            if (is_array($orderKey)) {
                foreach ($orderKey as $key) {
                    if (isset($order[$c])) {
                        $lastOrder = $order[$c];
                    }

                    $parts[] = $key . ' ' . $lastOrder;

                    $c++;
                }
            }

            if (!empty($parts)) {
                return ' ORDER BY ' . implode(', ', $parts);
            }
        }

        return '';
    }

    /**
     * @return string
     */
    protected function getGroupBy()
    {
        if ($this->model->getGroupBy()) {
            return ' GROUP BY ' . $this->model->getGroupBy();
        }

        return '';
    }

    /**
     * @return string
     */
    protected function getOffsetLimit()
    {
        if ($limit = $this->model->getLimit() and $offset = $this->model->getOffset()) {
            return ' LIMIT ' . $offset . ',' . $limit;
        }

        if ($limit = $this->model->getLimit()) {
            return ' LIMIT ' . $limit;
        }

        return '';
    }

    /**
     * @return string
     */
    protected function getCondition()
    {
        if ($cond = $this->model->getCondition()) {
            return ' WHERE ' . $cond . ' ';
        }

        return '';
    }
}
