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

namespace Pimcore\Model\Listing;

use Pimcore\Model\AbstractModel;
use Pimcore\Resource;

abstract class AbstractListing extends AbstractModel {

    /**
     * @var string|array
     */
    protected $order;

    /**
     * @var string|array
     */
    protected $orderKey;

    /**
     * @var integer
     */
    protected $limit;

    /**
     * @var integer
     */
    protected $offset;

    /**
     * @var string
     */
    protected $condition;

    /**
     * @var array
     */
    protected $conditionVariables = array();

    /**
     * @var string
     */
    protected $groupBy;

    /**
     * @var array
     */
    protected $validOrders = array(
        "ASC",
        "DESC"
    );

    /**
     * @var array
     */
    protected $conditionParams = array();

    /**
     * @abstract
     * @param  $key
     * @return bool
     */
    abstract public function isValidOrderKey($key);

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset() {
        return $this->offset;
    }

    /**
     * @return array|string
     */
    public function getOrder() {
        return $this->order;
    }

    /**
     * @param  $limit
     * @return void
     */
    public function setLimit($limit) {
        if (intval($limit) > 0) {
            $this->limit = intval($limit);
        }
        return $this;
    }

    /**
     * @param  $offset
     * @return void
     */
    public function setOffset($offset) {
        if (intval($offset) > 0) {
            $this->offset = intval($offset);
        }
        return $this;
    }

    /**
     * @param  $order
     * @return void
     */
    public function setOrder($order) {

        $this->order = array();

        if (is_string($order) && !empty($order)) {
            $order = strtoupper($order);
            if (in_array($order, $this->validOrders)) {
                $this->order[] = $order;
            }
        }
        else if (is_array($order) && !empty($order)) {
            $this->order = array();
            foreach ($order as $o) {
                $o = strtoupper($o);
                if (in_array($o, $this->validOrders)) {
                    $this->order[] = $o;
                }
            }
        }
        return $this;
    }

    /**
     * @return array|string
     */
    public function getOrderKey() {
        return $this->orderKey;
    }

    /**
     * @param string|array $orderKey
     * @param bool $quote
     * @return void
     */
    public function setOrderKey($orderKey, $quote = true) {

        $this->orderKey = array();

        if (is_string($orderKey) && !empty($orderKey)) {
            if ($this->isValidOrderKey($orderKey)) {
                $this->orderKey[] = $orderKey;
            }
        }
        else if (is_array($orderKey) && !empty($orderKey)) {
            $this->orderKey = array();
            foreach ($orderKey as $o) {
                if ($this->isValidOrderKey($o)) {
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
        return $this;
    }

    /**
     * @param $key
     * @param null $value
     * @param string $concatenator
     * @return $this
     */
    public function addConditionParam($key, $value = null, $concatenator = 'AND'){
        if(!is_null($value)){
            $this->conditionParams[$key] = array('value' => $value,'concatenator' => $concatenator);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getConditionParams(){
        return $this->conditionParams;
    }

    /**
     * @return $this
     */
    public function resetConditionParams(){
        $this->conditionParams = array();
        return $this;
    }

    /**
     * @return string
     */
    public function getCondition() {
        $conditionString = '';
        $conditionPrams = $this->getConditionParams();

        if(!empty($conditionPrams)){

            $params = array();
            $i = 0;
            foreach($conditionPrams as $key => $value){
                if(!$this->condition && $i == 0){
                    $conditionString .= $key . ' ';
                }else{
                    $conditionString .= ' ' . $value['concatenator'] . ' ' . $key . ' ';
                }

                /* check value because of calls like
                 *
                 * if($key = $this->_getParam('key')){
                 *   $list->addConditionParam(" `key` LIKE " . Pimcore_Resource::get()->quote("%" . $key . "%"),'');
                 * }
                 */
                if($value['value'] != ''){
                    $params[] = $value['value'];
                }
                $i++;
            }
            $this->setConditionVariables($params);
        }

        return $this->condition . $conditionString;
    }

    /**
     * @param string $condition
     * @return void
     */
    public function setCondition($condition, $conditionVariables = null) {
        $this->condition = $condition;

        // statement variables
        if(is_array($conditionVariables)) {
            $this->setConditionVariables($conditionVariables);
        } else if ($conditionVariables !== null) {
            $this->setConditionVariables(array($conditionVariables));
        }
        return $this;
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
     * @param $groupBy
     * @param bool $qoute
     * @return $this
     */
    public function setGroupBy($groupBy, $qoute = true) {
        if($groupBy) {
            $this->groupBy = $groupBy;
    
            if ($qoute) {
                $this->groupBy = "`" . $this->groupBy . "`";
            }
        }
        return $this;
    }

    /**
     * @param $validOrders
     * @return $this
     */
    public function setValidOrders($validOrders) {
        $this->validOrders = $validOrders;
        return $this;
    }

    /**
     * @param  $value
     * @return void
     */
    public function quote ($value, $type = null) {
        $db = Resource::get();
        return $db->quote($value, $type);
    }

    /**
     * @param $conditionVariables
     * @return $this
     */
    public function setConditionVariables($conditionVariables)
    {
        $this->conditionVariables = $conditionVariables;
        return $this;
    }

    /**
     * @return array
     */
    public function getConditionVariables()
    {
        return $this->conditionVariables;
    }
}
