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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Pimcore_Model_List_Abstract extends Pimcore_Model_Abstract {

    protected $order;
    protected $orderKey;
    protected $limit;
    protected $offset;
    protected $condition;
    protected $groupBy;
    protected $validOrders = array(
        "ASC",
        "DESC"
    );

    abstract public function isValidOrderKey($key);

    public function getLimit() {
        return $this->limit;
    }

    public function getOffset() {
        return $this->offset;
    }

    public function getOrder() {
        return $this->order;
    }

    public function setLimit($limit) {
        if (intval($limit) > 0) {
            $this->limit = intval($limit);
        }
    }

    public function setOffset($offset) {
        if (intval($offset) > 0) {
            $this->offset = intval($offset);
        }
    }

    public function setOrder($order) {

        $this->order = array();

        if (is_string($order)) {
            $order = strtoupper($order);
            if (in_array($order, $this->validOrders)) {
                $this->order[] = $order;
            }
        }
        else if (is_array($order)) {
            $this->order = array();
            foreach ($order as $o) {
                $o = strtoupper($o);
                if (in_array($o, $this->validOrders)) {
                    $this->order[] = $o;
                }
            }
        }
    }

    public function getOrderKey() {
        return $this->orderKey;
    }

    public function setOrderKey($orderKey, $quote = true) {

        $this->orderKey = array();

        if (is_string($orderKey)) {
            if ($this->isValidOrderKey($orderKey)) {
                $this->orderKey[] = $orderKey;
            }
        }
        else if (is_array($orderKey)) {
            $this->orderKey = array();
            foreach ($orderKey as $o) {
                if ($this->isValidOrderKey($orderKey)) {
                    $this->orderKey[] = $o;
                }
            }
        }

        if ($quote) {
            foreach ($this->orderKey as $key) {
                $tmpKeys[] = "`" . $key . "`";
            }
            $this->orderKey = $tmpKeys;
        }
    }

    public function getCondition() {
        return $this->condition;
    }

    public function setCondition($condition) {
        $this->condition = $condition;
    }

    /**
     * @return string
     */
    public function getGroupBy() {
        return $this->groupBy;
    }

    /**
     * @return array
     */
    public function getValidOrders() {
        return $this->validOrders;
    }

    /**
     * @param string $groupBy
     */
    public function setGroupBy($groupBy, $qoute = true) {
        if($groupBy) {
            $this->groupBy = $groupBy;
    
            if ($qoute) {
                $this->groupBy = "`" . $this->groupBy . "`";
            }
        }
    }

    /**
     * @param array $validOrders
     */
    public function setValidOrders($validOrders) {
        $this->validOrders = $validOrders;
    }
}
