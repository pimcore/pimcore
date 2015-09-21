<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Listing\Resource;

use Pimcore\Model;

abstract class AbstractResource extends Model\Resource\AbstractResource {

    /**
     * @var Model\Object\Listing
     */
    protected $model;


    /**
     * @return string
     */
    protected function getOrder() {

        $orderKey = $this->model->getOrderKey();
        $order = $this->model->getOrder();

        if (!empty($order) || !empty($orderKey)) {
            $c = 0;
            $lastOrder = $order[0];
            $parts = array();

            if(is_array($orderKey)) {
                foreach ($orderKey as $key) {
                    if ($order[$c]) {
                        $lastOrder = $order[$c];
                    }

                    $parts[] = $key . " " . $lastOrder;

                    $c++;
                }
            }

            if(!empty($parts)) {
                return " ORDER BY " . implode(", ", $parts);
            }
        }

        return "";
    }

    /**
     * @return string
     */
    protected function getGroupBy() {
        if ($this->model->getGroupBy()) {
            return " GROUP BY " . $this->model->getGroupBy();
        }
        return "";
    }

    /**
     * @return string
     */
    protected function getOffsetLimit() {
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
    protected function getCondition() {
        if ($cond = $this->model->getCondition()) {
            return " WHERE " . $cond . " ";
        }
        return "";
    }


    /**
     * @param \Zend_DB_Select $select
     *
     * @return $this
     */
    protected function addOrder(\Zend_DB_Select $select)
    {
        $orderKey = $this->model->getOrderKey();
        $order = $this->model->getOrder();

        if (!empty($order) || !empty($orderKey)) {
            $c = 0;
            $lastOrder = $order[0];
            $parts = array();

            if(is_array($orderKey)) {
                foreach ($orderKey as $key) {
                    if ($order[$c]) {
                        $lastOrder = $order[$c];
                    }

                    $parts[] = $key . " " . $lastOrder;

                    $c++;
                }
            }

            if(!empty($parts)) {

                $select->order( new \Zend_Db_Expr(implode(", ", $parts)) );
            }
        }
    }


    /**
     * @param \Zend_DB_Select $select
     *
     * @return $this
     */
    protected function addGroupBy(\Zend_DB_Select $select)
    {
        $groupBy = $this->model->getGroupBy();
        if($groupBy)
        {
            $select->group( $groupBy );
        }

        return $this;
    }


    /**
     * @param \Zend_DB_Select $select
     *
     * @return $this
     */
    protected function addLimit(\Zend_DB_Select $select)
    {
        $select->limit( $this->model->getLimit(), $this->model->getOffset() );

        return $this;
    }


    /**
     * @param \Zend_DB_Select $select
     *
     * @return $this
     */
    protected function addConditions(\Zend_DB_Select $select)
    {
        $condition = $this->model->getCondition();

        if($condition)
        {
            $select->where( $condition );
        }

        return $this;
    }
}
