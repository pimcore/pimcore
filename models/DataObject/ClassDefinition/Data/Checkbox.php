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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;

class Checkbox extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use DataObject\Traits\DefaultValueTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'checkbox';

    /**
     * @var int|null
     */
    public $defaultValue;

    /**
     * @return int|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     *
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        if (!is_numeric($defaultValue)) {
            $defaultValue = null;
        }
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function getSchemaColumns(): array
    {
        return [
            new Column($this->getName(), Type::getType(Types::BOOLEAN), [
                'notnull' => false
            ])
        ];
    }

    public function getQuerySchemaColumns(): array
    {
        return $this->getSchemaColumns();
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param bool $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        return is_null($data) ? null : (int)$data;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param bool $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (!is_null($data)) {
            $data = (bool) $data;
        }

        return $data;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param bool $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param bool $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param bool $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param bool $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return (string)$data;
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
        if (!$omitMandatoryCheck and $this->getMandatory() and $data === null) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }

        /* @todo seems to cause problems with old installations
        if(!is_bool($data) and $data !== 1 and $data !== 0){
        throw new \Exception(get_class($this).": invalid data");
        }*/
    }

    /**
     * Converts object data to a simple string value or CSV Export
     *
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        return (string)$data;
    }

    /**
     * fills object field data values from CSV Import String
     *
     * @abstract
     *
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return (bool)$importValue;
    }

    /** True if change is allowed in edit mode.
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Checkbox $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->defaultValue = $masterDefinition->defaultValue;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param  string $value
     * @param  string $operator
     * @param  array $params
     *
     * @return string
     *
     */
    public function getFilterCondition($value, $operator, $params = [])
    {
        $params['name'] = $this->name;

        return $this->getFilterConditionExt(
            $value,
            $operator,
            $params
        );
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param string $value
     * @param string $operator
     * @param array $params optional params used to change the behavior
     *
     * @return string
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        $db = \Pimcore\Db::get();
        $value = $db->quote($value);
        $key = $db->quoteIdentifier($this->name);

        $brickPrefix = $params['brickPrefix'] ? $db->quoteIdentifier($params['brickPrefix']) . '.' : '';

        return 'IFNULL(' . $brickPrefix . $key . ', 0) = ' . $value . ' ';
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function isEmpty($data)
    {
        return $data === null;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $context
     *
     * @return null|int
     */
    protected function doGetDefaultValue($object, $context = [])
    {
        return $this->getDefaultValue() ?? null;
    }

    /**
     * @param bool|null $oldValue
     * @param bool|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        return $oldValue === $newValue;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?bool';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?bool';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'bool|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'bool|null';
    }
}
