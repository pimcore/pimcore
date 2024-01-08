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
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class Table extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use DataObject\Traits\SimpleNormalizerTrait;
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\DataWidthTrait;

    /**
     * @internal
     *
     */
    public ?int $cols = null;

    /**
     * @internal
     */
    public bool $colsFixed = false;

    /**
     * @internal
     *
     */
    public ?int $rows = null;

    /**
     * @internal
     */
    public bool $rowsFixed = false;

    /**
     * Default data
     *
     * @internal
     *
     */
    public string $data = '';

    /**
     * @internal
     */
    public bool $columnConfigActivated = false;

    /**
     * @internal
     *
     */
    public array $columnConfig = [];

    public function getCols(): ?int
    {
        return $this->cols;
    }

    public function setCols(?int $cols): static
    {
        $this->cols = $this->getAsIntegerCast($cols);

        return $this;
    }

    public function getRows(): ?int
    {
        return $this->rows;
    }

    public function setRows(?int $rows): static
    {
        $this->rows = $this->getAsIntegerCast($rows);

        return $this;
    }

    public function getRowsFixed(): bool
    {
        return $this->rowsFixed;
    }

    public function setRowsFixed(bool $rowsFixed): static
    {
        $this->rowsFixed = $rowsFixed;

        return $this;
    }

    public function getColsFixed(): bool
    {
        return $this->colsFixed;
    }

    public function setColsFixed(bool $colsFixed): static
    {
        $this->colsFixed = $colsFixed;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function isColumnConfigActivated(): bool
    {
        return $this->columnConfigActivated;
    }

    public function setColumnConfigActivated(bool $columnConfigActivated): void
    {
        $this->columnConfigActivated = $columnConfigActivated;
    }

    public function getColumnConfig(): array
    {
        return $this->columnConfig;
    }

    public function setColumnConfig(array $columnConfig): void
    {
        $this->columnConfig = $columnConfig;
    }

    protected function convertDataToValueArray(array $data): array
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
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
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
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): array
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
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): string
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
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if (is_array($data)) {
            //make sure only array values are used of edit mode (other wise ext stores do not work anymore)
            return $this->convertDataToValueArray($data);
        }

        return $data;
    }

    public function getDataForGrid(?array $data, Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
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
     * @param null|DataObject\Concrete $object
     *
     */
    public function getDataFromGridEditor(array $data, Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        $versionPreview = $this->getDiffVersionPreview($data, $object, $params);
        if (is_array($versionPreview) && $versionPreview['html']) {
            return $versionPreview['html'];
        }

        return '';
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!empty($data) && !is_array($data)) {
            throw new Model\Element\ValidationException('Invalid table data');
        }
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            return json_encode($data);
        }

        return '';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
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

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDiffVersionPreview(?array $data, Concrete $object = null, array $params = []): array|string
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
                        $html .= htmlspecialchars($cell ?? '', ENT_QUOTES, 'UTF-8');
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
     * @param DataObject\ClassDefinition\Data\Table $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->cols = $mainDefinition->cols;
        $this->colsFixed = $mainDefinition->colsFixed;
        $this->rows = $mainDefinition->rows;
        $this->rowsFixed = $mainDefinition->rowsFixed;
        $this->data = $mainDefinition->data;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $this->isEqualArray($oldValue, $newValue);
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return 'array';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'array|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'array';
    }

    public function getGetterCode(DataObject\Objectbrick\Definition|DataObject\ClassDefinition|DataObject\Fieldcollection\Definition $class): string
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

    public function getGetterCodeObjectbrick(\Pimcore\Model\DataObject\Objectbrick\Definition $brickClass): string
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

    public function getGetterCodeFieldcollection(Definition $fieldcollectionDefinition): string
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

    public function getGetterCodeLocalizedfields(DataObject\Objectbrick\Definition|DataObject\ClassDefinition|DataObject\Fieldcollection\Definition $class): string
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
        $code .= 'public function get' . ucfirst($key) . ' (?string $language = null)' . $typeDeclaration . "\n";
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

    public function getColumnType(): string
    {
        return 'longtext';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'table';
    }
}
