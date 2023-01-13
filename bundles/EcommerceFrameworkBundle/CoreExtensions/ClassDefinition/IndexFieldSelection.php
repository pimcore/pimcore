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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ClassDefinition;

use Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

class IndexFieldSelection extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, NormalizerInterface
{
    use Data\Extension\ColumnType;
    use Data\Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'indexFieldSelection';

    /**
     * Type for the column to query
     *
     * @var array
     */
    public $queryColumnType = [
        'tenant' => 'varchar(100)',
        'field' => 'varchar(200)',
        'preSelect' => 'text',
    ];

    /**
     * Type for the column
     *
     * @var array
     */
    public $columnType = [
        'tenant' => 'varchar(100)',
        'field' => 'varchar(200)',
        'preSelect' => 'text',
    ];

    public $width;

    public $considerTenants = false;

    public $multiPreSelect = false;

    public $filterGroups = '';

    public $predefinedPreSelectOptions = [];

    public function __construct()
    {
    }

    /**
     * @param bool $considerTenants
     * @return void
     */
    public function setConsiderTenants($considerTenants)
    {
        $this->considerTenants = $considerTenants;
    }

    /**
     * @return bool
     */
    public function getConsiderTenants()
    {
        return $this->considerTenants;
    }

    public function setFilterGroups($filterGroups)
    {
        $this->filterGroups = $filterGroups;
    }

    public function getFilterGroups()
    {
        return $this->filterGroups;
    }

    /**
     * @param string $multiPreSelect
     */
    public function setMultiPreSelect($multiPreSelect)
    {
        $this->multiPreSelect = $multiPreSelect;
    }

    /**
     * @return string
     */
    public function getMultiPreSelect()
    {
        return $this->multiPreSelect;
    }

    /**
     * @param array $predefinedPreSelectOptions
     */
    public function setPredefinedPreSelectOptions($predefinedPreSelectOptions)
    {
        $this->predefinedPreSelectOptions = $predefinedPreSelectOptions;
    }

    /**
     * @return array
     */
    public function getPredefinedPreSelectOptions()
    {
        return $this->predefinedPreSelectOptions;
    }

    /**
     * @param mixed $data
     * @param null|AbstractObject $object
     * @param array $params
     *
     * @return array
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof ObjectData\IndexFieldSelection) {
            return [
                $this->getName() . '__tenant' => $data->getTenant(),
                $this->getName() . '__field' => $data->getField(),
                $this->getName() . '__preSelect' => $data->getPreSelect(),
            ];
        }

        return [
            $this->getName() . '__tenant' => null,
            $this->getName() . '__field' => null,
            $this->getName() . '__preSelect' => null,
        ];
    }

    /**
     * @param mixed $data
     * @param null|AbstractObject $object
     * @param array $params
     *
     * @return ObjectData\IndexFieldSelection|null
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . '__field']) {
            return new ObjectData\IndexFieldSelection($data[$this->getName() . '__tenant'], $data[$this->getName() . '__field'], $data[$this->getName() . '__preSelect']);
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param null|AbstractObject $object
     * @param array $params
     *
     * @return array
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|AbstractObject $object
     * @param array $params
     *
     * @return array|null
     *
     * @see Data::getDataForEditmode
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof ObjectData\IndexFieldSelection) {
            return [
                'tenant' => $data->getTenant(),
                'field' => $data->getField(),
                'preSelect' => $data->getPreSelect(),
            ];
        }

        return null;
    }

    /**
     * @param array $data
     * @param null|AbstractObject $object
     * @param array $params
     *
     * @return ObjectData\IndexFieldSelection|null
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data['field']) {
            if (is_array($data['preSelect'])) {
                $data['preSelect'] = implode(',', $data['preSelect']);
            }

            return new ObjectData\IndexFieldSelection($data['tenant'], $data['field'], $data['preSelect']);
        }

        return null;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param ObjectData\IndexFieldSelection|null $data
     * @param Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof ObjectData\IndexFieldSelection) {
            return $data->getTenant() . ' ' . $data->getField() . ' ' . $data->getPreSelect();
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck && $this->getMandatory() &&
            ($data === null || $data->getField() === null)) {
            throw new \Exception(get_class($this).': Empty mandatory field [ '.$this->getName().' ]');
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @internal
     *
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $key = $this->getName();
        $getter = 'get'.ucfirst($key);
        if ($object->$getter() instanceof ObjectData\IndexFieldSelection) {
            $preSelect = $object->$getter()->getPreSelect();
            if (is_array($preSelect)) {
                $preSelect = implode('%%', $preSelect);
            }

            return $object->$getter()->getTenant() . '%%%%' . $object->$getter()->getField() . '%%%%' . $preSelect ;
        }

        return '';
    }

    /**
     * True if change is allowed in edit mode.
     *
     * @param Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param mixed $width
     */
    public function setWidth($width)
    {
        $this->width = (int)$width;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . ObjectData\IndexFieldSelection::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . ObjectData\IndexFieldSelection::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . ObjectData\IndexFieldSelection::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . ObjectData\IndexFieldSelection::class . '|null';
    }

    public function normalize($value, $params = [])
    {
        if ($value instanceof ObjectData\IndexFieldSelection) {
            return [
                'tenant' => $value->getTenant(),
                'field' => $value->getField(),
                'preSelect' => $value->getPreSelect(),
            ];
        }

        return null;
    }

    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $tenant = $value['tenant'];
            $field = $value['field'];
            $preSelect = $value['preSelect'];

            return new ObjectData\IndexFieldSelection($tenant, $field, $preSelect);
        }
        if ($value instanceof ObjectData\IndexFieldSelection) {
            return $value;
        }

        return null;
    }
}
