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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\GridColumnConfig\Operator;

use Pimcore\Db;
use Pimcore\Model\Element\Service;

class RequiredBy extends AbstractOperator
{
    protected $elementType;

    protected $onlyCount;

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);
        $this->elementType = $config->elementType;
        $this->onlyCount = $config->onlyCount;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->isArrayType = true;

        $db = Db::get();
        $typeCondition = '';
        switch ($this->getElementType()) {
            case 'document': $typeCondition = " AND sourcetype = 'document'";
                break;
            case 'asset': $typeCondition = " AND sourcetype = 'asset'";
                break;
            case 'object': $typeCondition = " AND sourcetype = 'object'";
                break;
        }

        if ($this->getOnlyCount()) {
            $query = 'select count(*) from dependencies where targetid = ' . $element->getId() . $typeCondition;
            $count = $db->fetchOne($query);
            $result->value = $count;
        } else {
            $resultList = [];
            $query = 'select * from dependencies where targetid = ' . $element->getId() . $typeCondition;
            $dependencies = $db->fetchAll($query);
            foreach ($dependencies as $dependency) {
                $sourceType = $dependency['sourcetype'];
                $sourceId = $dependency['sourceid'];
                $element = Service::getElementById($sourceType, $sourceId);
                $resultList[] = $element;
            }
            $result->value = $resultList;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getElementType()
    {
        return $this->elementType;
    }

    /**
     * @param mixed $elementType
     */
    public function setElementType($elementType)
    {
        $this->elementType = $elementType;
    }

    /**
     * @return mixed
     */
    public function getOnlyCount()
    {
        return $this->onlyCount;
    }

    /**
     * @param mixed $onlyCount
     */
    public function setOnlyCount($onlyCount)
    {
        $this->onlyCount = $onlyCount;
    }
}
