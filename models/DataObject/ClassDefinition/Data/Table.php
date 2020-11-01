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
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Tool\Serialize;

class Table extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, EqualComparisonInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'table';

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
    public $cols;

    /**
     * @var bool
     */
    public $colsFixed;

    /**
     * @var int
     */
    public $rows;

    /**
     * @var bool
     */
    public $rowsFixed;

    /**
     * Default data
     *
     * @var string
     */
    public $data = '';

    /**
     * @var bool
     */
    public $columnConfigActivated = false;

    /**
     * @var array
     */
    public $columnConfig = [];

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'longtext';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'longtext';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = 'array|string';

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
    public function getCols()
    {
        return $this->cols;
    }

    /**
     * @param int $cols
     *
     * @return $this
     */
    public function setCols($cols)
    {
        $this->cols = $this->getAsIntegerCast($cols);

        return $this;
    }

    /**
     * @return int
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param int $rows
     *
     * @return $this
     */
    public function setRows($rows)
    {
        $this->rows = $this->getAsIntegerCast($rows);

        return $this;
    }

    /**
     * @return bool
     */
    public function getRowsFixed()
    {
        return $this->rowsFixed;
    }

    /**
     * @param bool $rowsFixed
     *
     *@return $this
     */
    public function setRowsFixed($rowsFixed)
    {
        $this->rowsFixed = (bool)$rowsFixed;

        return $this;
    }

    /**
     * @return bool
     */
    public function getColsFixed()
    {
        return $this->colsFixed;
    }

    /**
     * @param bool $colsFixed
     *
     * @return $this
     */
    public function setColsFixed($colsFixed)
    {
        $this->colsFixed = (bool)$colsFixed;

        return $this;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return bool
     */
    public function isColumnConfigActivated(): bool
    {
        return $this->columnConfigActivated;
    }

    /**
     * @param bool $columnConfigActivated
     */
    public function setColumnConfigActivated(bool $columnConfigActivated): void
    {
        $this->columnConfigActivated = $columnConfigActivated;
    }

    /**
     * @return array
     */
    public function getColumnConfig(): array
    {
        return $this->columnConfig;
    }

    /**
     * @param array $columnConfig
     */
    public function setColumnConfig(array $columnConfig): void
    {
        $this->columnConfig = $columnConfig;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function convertDataToValueArray(array $data)
    {
        $valueArray = [];
        foreach ($data as $entry) {
            if (is_array($entry)) {
                $valueArray[] = $this->convertDataToValueArray($entry);
            } else {
                $valueArray[] = $entry;
            }
        }

        return $valueArray;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            //make sure only array values are stored to DB
            $data = $this->convertDataToValueArray($data);
        }

        return Serialize::serialize($data);
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        $unserializedData = Serialize::unserialize((string) $data);

        if ($data === null || $unserializedData === null) {
            return null;
        }

        //set array keys based on column configuration if set
        $columnConfig = $this->getColumnConfig();

        if (!$this->isColumnConfigActivated() || !$columnConfig) {
            return $unserializedData;
        }

        $dataWithKeys = [];

        foreach ($unserializedData as $row) {
            $indexedRow = [];
            $index = 0;

            foreach ($row as $col) {
                $indexedRow[$columnConfig[$index]['key']] = $col;
                $index++;
            }

            $dataWithKeys[] = $indexedRow;
        }

        return $dataWithKeys;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        if (!empty($data)) {
            $tmpLine = [];
            if (is_array($data)) {
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $tmpLine[] = implode('|', $row);
                    }
                }
            }

            return implode("\n", $tmpLine);
        }

        return '';
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            //make sure only array values are used of edit mode (other wise ext stores do not work anymore)
            return $this->convertDataToValueArray($data);
        }

        return $data;
    }

    /**
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        // check for empty data
        $checkData = '';
        if (is_array($data)) {
            foreach ($data as $row) {
                if (is_array($row)) {
                    $checkData .= implode('', $row);
                }
            }
        }
        $checkData = str_replace(' ', '', $checkData);

        if (empty($checkData)) {
            return null;
        }

        return $data;
    }

    /**
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return array
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        $versionPreview = $this->getDiffVersionPreview($data, $object, $params);
        if (is_array($versionPreview) && $versionPreview['html']) {
            return $versionPreview['html'];
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
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!empty($data) and !is_array($data)) {
            throw new Model\Element\ValidationException('Invalid table data');
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            return base64_encode(Serialize::serialize($data));
        }

        return '';
    }

    /**
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return array|null
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $value = Serialize::unserialize(base64_decode($importValue));
        if (is_array($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if (!empty($data)) {
            $tmpLine = [];
            if (is_array($data)) {
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $tmpLine[] = implode(' ', $row);
                    }
                }
            }

            return implode("\n", $tmpLine);
        }

        return '';
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

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param array|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if ($data) {
            $html = '<table>';

            if ($this->isColumnConfigActivated()) {
                $html .= '<tr>';

                $index = 0;
                $columConfig = $this->getColumnConfig();

                foreach (current($data) as $cellData) {
                    $html .= '<th>';
                    $html .= htmlentities($columConfig[$index]['label']);
                    $html .= '</td>';
                    $index++;
                }
                $html .= '</tr>';
            }

            foreach ($data as $row) {
                $html .= '<tr>';
                if (is_array($row)) {
                    foreach ($row as $cell) {
                        $html .= '<td>';
                        $html .= htmlentities($cell);
                        $html .= '</td>';
                    }
                }
                $html .= '</tr>';
            }
            $html .= '</table>';

            $value = [];
            $value['html'] = $html;
            $value['type'] = 'html';

            return $value;
        } else {
            return '';
        }
    }

    /** converts data to be imported via webservices
     * @deprecated
     *
     * @param mixed $value
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return array|mixed
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if ($value && is_array($value)) {
            $result = [];
            foreach ($value as $item) {
                $item = (array) $item;
                $item = array_values($item);
                $result[] = $item;
            }

            return $result;
        }

        return $value;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Table $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->cols = $masterDefinition->cols;
        $this->colsFixed = $masterDefinition->colsFixed;
        $this->rows = $masterDefinition->rows;
        $this->rowsFixed = $masterDefinition->rowsFixed;
        $this->data = $masterDefinition->data;
    }

    /**
     * @param array|null $oldValue
     * @param array|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        return $this->isEqualArray($oldValue, $newValue);
    }
}
