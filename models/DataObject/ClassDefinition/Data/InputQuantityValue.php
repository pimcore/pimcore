<?php
declare(strict_types=1);

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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Data\InputQuantityValue as InputQuantityValueDataObject;
use Pimcore\Model\DataObject\QuantityValue\Unit;

/**
 * TODO: Refactor - this class is very similar to the parent one so probably we can try to refactor parent and have better results here also
 *
 * Class InputQuantityValue
 *
 * @package Pimcore\Model\DataObject\ClassDefinition\Data
 */
class InputQuantityValue extends QuantityValue
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * @internal
     *
     * @var string
     */
    public string $fieldtype = 'inputQuantityValue';

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var array
     */
    public $queryColumnType = [
        'value' => 'varchar(255)',
        'unit' => 'varchar(50)',
    ];

    /**
     * Type for the column
     *
     * @internal
     *
     * @var array
     */
    public $columnType = [
        'value' => 'varchar(255)',
        'unit' => 'varchar(50)',
    ];

    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?InputQuantityValueDataObject
    {
        if ($data[$this->getName() . '__value'] || $data[$this->getName() . '__unit']) {
            $dataObject = $this->getNewDataObject($data[$this->getName() . '__value'], $data[$this->getName() . '__unit']);

            if (isset($params['owner'])) {
                $dataObject->_setOwner($params['owner']);
                $dataObject->_setOwnerFieldname($params['fieldname']);
                $dataObject->_setOwnerLanguage($params['language'] ?? null);
            }

            return $dataObject;
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return InputQuantityValueDataObject|null
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?InputQuantityValueDataObject
    {
        if ($data['value'] || $data['unit']) {
            if (empty($data['unit']) || $data['unit'] == -1) {
                return $this->getNewDataObject($data['value'], null);
            }

            return $this->getNewDataObject($data['value'], $data['unit']);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = [])
    {
        if ($omitMandatoryCheck) {
            return;
        }

        if ($this->getMandatory() &&
            ($data === null || $data->getValue() === null || $data->getUnitId() === null)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    private function getNewDataObject(string $value = null, Unit|string $unitId = null): InputQuantityValueDataObject
    {
        return new InputQuantityValueDataObject($value, $unitId);
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Model\DataObject\Data\InputQuantityValue::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Model\DataObject\Data\InputQuantityValue::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . Model\DataObject\Data\InputQuantityValue::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Model\DataObject\Data\InputQuantityValue::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof Model\DataObject\Data\InputQuantityValue) {
            return [
                'value' => $value->getValue(),
                'unitId' => $value->getUnitId(),
            ];
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?InputQuantityValueDataObject
    {
        if (is_array($value)) {
            return new Model\DataObject\Data\InputQuantityValue($value['value'], $value['unitId']);
        }

        return null;
    }
}
