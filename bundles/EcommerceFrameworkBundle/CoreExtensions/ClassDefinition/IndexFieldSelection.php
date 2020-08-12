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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ClassDefinition;

use Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\Concrete;

class IndexFieldSelection extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface
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

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CoreExtensions\\ObjectData\\IndexFieldSelection';

    public $width;
    public $considerTenants = false;
    public $multiPreSelect = false;
    public $filterGroups = '';
    public $predefinedPreSelectOptions = [];

    public function __construct()
    {
    }

    public function setConsiderTenants($considerTenants)
    {
        $this->considerTenants = $considerTenants;
    }

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
     * @param bool $multiPreSelect
     */
    public function setMultiPreSelect($multiPreSelect)
    {
        $this->multiPreSelect = $multiPreSelect;
    }

    /**
     * @return bool
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
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param ObjectData\IndexFieldSelection|null $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return array
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
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . '__field']) {
            return new ObjectData\IndexFieldSelection($data[$this->getName() . '__tenant'], $data[$this->getName() . '__field'], $data[$this->getName() . '__preSelect']);
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param ObjectData\IndexFieldSelection|null $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param ObjectData\IndexFieldSelection|null $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|null
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
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return ObjectData\IndexFieldSelection|null
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
     * @param IndexFieldSelection|null $data
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
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if (!$omitMandatoryCheck && $this->getMandatory() &&
            ($data === null || $data->getField() === null)) {
            throw new \Exception(get_class($this).': Empty mandatory field [ '.$this->getName().' ]');
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
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
     * fills object field data values from CSV Import String
     *
     * @param string $importValue
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return ObjectData\IndexFieldSelection|null
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $values = explode('%%%%', $importValue);

        $value = null;
        if ($values[0] && $values[1] && $values[2]) {
            $preSelect = explode('%%', $value[2]);
            $value = new ObjectData\IndexFieldSelection($value[0], $values[1], $preSelect);
        }

        return $value;
    }

    /**
     * converts data to be exposed via webservices
     *
     * @param \Pimcore\Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $key = $this->getName();
        $getter = 'get'.ucfirst($key);

        if ($object->$getter() instanceof ObjectData\IndexFieldSelection) {
            $preSelect = $object->$getter()->getPreSelect();
            if ($preSelect) {
                if (!is_array($preSelect)) {
                    $preSelect = explode(',', $preSelect);
                }
                $preSelect = implode('%%', $preSelect);
            }

            return [
                'tenant' => $object->$getter()->getTenant(),
                'field' => $object->$getter()->getField(),
                'preSelect' => $preSelect,
            ];
        } else {
            return null;
        }
    }

    /**
     * converts data to be imported via webservices
     *
     * @deprecated
     *
     * @param mixed $value
     * @param \Pimcore\Model\DataObject\AbstractObject|null $relatedObject
     * @param mixed $params
     * @param \Pimcore\Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } elseif ($value['field'] !== null) {
            return new ObjectData\IndexFieldSelection($value['tenant'], $value['field'], explode('%%', $value['preSelect']));
        } else {
            throw new \Exception(get_class($this).': cannot get values from web service import - invalid data');
        }
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
        $this->width = intval($width);
    }
}
