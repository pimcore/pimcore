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

use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\Element\ElementInterface;

class DefaultValue extends AbstractValue
{
    /**
     * @var LocaleServiceInterface
     */
    protected $localeService;

    public function __construct($config, $context = null, LocaleServiceInterface $localeService = null)
    {
        parent::__construct($config, $context);
        $this->localeService = $localeService;
    }

    /**
     * @param Concrete $object
     * @param string $key
     * @param string|null $brickType
     * @param string|null $brickKey
     *
     * @return \stdClass
     *
     * @throws \Exception
     */
    private function getValueForObject($object, $key, $brickType = null, $brickKey = null)
    {
        if (!$key) {
            return;
        }

        $fieldDefinition = null;
        if (!empty($brickType)) {
            $getter = 'get' . Service::getFieldForBrickType($object->getClass(), $brickType);
            $value = $object->$getter();

            $getBrickType = 'get' . ucfirst($brickType);
            $value = $value->$getBrickType();
            if (!empty($value) && !empty($brickKey)) {
                $brickGetter = 'get' . ucfirst($brickKey);
                $value = $value->$brickGetter();

                $brickClass = Objectbrick\Definition::getByKey($brickType);
                $context = ['object' => $object, 'outerFieldname' => $key];
                $fieldDefinition = $brickClass->getFieldDefinition($brickKey, $context);
            }
        } else {
            $getter = 'get' . ucfirst($key);
            $value = $object->$getter();

            $fieldDefinition = $object->getClass()->getFieldDefinition($key);

            if (!$fieldDefinition) {
                $localizedFields = $object->getClass()->getFieldDefinition('localizedfields');
                if ($localizedFields instanceof Data\Localizedfields) {
                    $fieldDefinition = $localizedFields->getFieldDefinition($key);
                }
            }
        }

        if (!$fieldDefinition instanceof Data) {
            return $this->getDefaultValue($value);
        }

        if ($fieldDefinition->isEmpty($value)) {
            $parent = Service::hasInheritableParentObject($object);

            if (!empty($parent)) {
                return $this->getValueForObject($parent, $key, $brickType, $brickKey);
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

    private function getClassificationStoreValueForObject($object, $key)
    {
        $keyParts = explode('~', $key);

        if (strpos($key, '~') === 0) {
            $type = $keyParts[1];
            if ($type === 'classificationstore') {
                $field = $keyParts[2];
                $groupKeyId = explode('-', $keyParts[3]);

                $groupId = $groupKeyId[0];
                $keyid = $groupKeyId[1];
                $getter = 'get' . ucfirst($field);

                if (method_exists($object, $getter)) {
                    /** @var Classificationstore $classificationStoreData */
                    $classificationStoreData = $object->$getter();

                    /** @var Data\Classificationstore $csFieldDefinition */
                    $csFieldDefinition = $object->getClass()->getFieldDefinition($field);
                    $csLanguage = $this->localeService->getLocale();

                    if (!$csFieldDefinition->isLocalized()) {
                        $csLanguage = 'default';
                    }

                    $fielddata = $classificationStoreData->getLocalizedKeyValue($groupId, $keyid, $csLanguage, true, true);

                    $keyConfig = Classificationstore\KeyConfig::getById($keyid);
                    $type = $keyConfig->getType();
                    $definition = json_decode($keyConfig->getDefinition());
                    $definition = Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

                    $result = new \stdClass();
                    $result->value = $fielddata;
                    $result->label = $definition->getTitle();
                    $result->def = $definition;
                    $result->empty = $definition->isEmpty($fielddata);
                    $result->objectid = $object->getId();

                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * @param mixed $value
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

            return $this->getClassificationStoreValueForObject($element, $this->attribute);
        } elseif (count($attributeParts) > 1) {
            $brickType = $attributeParts[0];
            $brickKey = $attributeParts[1];

            $getter = 'get' . Service::getFieldForBrickType($element->getClass(), $brickType);
        }

        if ($this->attribute && method_exists($element, $getter)) {
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
