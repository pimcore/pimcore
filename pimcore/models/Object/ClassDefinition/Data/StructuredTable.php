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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class StructuredTable extends Model\Object\ClassDefinition\Data
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'structuredTable';

    /**
     * @var int
     */
    public $width;

    /**
     * @var int
     */
    public $height;

    /**
     * @var int
     */
    public $labelWidth;

    /**
     * @var string
     */
    public $labelFirstCell;

    /**
     * @var object
     */
    public $cols;

    /**
     * @var object
     */
    public $rows;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = null;

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = null;

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\Object\\Data\\StructuredTable';

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
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);

        return $this;
    }

    /**
     * @return int
     */
    public function getLabelWidth()
    {
        return $this->labelWidth;
    }

    /**
     * @param int $labelWidth
     *
     * @return $this
     */
    public function setLabelWidth($labelWidth)
    {
        $this->labelWidth = $labelWidth;

        return $this;
    }

    /**
     * @param $labelFirstCell
     *
     * @return $this
     */
    public function setLabelFirstCell($labelFirstCell)
    {
        $this->labelFirstCell = $labelFirstCell;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabelFirstCell()
    {
        return $this->labelFirstCell;
    }

    /**
     * @return object
     */
    public function getCols()
    {
        return $this->cols;
    }

    /**
     * @param object $cols
     *
     * @return $this
     */
    public function setCols($cols)
    {
        if (isset($cols['key'])) {
            $cols = [$cols];
        }
        usort($cols, [$this, 'sort']);

        $this->cols = [];

        foreach ($cols as $c) {
            $c['key'] = strtolower($c['key']);
            $this->cols[] = $c;
        }

        return $this;
    }

    /**
     * @return object
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param $rows
     *
     * @return $this
     */
    public function setRows($rows)
    {
        if (isset($rows['key'])) {
            $rows = [$rows];
        }

        usort($rows, [$this, 'sort']);

        $this->rows = [];

        foreach ($rows as $r) {
            $r['key'] = strtolower($r['key']);
            $this->rows[] = $r;
        }

        return $this;
    }

    /**
     * @param $a
     * @param $b
     *
     * @return int|mixed
     */
    public function sort($a, $b)
    {
        if (is_array($a) && is_array($b)) {
            return $a['position'] - $b['position']; // strcmp($a['position'], $b['position']);
        }

        return strcmp($a, $b);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     *
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $resourceData = [];
        if (!empty($data)) {
            $data = $data->getData();

            foreach ($this->getRows() as $r) {
                foreach ($this->getCols() as $c) {
                    $name = $r['key'] . '#' . $c['key'];
                    $resourceData[$this->getName() . '__' . $name] = $data[$r['key']][$c['key']];
                }
            }
        }

        return $resourceData;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     *
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return Object\Data\StructuredTable
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        $structuredData = [];
        foreach ($this->getRows() as $r) {
            foreach ($this->getCols() as $c) {
                $name = $r['key'] . '#' . $c['key'];
                $structuredData[$r['key']][$c['key']] = $data[$this->getName() . '__' . $name];
            }
        }

        return new Object\Data\StructuredTable($structuredData);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     *
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     *
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $editArray = [];
        if ($data instanceof Object\Data\StructuredTable) {
            if ($data->isEmpty()) {
                return [];
            } else {
                $data = $data->getData();
                foreach ($this->getRows() as $r) {
                    $editArrayItem = [];
                    $editArrayItem['__row_identifyer'] = $r['key'];
                    $editArrayItem['__row_label'] = $r['label'];
                    foreach ($this->getCols() as $c) {
                        $editArrayItem[$c['key']] = $data[$r['key']][$c['key']];
                    }
                    $editArray[] = $editArrayItem;
                }
            }
        }

        return $editArray;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $table = new Object\Data\StructuredTable();
        $tableData = [];
        foreach ($data as $dataLine) {
            foreach ($this->cols as $c) {
                $tableData[$dataLine['__row_identifyer']][$c['key']] = $dataLine[$c['key']];
            }
        }
        $table->setData($tableData);

        return $table;
    }

    /**
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if ($data instanceof Object\Data\StructuredTable) {
            if (!$data->isEmpty()) {
                return $data->getData();
            }
        }

        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     *
     * @param string $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data) {
            return $data->getHtmlTable($this->rows, $this->cols);
        } else {
            return null;
        }
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
        if (!$omitMandatoryCheck and $this->getMandatory()) {
            $empty = true;
            if (!empty($data)) {
                $dataArray = $data->getData();
                foreach ($this->getRows() as $r) {
                    foreach ($this->getCols() as $c) {
                        if (!empty($dataArray[$r['key']][$c['key']])) {
                            $empty = false;
                        }
                    }
                }
            }
            if ($empty) {
                throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
            }
        }

        if (!empty($data) and !$data instanceof Object\Data\StructuredTable) {
            throw new Model\Element\ValidationException('invalid table data');
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param Object\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $value = $this->getDataFromObjectParam($object, $params);

        if ($value instanceof Object\Data\StructuredTable) {
            $string = '';
            $dataArray = $value->getData();
            foreach ($this->getRows() as $r) {
                foreach ($this->getCols() as $c) {
                    $string .= $dataArray[$r['key']][$c['key']] . '##';
                }
            }

            return $string;
        } else {
            return null;
        }
    }

    /**
     * @param $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed|Object\Data\StructuredTable
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $dataArray = explode('##', $importValue);

        $i = 0;
        $dataTable = [];
        foreach ($this->getRows() as $r) {
            foreach ($this->getCols() as $c) {
                $dataTable[$r['key']][$c['key']] = $dataArray[$i];
                $i++;
            }
        }

        $value = new Object\Data\StructuredTable($dataTable);

        return $value;
    }

    /**
     * converts data to be exposed via webservices
     *
     * @param string $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $webserviceArray = [];
        $table = $this->getDataFromObjectParam($object, $params);

        if ($table instanceof Object\Data\StructuredTable) {
            $dataArray = $table->getData();
            foreach ($this->getRows() as $r) {
                foreach ($this->getCols() as $c) {
                    $name = $r['key'] . '#' . $c['key'];
                    $webserviceArray[$name] = $dataArray[$r['key']][$c['key']];
                }
            }

            return $webserviceArray;
        } else {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param null $object
     * @param mixed $params
     * @param null $idMapper
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } else {
            if ($value instanceof \stdClass) {
                $value = (array) $value;
            }
            if (is_array($value)) {
                $dataArray = [];
                foreach ($this->getRows() as $r) {
                    foreach ($this->getCols() as $c) {
                        $name = $r['key'] . '#' . $c['key'];
                        $dataArray[$r['key']][$c['key']] = $value[$name];
                    }
                }

                return new Object\Data\StructuredTable($dataArray);
            } else {
                throw new \Exception('cannot get values from web service import - invalid data');
            }
        }
    }

    /**
     * @return array|string
     */
    public function getColumnType()
    {
        $columns = [];
        foreach ($this->calculateDbColumns() as $c) {
            $columns[$c->name] = $c->type;
        }

        return $columns;
    }

    /**
     * @return array|string
     */
    public function getQueryColumnType()
    {
        $columns = [];
        foreach ($this->calculateDbColumns() as $c) {
            $columns[$c->name] = $c->type;
        }

        return $columns;
    }

    /**
     * @return array
     */
    protected function calculateDbColumns()
    {
        $rows = $this->getRows();
        $cols = $this->getCols();

        $dbCols = [];

        foreach ($rows as $r) {
            foreach ($cols as $c) {
                $name = $r['key'] . '#' . $c['key'];

                $col = new \stdClass();
                $col->name = $name;
                $length = 0;
                if (isset($c['length']) && $c['length']) {
                    $length = $c['length'];
                }
                $col->type = $this->typeMapper($c['type'], $length);
                $dbCols[] = $col;
            }
        }

        return $dbCols;
    }

    /**
     * @param $type string text|number|bool
     * @param $length int The length of the column, default is 255 for text
     *
     * @return string|null
     */
    protected function typeMapper($type, $length = null)
    {
        $mapper = [
            'text' => 'varchar('.($length > 0 ? $length : '190').')',
            'number' => 'double',
            'bool' => 'tinyint(1)'
        ];

        return $mapper[$type];
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        if ($data instanceof Object\Data\StructuredTable) {
            return $data->isEmpty();
        } else {
            return true;
        }
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** See parent class.
     * @param mixed $data
     * @param null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        $defaultData = parent::getDiffDataForEditMode($data, $object, $params);
        $html =  $defaultData[0]['value'];
        $value = [];
        $value['html'] = $html;
        $value['type'] = 'html';
        $defaultData[0]['value'] = $value;

        return $defaultData;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition)
    {
        $this->labelWidth = $masterDefinition->labelWidth;
        $this->labelFirstCell = $masterDefinition->labelFirstCell;
        $this->cols = $masterDefinition->cols;
        $this->rows = $masterDefinition->rows;
    }
}
