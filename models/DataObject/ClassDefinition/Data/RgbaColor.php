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

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Tool\Serialize;

class RgbaColor extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use Model\DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'rgbaColor';

    /**
     * @var int
     */
    public $width;

    /**
     * Type for the column to query
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
     * @var array
     */
    public $columnType = ['rgb' => 'VARCHAR(6) NULL DEFAULT NULL',
        'a' => 'VARCHAR(2) NULL DEFAULT NULL',
    ];

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\RgbaColor';

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param Model\DataObject\Data\RgbaColor $data
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
                $data->setOwner($params['owner'], $params['fieldname'], $params['language'] ?? null);
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
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        parent::checkValidity($data, $omitMandatoryCheck);
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
     * converts data to be exposed via webservices
     *
     * @deprecated
     *
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof Model\DataObject\Data\RgbaColor) {
            return $this->getDataForEditmode($data, $object, $params);
        }

        return null;
    }

    /**
     * converts data to be imported via webservices
     *
     * @deprecated
     *
     * @param mixed $value
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return Model\DataObject\Data\RgbaColor|null
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        return $this->getDataFromEditmode($value, $object, $params);
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

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value instanceof Model\DataObject\Data\RgbaColor) {
            $rgb = sprintf('%02x%02x%02x', $value->getR(), $value->getG(), $value->getB());
            $a = sprintf('%02x', $value->getA());

            return [
                'value' => $rgb,
                'value2' => $a,
            ];
        } elseif (is_array($value)) {
            return [
                'value' => $value[$this->getName() . '__rgb'],
                'value2' => $value[$this->getName() . '__a'],
            ];
        }

        return $value;
    }

    /** See marshal
     * @param mixed $value
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\RgbaColor|null
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if ($value) {
            $rgb = $value['value'];
            $a = $value['value2'];
            list($r, $g, $b) = sscanf($rgb, '%02x%02x%02x');
            $a = hexdec($a);
            $color = new Model\DataObject\Data\RgbaColor($r, $g, $b, $a);

            return $color;
        }

        return null;
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @param string $importValue
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($importValue, $object, $params);
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
     * @param string|array $value
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
            if ($operator == '=') {
                $operator = 'IS';
            } elseif ($operator == '!=') {
                $operator = 'IS NOT';
            }
        } elseif (!is_array($value) && !is_object($value)) {
            if ($operator == 'LIKE') {
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
}
