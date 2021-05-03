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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class RgbaColor extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'rgbaColor';

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var array
     */
    public $queryColumnType = [
        'rgb' => 'VARCHAR(6) NULL DEFAULT NULL',
        'a' => 'VARCHAR(2) NULL DEFAULT NULL',
    ];

    /**
     * Type for the column
     *
     * @internal
     *
     * @var array
     */
    public $columnType = ['rgb' => 'VARCHAR(6) NULL DEFAULT NULL',
        'a' => 'VARCHAR(2) NULL DEFAULT NULL',
    ];

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param Model\DataObject\Data\RgbaColor|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\RgbaColor) {
            $rgb = sprintf('%02x%02x%02x', $data->getR(), $data->getG(), $data->getB());
            $a = sprintf('%02x', $data->getA());

            return [
                $this->getName() . '__rgb' => $rgb,
                $this->getName() . '__a' => $a,
            ];
        }

        return [
            $this->getName() . '__rgb' => null,
            $this->getName() . '__a' => null,
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\RgbaColor|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (is_array($data) && isset($data[$this->getName() . '__rgb']) && isset($data[$this->getName() . '__a'])) {
            list($r, $g, $b) = sscanf($data[$this->getName() . '__rgb'], '%02x%02x%02x');
            $a = hexdec($data[$this->getName() . '__a']);
            $data = new Model\DataObject\Data\RgbaColor($r, $g, $b, $a);
        }

        if ($data instanceof Model\DataObject\Data\RgbaColor) {
            if (isset($params['owner'])) {
                $data->_setOwner($params['owner']);
                $data->_setOwnerFieldname($params['fieldname']);
                $data->_setOwnerLanguage($params['language'] ?? null);
            }

            return $data;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param Model\DataObject\Data\RgbaColor $data
     * @param null|Model\DataObject\Concrete $object
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
     * @param Model\DataObject\Data\RgbaColor|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof  Model\DataObject\Data\RgbaColor) {
            $rgba = sprintf('#%02x%02x%02x%02x', $data->getR(), $data->getG(), $data->getB(), $data->getA());

            return $rgba;
        }

        return null;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\RgbaColor|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data) {
            $data = trim($data, '# ');
            list($r, $g, $b, $a) = sscanf($data, '%02x%02x%02x%02x');
            $color = new Model\DataObject\Data\RgbaColor($r, $g, $b, $a);

            return $color;
        }

        return null;
    }

    /**
     * @param float $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\RgbaColor|null
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        parent::checkValidity($data, $omitMandatoryCheck);

        if ($data instanceof Model\DataObject\Data\RgbaColor) {
            $this->checkColorComponent($data->getR());
            $this->checkColorComponent($data->getG());
            $this->checkColorComponent($data->getB());
            $this->checkColorComponent($data->getA());
        }
    }

    private function checkColorComponent($color)
    {
        if (!is_null($color)) {
            if (!($color >= 0 && $color <= 255)) {
                throw new Model\Element\ValidationException('Color component out of range');
            }
        }
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\RgbaColor $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->width = $masterDefinition->width;
    }

    /**
     * @param Model\DataObject\Data\RgbaColor|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return $data === null;
    }

    /**
     * display the quantity value field data in the grid
     *
     * @param Model\DataObject\Data\RgbaColor|null $data
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     *
     * @return string|null
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @param Model\DataObject\Data\RgbaColor|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof  Model\DataObject\Data\RgbaColor) {
            $value = $data->getHex(true, true);
            $result = '<div style="float: left;"><div style="float: left; margin-right: 5px; background-image: ' . ' url(/bundles/pimcoreadmin/img/ext/colorpicker/checkerboard.png);">'
                        . '<div style="background-color: ' . $value . '; width:15px; height:15px;"></div></div>' . $value . '</div>';

            return $result;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof Model\DataObject\Data\RgbaColor) {
            return [
                'r' => $value->getR(),
                'g' => $value->getG(),
                'b' => $value->getB(),
                'a' => $value->getA(),
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $color = new Model\DataObject\Data\RgbaColor();
            $color->setR($value['r']);
            $color->setG($value['g']);
            $color->setB($value['b']);
            $color->setA($value['a']);

            return $color;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param  string|array $value
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
     * @param string|array|object $value
     * @param string $operator
     * @param array $params optional params used to change the behavior
     *
     * @return string
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        $db = \Pimcore\Db::get();
        $name = $params['name'] ? $params['name'] : $this->name;
        $key = 'concat(' . $db->quoteIdentifier($name  . '__rgb') .' ,'
            . $db->quoteIdentifier($name  . '__a') .')';

        if ($value === 'NULL') {
            if ($operator === '=') {
                $operator = 'IS';
            } elseif ($operator === '!=') {
                $operator = 'IS NOT';
            }
        } elseif (!is_array($value) && !is_object($value)) {
            if ($operator === 'LIKE') {
                $value = $db->quote('%' . $value . '%');
            } else {
                $value = $db->quote($value);
            }
        }

        return $key . ' ' . $operator . ' ' . $value . ' ';
    }

    /**
     * @param mixed $value
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     *
     * @return string
     */
    public function marshalBeforeEncryption($value, $object = null, $params = [])
    {
        return Serialize::serialize($value);
    }

    /**
     * @param mixed $value
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     *
     * @return mixed
     */
    public function unmarshalAfterDecryption($value, $object = null, $params = [])
    {
        return Serialize::unserialize($value);
    }

    /**
     * @param Model\DataObject\Data\RgbaColor|null $oldValue
     * @param Model\DataObject\Data\RgbaColor|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $oldValue = $oldValue instanceof Model\DataObject\Data\RgbaColor ? $oldValue->getHex(true, false) : null;
        $newValue = $newValue instanceof Model\DataObject\Data\RgbaColor ? $newValue->getHex(true, false) : null;

        return $oldValue === $newValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Model\DataObject\Data\RgbaColor::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Model\DataObject\Data\RgbaColor::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . Model\DataObject\Data\RgbaColor::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Model\DataObject\Data\RgbaColor::class . '|null';
    }
}
