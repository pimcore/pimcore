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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class Table extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\Traits\SimpleNormalizerTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'table';

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
     * @var int|null
     */
    public $cols;

    /**
     * @internal
     *
     * @var bool
     */
    public $colsFixed = false;

    /**
     * @internal
     *
     * @var int|null
     */
    public $rows;

    /**
     * @internal
     *
     * @var bool
     */
    public $rowsFixed = false;

    /**
     * Default data
     *
     * @internal
     *
     * @var string
     */
    public $data = '';

    /**
     * @internal
     *
     * @var bool
     */
    public $columnConfigActivated = false;

    /**
     * @internal
     *
     * @var array
     */
    public $columnConfig = [];

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'longtext';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'longtext';

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
     * @return int|null
     */
    public function getCols()
    {
        return $this->cols;
    }

    /**
     * @param int|null $cols
     *
     * @return $this
     */
    public function setCols($cols)
    {
        $this->cols = $this->getAsIntegerCast($cols);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param int|null $rows
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
     * @param string|array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (empty($data)) {
            // if it is empty then there is no need to serialize anything
            return null;
        }

        if (is_array($data)) {
            //make sure only array values are stored to DB
            $data = $this->convertDataToValueArray($data);
        }

        return Serialize::serialize($data);
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        $unserializedData = Serialize::unserialize((string) $data);

        if ($data === null || empty($unserializedData)) {
            return [];
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
                if (isset($columnConfig[$index])) {
                    $indexedRow[$columnConfig[$index]['key']] = $col;
                }
                $index++;
            }

            $dataWithKeys[] = $indexedRow;
        }

        return $dataWithKeys;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param string|array $data
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
     * @param array|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
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
     * @param array|null $data
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
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!empty($data) && !is_array($data)) {
            throw new Model\Element\ValidationException('Invalid table data');
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
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
                    $html .= htmlspecialchars($columConfig[$index]['label'], ENT_QUOTES, 'UTF-8');
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
                        $html .= htmlspecialchars($cell, ENT_QUOTES, 'UTF-8');
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

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return 'array';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return 'array|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return 'array';
    }

    /**
     * {@inheritdoc}
     */
    public function getGetterCode($class)
    {
        $key = $this->getName();

        if ($this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface && $this->getReturnTypeDeclaration()) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $code = '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . '()' . $typeDeclaration . "\n";
        $code .= '{' . "\n";

        $code .= $this->getPreGetValueHookCode($key);

        if ($this instanceof  PreGetDataInterface) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n\n";
        }

        // insert this line if inheritance from parent objects is allowed
        if ($class instanceof DataObject\ClassDefinition && $class->getAllowInherit() && $this->supportsInheritance()) {
            $code .= "\t" . 'if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("' . $key . '")->isEmpty($data)) {' . "\n";
            $code .= "\t\t" . 'try {' . "\n";
            $code .= "\t\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
            $code .= "\t\t" . '} catch (InheritanceParentNotFoundException $e) {' . "\n";
            $code .= "\t\t\t" . '// no data from parent available, continue ...' . "\n";
            $code .= "\t\t" . '}' . "\n";
            $code .= "\t" . '}' . "\n\n";
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . 'return $data->getPlain() ?? [];' . "\n";
        $code .= "\t" . '}' . "\n\n";

        $code .= "\t" . 'return $data ?? [];' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function getGetterCodeObjectbrick($brickClass)
    {
        $key = $this->getName();

        if ($this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface && $this->getReturnTypeDeclaration()) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . '()' . $typeDeclaration . "\n";
        $code .= '{' . "\n";

        if ($this instanceof PreGetDataInterface) {
            $code .= "\t" . '$data = $this->getDefinition()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        if ($this->supportsInheritance()) {
            $code .= "\t" . 'if (\Pimcore\Model\DataObject::doGetInheritedValues($this->getObject()) && $this->getDefinition()->getFieldDefinition("' . $key . '")->isEmpty($data)) {' . "\n";
            $code .= "\t\t" . 'try {' . "\n";
            $code .= "\t\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
            $code .= "\t\t" . '} catch (InheritanceParentNotFoundException $e) {' . "\n";
            $code .= "\t\t\t" . '// no data from parent available, continue ...' . "\n";
            $code .= "\t\t" . '}' . "\n";
            $code .= "\t" . '}' . "\n";
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . 'return $data->getPlain() ?? [];' . "\n";
        $code .= "\t" . '}' . "\n\n";

        $code .= "\t" . 'return $data ?? [];' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function getGetterCodeFieldcollection($fieldcollectionDefinition)
    {
        $key = $this->getName();

        if ($this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface && $this->getReturnTypeDeclaration()) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . '()' . $typeDeclaration . "\n";
        $code .= '{' . "\n";

        if ($this instanceof PreGetDataInterface) {
            $code .= "\t" . '$container = $this;' . "\n";
            $code .= "\t" . '/** @var \\' . static::class . ' $fd */' . "\n";
            $code .= "\t" . '$fd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";
            $code .= "\t" . '$data = $fd->preGetData($container);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . 'return $data->getPlain() ?? [];' . "\n";
        $code .= "\t" . '}' . "\n";

        $code .= "\t" . 'return $data ?? [];' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function getGetterCodeLocalizedfields($class)
    {
        $key = $this->getName();

        if ($this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface && $this->getReturnTypeDeclaration()) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $code = '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . ' ($language = null)' . $typeDeclaration . "\n";
        $code .= '{' . "\n";

        $code .= "\t" . '$data = $this->getLocalizedfields()->getLocalizedValue("' . $key . '", $language);' . "\n";

        if (!$class instanceof DataObject\Fieldcollection\Definition) {
            $code .= $this->getPreGetValueHookCode($key);
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . 'return $data->getPlain() ?? [];' . "\n";
        $code .= "\t" . '}' . "\n";

        // we don't need to consider preGetData, because this is already managed directly by the localized fields within getLocalizedValue()

        $code .= "\treturn " . '$data ?? []' . ";\n";
        $code .= "}\n\n";

        return $code;
    }
}
