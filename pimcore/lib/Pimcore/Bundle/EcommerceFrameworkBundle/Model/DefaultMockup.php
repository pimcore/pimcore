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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Logger;

class DefaultMockup implements IProduct
{
    protected $id;
    protected $params;
    protected $relations;

    public function __construct($id, $params, $relations)
    {
        $this->id = $id;
        $this->params = $params;

        $this->relations = [];
        if ($relations) {
            foreach ($relations as $relation) {
                $this->relations[$relation['fieldname']][] = ['id' => $relation['dest'], 'type' => $relation['type']];
            }
        }
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getParam($key)
    {
        return $this->params[$key];
    }

    /**
     * @param mixed $params
     *
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param array $relations
     *
     * @return $this
     */
    public function setRelations($relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getRelationAttribute($attributeName)
    {
        $relationObjectArray = [];
        if ($this->relations[$attributeName]) {
            foreach ($this->relations[$attributeName] as $relation) {
                $relationObject = \Pimcore\Model\Element\Service::getElementById($relation['type'], $relation['id']);
                if ($relationObject) {
                    $relationObjectArray[] = $relationObject;
                }
            }
        }

        if (count($relationObjectArray) == 1) {
            return $relationObjectArray[0];
        } elseif (count($relationObjectArray) > 1) {
            return $relationObjectArray;
        } else {
            return null;
        }
    }

    public function __call($method, $args)
    {
        if (substr($method, 0, 3) == 'get') {
            $attributeName = lcfirst(substr($method, 3));
            if (is_array($this->params) && array_key_exists($attributeName, $this->params)) {
                return $this->params[$attributeName];
            }

            if (is_array($this->relations) && array_key_exists($attributeName, $this->relations)) {
                $relation = $this->getRelationAttribute($attributeName);
                if ($relation) {
                    return $relation;
                }
            }
        }
        $msg = "Method $method not in Mockup implemented, delegating to object with id {$this->id}.";
        if (PIMCORE_DEBUG) {
            Logger::warn($msg);
        } else {
            Logger::info($msg);
        }

        $object = $this->getOriginalObject();
        if ($object) {
            return call_user_func_array([$object, $method], $args);
        } else {
            throw new \Exception("Object with {$this->id} not found.");
        }
    }

    public function getOriginalObject()
    {
        Logger::notice("Getting original object {$this->id}.");

        return \Pimcore\Model\DataObject\AbstractObject::getById($this->id);
    }

    /**
     * called by default CommitOrderProcessor to get the product name to store it in the order item
     * should be overwritten in mapped sub classes of product classes
     *
     * @return string
     */
    public function getOSName()
    {
        return $this->__call('getOSName', []);
    }

    /**
     * called by default CommitOrderProcessor to get the product number to store it in the order item
     * should be overwritten in mapped sub classes of product classes
     *
     * @return string
     */
    public function getOSProductNumber()
    {
        return $this->__call('getOSProductNumber', []);
    }
}
