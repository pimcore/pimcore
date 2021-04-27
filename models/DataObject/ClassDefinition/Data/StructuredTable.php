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
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;

class StructuredTable extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use Data\Extension\PositionSortTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'structuredTable';

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public $height = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public $labelWidth = 0;

    /**
     * v
     *
     * @var string
     */
    public $labelFirstCell;

    /**
     * @internal
     *
     * @var array
     */
    public $cols = [];

    /**
     * @internal
     *
     * @var array
     */
    public $rows = [];

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
     * @return string|int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string|int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getLabelWidth()
    {
        return $this->labelWidth;
    }

    /**
     * @param string|int $labelWidth
     *
     * @return $this
     */
    public function setLabelWidth($labelWidth)
    {
        if (is_numeric($labelWidth)) {
            $labelWidth = (int)$labelWidth;
        }
        $this->labelWidth = $labelWidth;

        return $this;
    }

    /**
     * @param string $labelFirstCell
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
     * @return array
     */
    public function getCols()
    {
        return $this->cols;
    }

    /**
     * @param array $cols
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
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param array $rows
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
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\StructuredTable $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $resourceData = [];
        if ($data instanceof DataObject\Data\StructuredTable) {
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
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\StructuredTable
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

        $structuredTable = new DataObject\Data\StructuredTable($structuredData);

        if (isset($params['owner'])) {
            $structuredTable->_setOwner($params['owner']);
            $structuredTable->_setOwnerFieldname($params['fieldname']);
            $structuredTable->_setOwnerLanguage($params['language'] ?? null);
        }

        return $structuredTable;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param DataObject\Data\StructuredTable $data
     * @param null|DataObject\Concrete $object
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
     * @param DataObject\Data\StructuredTable|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $editArray = [];
        if ($data instanceof DataObject\Data\StructuredTable) {
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
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\StructuredTable
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $table = new DataObject\Data\StructuredTable();
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
     * @param DataObject\Data\StructuredTable|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\StructuredTable) {
            if (!$data->isEmpty()) {
                return $data->getData();
            }
        }

        return null;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\StructuredTable|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\StructuredTable) {
            return $data->getHtmlTable($this->rows, $this->cols);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
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

        if (!empty($data) and !$data instanceof DataObject\Data\StructuredTable) {
            throw new Model\Element\ValidationException('invalid table data');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $value = $this->getDataFromObjectParam($object, $params);
        $string = '';

        if ($value instanceof DataObject\Data\StructuredTable) {
            $dataArray = $value->getData();
            foreach ($this->getRows() as $r) {
                foreach ($this->getCols() as $c) {
                    $string .= $dataArray[$r['key']][$c['key']] . '##';
                }
            }
        }

        return $string;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * @param string $type text|number|bool
     * @param int $length The length of the column, default is 255 for text
     *
     * @return string|null
     */
    protected function typeMapper($type, $length = null)
    {
        $mapper = [
            'text' => 'varchar('.($length > 0 ? $length : '190').')',
            'number' => 'double',
            'bool' => 'tinyint(1)',
        ];

        return $mapper[$type];
    }

    /**
     * @param DataObject\Data\StructuredTable|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        if ($data instanceof DataObject\Data\StructuredTable) {
            return $data->isEmpty();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** See parent class.
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        $defaultData = parent::getDiffDataForEditMode($data, $object, $params);
        $html = $defaultData[0]['value'];
        $value = [];
        $value['html'] = $html;
        $value['type'] = 'html';
        $defaultData[0]['value'] = $value;

        return $defaultData;
    }

    /**
     * @param DataObject\ClassDefinition\Data\StructuredTable $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->labelWidth = $masterDefinition->labelWidth;
        $this->labelFirstCell = $masterDefinition->labelFirstCell;
        $this->cols = $masterDefinition->cols;
        $this->rows = $masterDefinition->rows;
    }

    /**
     *
     * @param DataObject\Data\StructuredTable|null $oldValue
     * @param DataObject\Data\StructuredTable|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $oldData = $oldValue instanceof DataObject\Data\StructuredTable ? $oldValue->getData() : [];
        $newData = $newValue instanceof DataObject\Data\StructuredTable ? $newValue->getData() : [];

        return $this->isEqualArray($oldData, $newData);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\StructuredTable::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\StructuredTable::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\StructuredTable::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\StructuredTable::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof DataObject\Data\StructuredTable) {
            $data = $value->getData();

            return $data;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $table = new DataObject\Data\StructuredTable();
            $table->setData($value);

            return $table;
        }

        return null;
    }
}
