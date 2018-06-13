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

namespace Pimcore\DataObject\GridColumnConfig\Value;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\Element\ElementInterface;

class DefaultValue extends AbstractValue
{
    /**
     * @param $object
     * @param $key
     * @param null $brickType
     * @param null $brickKey
     * @param null $fieldDefinition
     *
     * @return \stdClass
     *
     * @throws \Exception
     */
    private function getValueForObject($object, $key, $brickType = null, $brickKey = null, $fieldDefinition = null)
    {
        $getter = 'get' . ucfirst($key);
        $value = $object->$getter();

        if (!empty($value) && !empty($brickType)) {
            $getBrickType = 'get' . ucfirst($brickType);
            $value = $value->$getBrickType();

            if (!empty($value) && !empty($brickKey)) {
                $brickGetter = 'get' . ucfirst($brickKey);
                $value = $value->$brickGetter();
            }
        }

        if (!$fieldDefinition) {
            $fieldDefinition = $object->getClass()->getFieldDefinition($key);

            if (!$fieldDefinition && ($localizedFields = $object->getClass()->getFieldDefinition('localizedfields'))) {
                $fieldDefinition = $localizedFields->getFieldDefinition($key);
            }
        }

        if (!empty($brickType) && !empty($brickKey)) {
            $brickClass = Objectbrick\Definition::getByKey($brickType);
            $context = ['object' => $object, 'outerFieldname' => $key];
            $fieldDefinition = $brickClass->getFieldDefinition($brickKey, $context);
        }

        if ($fieldDefinition->isEmpty($value)) {
            $parent = Service::hasInheritableParentObject($object);

            if (!empty($parent)) {
                return $this->getValueForObject($parent, $key, $brickType, $brickKey, $fieldDefinition);
            }
        }

        $result = new \stdClass();
        $result->value = $value;
        $result->label = $fieldDefinition->getTitle();
        $result->def = $fieldDefinition;
        $result->empty = $fieldDefinition->isEmpty($value);
        $result->objectid = $object->getId();

        return $result;
    }

    /**
     * @param $value
     *
     * @return \stdClass
     */
    private function getDefaultValue($value)
    {
        $result = new \stdClass();
        $result->value = $value;
        $result->label = $this->label;
        $result->def = null;

        if (empty($value) || (is_object($value) && method_exists($value, 'isEmpty') && $value->isEmpty())) {
            $result->empty = true;
        } else {
            $result->empty = false;
        }

        return $result;
    }

    /**
     * @param ElementInterface|Concrete $element
     *
     * {@inheritdoc}
     */
    public function getLabeledValue($element)
    {
        /** @var Concrete $element */
        $attributeParts = explode('~', $this->attribute);

        $getter = 'get' . ucfirst($this->attribute);
        $brickType = null;
        $brickKey = null;

        if (substr($this->attribute, 0, 1) == '~') {
            // key value, ignore for now
        } elseif (count($attributeParts) > 1) {
            $brickType = $attributeParts[0];
            $brickKey = $attributeParts[1];
        }

        if (method_exists($element, $getter)) {
            if ($element instanceof AbstractObject) {
                try {
                    $result = $this->getValueForObject($element, $this->attribute, $brickType, $brickKey);
                } catch (\Exception $e) {
                    $result = $this->getDefaultValue($element->$getter());
                }
            } else {
                $result = $this->getDefaultValue($element->$getter());
            }

            return $result;
        }

        return null;
    }
}
