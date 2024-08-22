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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Closure;
use Exception;
use JsonSerializable;
use LogicException;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use ReflectionMethod;

abstract class Data implements DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface, JsonSerializable
{
    use DataObject\ClassDefinition\Helper\VarExport;

    public ?string $name = null;

    public ?string $title = null;

    public ?string $tooltip = null;

    public bool $mandatory = false;

    public bool $noteditable = false;

    public bool $index = false;

    public bool $locked = false;

    public ?string $style = null;

    public array|string|null $permissions = null;

    /**
     * @deprecated Will be removed in Pimcore 12, use getFieldType() instead.
     */
    public string $fieldtype = '';

    public bool $relationType = false;

    public bool $invisible = false;

    public bool $visibleGridView = true;

    public bool $visibleSearch = true;

    public static array $validFilterOperators = [
        'LIKE',
        'NOT LIKE',
        '=',
        'IS',
        'IS NOT',
        '!=',
        '<',
        '>',
        '>=',
        '<=',
    ];

    //TODO remove childs in Pimcore 12
    /**
     * @var string[]
     */
    protected const FORBIDDEN_NAMES = [
        'apipluginbroker', 'baseobject', 'byid', 'bypath', 'cachekey', 'cachetag', 'cachetags', 'childamount',
        'childpermissions', 'children', 'childrensortby', 'childrensortorder', 'childs', 'class', 'classid',
        'classname', 'classtitle', 'closestparentofclass', 'creationdate', 'currentfullpath', 'dao', 'data',
        'definition', 'dependencies', 'dirtyfields', 'dirtylanguages', 'dodelete', 'fieldname', 'fullpath',
        'getinheritedvalues', 'haschildren', 'hassiblings', 'hideunpublished', 'id', 'idpath', 'index', 'key',
        'language', 'latestversion', 'lazyloadedfieldnames', 'list', 'listingcachekey', 'locked', 'modelfactory',
        'modificationdate', 'nextparentforinheritance', 'object', 'objectvar', 'objectvars', 'omitmandatorycheck',
        'parent', 'parentclass', 'parentid', 'path', 'permissions', 'permissionsforuser', 'properties', 'property',
        'published', 'realfullpath', 'realpath', 'relationdata', 'resource', 'scheduledtasks', 'siblings', 'type',
        'types', 'usermodification', 'userowner', 'userpermissions', 'validtablecolumns', 'value', 'valueforfieldname',
        'valuefromparent', 'values', 'versioncount', 'versions',
    ];

    /**
     * Returns the data for the editmode
     */
    abstract public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): mixed;

    /**
     * Converts data from editmode to internal eg. Image-Id to Asset\Image object
     */
    abstract public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): mixed;

    /**
     * Checks if data is valid for current data field
     *
     * @throws Exception
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        $isEmpty = true;

        // this is to do not treated "0" as empty
        if (is_string($data) || is_numeric($data)) {
            if (strlen($data) > 0) {
                $isEmpty = false;
            }
        }

        if (!empty($data)) {
            $isEmpty = false;
        }

        if (!$omitMandatoryCheck && $this->getMandatory() && $isEmpty) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @internal
     */
    public function getForCsvExport(DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object, array $params = []): string
    {
        return $this->getDataFromObjectParam($object, $params) ?? '';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        // this is the default, but csv doesn't work for all data types
        return $this->getForCsvExport($object, $params);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function getMandatory(): bool
    {
        return $this->mandatory;
    }

    public function getPermissions(): array|string|null
    {
        return $this->permissions;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return $this
     */
    public function setMandatory(bool $mandatory): static
    {
        $this->mandatory = $mandatory;

        return $this;
    }

    /**
     * @return $this
     */
    public function setPermissions(array|string|null $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @return $this
     */
    public function setValues(array $data = [], array $blockedKeys = []): static
    {
        foreach ($data as $key => $value) {
            if (isset($value) && !in_array($key, $blockedKeys)) {
                $method = 'set' . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }

        return $this;
    }

    abstract public function getFieldType(): string;

    public function getNoteditable(): bool
    {
        return $this->noteditable;
    }

    /**
     * @return $this
     */
    public function setNoteditable(bool $noteditable): static
    {
        $this->noteditable = $noteditable;

        return $this;
    }

    public function getIndex(): bool
    {
        return $this->index;
    }

    /**
     * @return $this
     */
    public function setIndex(?bool $index): static
    {
        if (null === $index) {
            trigger_deprecation(
                'pimcore/pimcore',
                '11.0.7',
                sprintf('Passing null to method %s is deprecated', __METHOD__)
            );
            $index = false;
        }
        $this->index = $index;

        return $this;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @return $this
     */
    public function setStyle(?string $style): static
    {
        $this->style = $style;

        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @return $this
     */
    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    /**
     * @return $this
     */
    public function setTooltip(?string $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function isRelationType(): bool
    {
        return $this->relationType;
    }

    public function getInvisible(): bool
    {
        return $this->invisible;
    }

    /**
     * @return $this
     */
    public function setInvisible(bool $invisible): static
    {
        $this->invisible = $invisible;

        return $this;
    }

    public function getVisibleGridView(): bool
    {
        return $this->visibleGridView;
    }

    /**
     * @return $this
     */
    public function setVisibleGridView(bool $visibleGridView): static
    {
        $this->visibleGridView = $visibleGridView;

        return $this;
    }

    public function getVisibleSearch(): bool
    {
        return $this->visibleSearch;
    }

    /**
     * @return $this
     */
    public function setVisibleSearch(bool $visibleSearch): static
    {
        $this->visibleSearch = $visibleSearch;

        return $this;
    }

    public function getCacheTags(mixed $data, array $tags = []): array
    {
        return $tags;
    }

    public function resolveDependencies(mixed $data): array
    {
        return [];
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     */
    public function getFilterCondition(mixed $value, string $operator, array $params = []): string
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
     * @param array $params optional params used to change the behavior
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        if (is_array($value) && empty($value)) {
            return '';
        }

        $db = \Pimcore\Db::get();
        $name = $params['name'] ?: $this->name;
        $key = $db->quoteIdentifier($name);
        $isNumeric = false;
        if (!empty($params['brickPrefix'])) {
            $key = $params['brickPrefix'].$key;
        }

        if ($operator === 'in') {
            $formattedValues = implode(',', array_map(floatval(...), explode(',', $value)));

            return $key . ' ' . $operator . ' (' . $formattedValues . ')';
        }

        if ($this instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue) {
            if ($this->elementType === 'date') {
                $dateFormat = 'Y-m-d H:i:s';
                $startDate = new \Carbon\Carbon($value);
                if ($operator === '=') {
                    $maxTime = $startDate->addDay();
                    $endDate = new \Carbon\Carbon($maxTime);
                    $operator = ' BETWEEN ' . $db->quote($startDate->format($dateFormat));
                    $operator .= ' AND ' . $db->quote($endDate->format($dateFormat));

                    return $key . ' ' . $operator;
                } else {
                    return $key . ' ' . $operator . ' ' . $db->quote($startDate->format($dateFormat));
                }
            }

            if ($this->elementType === 'boolean') {
                if ($this->calculatorType === 'class') {
                    $bool = $value === 1 ? 1 : 0;
                } else {
                    $bool = $value === 1 ? $db->quote('true') : $db->quote('false');
                }

                return $key . ' ' . $operator . ' ' . $bool;
            }

            if ($this->elementType === 'numeric') {
                $isNumeric = true;
            }
        }

        if (!$isNumeric) {
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
        }

        if (in_array($operator, DataObject\ClassDefinition\Data::$validFilterOperators)) {
            $trailer = '';
            //the db interprets 0 as NULL -> if empty (0) is selected in the filter, we must also filter for NULL
            if ($value === '\'0\'' || is_array($value) && in_array(0, $value)) {
                $trailer = ' OR ' . $key . ' IS NULL';
            }

            if (str_contains($name, 'cskey') && is_array($value) && !empty($value)) {
                $values = array_map(function ($val) use ($db) {
                    return $db->quote(Helper::escapeLike($val));
                }, $value);

                return $key . ' ' . $operator . ' ' . implode(' OR ' . $key . ' ' . $operator . ' ', $values) . $trailer;
            }

            return $key . ' ' . $operator . ' ' . $value . ' ' . $trailer;
        }

        return '';
    }

    protected function getPreGetValueHookCode(string $key): string
    {
        $code = "\t" . 'if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {' . "\n";
        $code .= "\t\t" . '$preValue = $this->preGetValue("' . $key . '");' . "\n";
        $code .= "\t\t" . 'if ($preValue !== null) {' . "\n";
        $code .= "\t\t\t" . 'return $preValue;' . "\n";
        $code .= "\t\t" . '}' . "\n";
        $code .= "\t" . '}' . "\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for object classes using this data type
     */
    public function getGetterCode(DataObject\ClassDefinition|DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition $class): string
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

        if ($this instanceof DataObject\ClassDefinition\Data\PreGetDataInterface) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n\n";
        }

        // insert this line if inheritance from parent objects is allowed
        if ($class instanceof DataObject\ClassDefinition && $class->getAllowInherit() && $this->supportsInheritance()) {
            $code .= "\t" . 'if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("' . $key . '")->isEmpty($data)) {' . "\n";
            $code .= "\t\t" . 'try {' . "\n";
            $code .= "\t\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
            $code .= "\t\t" . '} catch (InheritanceParentNotFoundException $e) {' . "\n";
            $code .= "\t\t\t" . '// no data from parent available, continue ...' . "\n";
            $code .= "\t\t" . '}' . "\n";
            $code .= "\t" . '}' . "\n\n";
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . 'return $data->getPlain();' . "\n";
        $code .= "\t" . '}' . "\n\n";

        $code .= "\t" . 'return $data;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for object classes using this data type
     */
    public function getSetterCode(DataObject\Objectbrick\Definition|DataObject\ClassDefinition|DataObject\Fieldcollection\Definition $class): string
    {
        if ($class instanceof DataObject\Objectbrick\Definition) {
            $classname = 'Objectbrick\\Data\\' . ucfirst($class->getKey());
        } elseif ($class instanceof DataObject\Fieldcollection\Definition) {
            $classname = 'Fieldcollection\\Data\\' . ucfirst($class->getKey());
        } else {
            $classname = $class->getName();
        }

        $key = $this->getName();

        if ($this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface && $this->getParameterTypeDeclaration()) {
            $typeDeclaration = $this->getParameterTypeDeclaration() . ' ';
        } else {
            $typeDeclaration = '';
        }

        $code = '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return $this' . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . '(' . $typeDeclaration . '$' . $key . '): static' . "\n";
        $code .= '{' . "\n";

        if (
            (
                $this->supportsDirtyDetection() &&
                $this instanceof DataObject\ClassDefinition\Data\EqualComparisonInterface
            ) || method_exists($this, 'preSetData')
        ) {
            $code .= "\t" . '/** @var \\' . static::class . ' $fd */' . "\n";
            $code .= "\t" . '$fd = $this->getClass()->getFieldDefinition("' . $key . '");' . "\n";
        }

        if ($this instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            if ($this->getDelegate()) {
                $code .= "\t" . '$encryptedFd = $this->getClass()->getFieldDefinition("' . $key . '");' . "\n";
                $code .= "\t" . '$delegate = $encryptedFd->getDelegate();' . "\n";
                $code .= "\t" . 'if ($delegate && !($' . $key . ' instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField)) {' . "\n";
                $code .= "\t\t" . '$' . $key . ' = new \\Pimcore\\Model\\DataObject\\Data\\EncryptedField($delegate, $' . $key . ');' . "\n";
                $code .= "\t" . '}' . "\n";
            }
        }

        if ($this->supportsDirtyDetection()) {
            $code .= "\t" . '$hideUnpublished = \\Pimcore\\Model\\DataObject\\Concrete::getHideUnpublished();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished(false);' . "\n";

            if ($class instanceof DataObject\ClassDefinition && $class->getAllowInherit()) {
                $code .= "\t" . '$currentData = \\Pimcore\\Model\\DataObject\\Service::useInheritedValues(false, function() {' . "\n";
                $code .= "\t\t" . 'return $this->get' . ucfirst($this->getName()) . '();' . "\n";
                $code .= "\t" . '});' . "\n";
            } else {
                $code .= "\t" . '$currentData = $this->get' . ucfirst($this->getName()) . '();' . "\n";
            }

            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished($hideUnpublished);' . "\n";

            if ($this instanceof DataObject\ClassDefinition\Data\EqualComparisonInterface) {
                $code .= "\t" . '$isEqual = $fd->isEqual($currentData, $' . $key . ');' . "\n";
                $code .= "\t" . 'if (!$isEqual) {' . "\n";
                $code .= "\t\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
                $code .= "\t" . '}' . "\n";
            } else {
                $code .= "\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
            }
        }

        if ($this instanceof DataObject\ClassDefinition\Data\PreSetDataInterface) {
            $code .= "\t" . '$this->' . $key . ' = ' . '$fd->preSetData($this, $' . $key . ');' . "\n";
        } else {
            $code .= "\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n\n";
            $code .= "\t" . '$this->' . $key . ' = ' . '$' . $key . ";\n\n";
        }

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for object brick classes using this data type
     */
    public function getGetterCodeObjectbrick(DataObject\Objectbrick\Definition $brickClass): string
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

        if ($this instanceof DataObject\ClassDefinition\Data\PreGetDataInterface) {
            $code .= "\t" . '$data = $this->getDefinition()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        if ($this->supportsInheritance()) {
            $code .= "\t" . 'if(\Pimcore\Model\DataObject::doGetInheritedValues($this->getObject()) && $this->getDefinition()->getFieldDefinition("' . $key . '")->isEmpty($data)) {' . "\n";
            $code .= "\t\t" . 'try {' . "\n";
            $code .= "\t\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
            $code .= "\t\t" . '} catch (InheritanceParentNotFoundException $e) {' . "\n";
            $code .= "\t\t\t" . '// no data from parent available, continue ...' . "\n";
            $code .= "\t\t" . '}' . "\n";
            $code .= "\t" . '}' . "\n";
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . 'return $data->getPlain();' . "\n";
        $code .= "\t" . '}' . "\n\n";

        $code .= "\t" . 'return $data;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for object brick classes using this data type
     */
    public function getSetterCodeObjectbrick(DataObject\Objectbrick\Definition $brickClass): string
    {
        $key = $this->getName();

        if ($this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface && $this->getParameterTypeDeclaration()) {
            $typeDeclaration = $this->getParameterTypeDeclaration() . ' ';
        } else {
            $typeDeclaration = '';
        }

        $code = '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return $this' . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . $typeDeclaration . '$' . $key . '): static' . "\n";
        $code .= '{' . "\n";

        if (
            (
                $this->supportsDirtyDetection() &&
                $this instanceof DataObject\ClassDefinition\Data\EqualComparisonInterface
            ) || method_exists($this, 'preSetData')
        ) {
            $code .= "\t" . '/** @var \\' . static::class . ' $fd */' . "\n";
            $code .= "\t" . '$fd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";
        }

        if ($this instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            if ($this->getDelegate()) {
                $code .= "\t" . '/** @var \\' . static::class . ' $encryptedFd */' . "\n";
                $code .= "\t" . '$encryptedFd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";
                $code .= "\t" . '$delegate = $encryptedFd->getDelegate();' . "\n";
                $code .= "\t" . 'if ($delegate && !($' . $key . ' instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField)) {' . "\n";
                $code .= "\t\t" . '$' . $key . ' = new \\Pimcore\\Model\\DataObject\\Data\\EncryptedField($delegate, $' . $key . ');' . "\n";
                $code .= "\t" . '}' . "\n";
            }
        }

        if ($this->supportsDirtyDetection()) {
            $code .= "\t" . '$class = $this->getObject() ? $this->getObject()->getClass() : null;' . "\n";
            $code .= "\t" . '$hideUnpublished = \\Pimcore\\Model\\DataObject\\Concrete::getHideUnpublished();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished(false);' . "\n";

            $code .= "\t" . 'if ($class && $class->getAllowInherit()) {' . "\n";
            $code .= "\t\t" . '$currentData = \\Pimcore\\Model\\DataObject\\Service::useInheritedValues(false, function() {' . "\n";
            $code .= "\t\t\t" . 'return $this->get' . ucfirst($this->getName()) . '();' . "\n";
            $code .= "\t\t" . '});' . "\n";
            $code .= "\t" . '}'."\n";
            $code .= "\t" . 'else {' . "\n";
            $code .= "\t\t" . '$currentData = $this->get' . ucfirst($this->getName()) . '();' . "\n";
            $code .= "\t" . '}';
            $code .= "\t" . '' . "\n";

            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished($hideUnpublished);' . "\n";

            if ($this instanceof DataObject\ClassDefinition\Data\EqualComparisonInterface) {
                $code .= "\t" . '$isEqual = $fd->isEqual($currentData, $' . $key . ');' . "\n";
                $code .= "\t" . 'if (!$isEqual) {' . "\n";
                $code .= "\t\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
                $code .= "\t" . '}' . "\n";
            } else {
                $code .= "\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
            }
        }

        if ($this instanceof DataObject\ClassDefinition\Data\PreSetDataInterface) {
            $code .= "\t" . '$this->' . $key . ' = ' . '$fd->preSetData($this, $' . $key . ');' . "\n";
        } else {
            $code .= "\t" . '$this->' . $key . ' = ' . '$' . $key . ";\n\n";
        }

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for fieldcollectionk classes using this data type
     */
    public function getGetterCodeFieldcollection(DataObject\Fieldcollection\Definition $fieldcollectionDefinition): string
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

        if ($this instanceof DataObject\ClassDefinition\Data\PreGetDataInterface) {
            $code .= "\t" . '$container = $this;' . "\n";
            $code .= "\t" . '/** @var \\' . static::class . ' $fd */' . "\n";
            $code .= "\t" . '$fd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";
            $code .= "\t" . '$data = $fd->preGetData($container);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . 'return $data->getPlain();' . "\n";
        $code .= "\t" . '}' . "\n\n";

        $code .= "\t" . 'return $data;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for fieldcollection classes using this data type
     */
    public function getSetterCodeFieldcollection(DataObject\Fieldcollection\Definition $fieldcollectionDefinition): string
    {
        $key = $this->getName();

        if ($this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface && $this->getParameterTypeDeclaration()) {
            $typeDeclaration = $this->getParameterTypeDeclaration() . ' ';
        } else {
            $typeDeclaration = '';
        }

        $code = '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return $this' . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . '(' . $typeDeclaration . '$' . $key . '): static' . "\n";
        $code .= '{' . "\n";

        if (
            (
                $this->supportsDirtyDetection() &&
                $this instanceof DataObject\ClassDefinition\Data\EqualComparisonInterface
            ) || method_exists($this, 'preSetData')
        ) {
            $code .= "\t" . '/** @var \\' . static::class . ' $fd */' . "\n";
            $code .= "\t" . '$fd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";
        }

        if ($this instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            if ($this->getDelegate()) {
                $code .= "\t" . '/** @var \\' . static::class . ' $encryptedFd */' . "\n";
                $code .= "\t" . '$encryptedFd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";
                $code .= "\t" . '$delegate = $encryptedFd->getDelegate();' . "\n";
                $code .= "\t" . 'if ($delegate && !($' . $key . ' instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField)) {' . "\n";
                $code .= "\t\t" . '$' . $key . ' = new \\Pimcore\\Model\\DataObject\\Data\\EncryptedField($delegate, $' . $key . ');' . "\n";
                $code .= "\t" . '}' . "\n";
            }
        }

        if ($this->supportsDirtyDetection()) {
            $code .= "\t" . '$hideUnpublished = \\Pimcore\\Model\\DataObject\\Concrete::getHideUnpublished();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished(false);' . "\n";
            $code .= "\t" . '$currentData = $this->get' . ucfirst($this->getName()) . '();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished($hideUnpublished);' . "\n";

            if ($this instanceof DataObject\ClassDefinition\Data\EqualComparisonInterface) {
                $code .= "\t" . '$isEqual = $fd->isEqual($currentData, $' . $key . ');' . "\n";
                $code .= "\t" . 'if (!$isEqual) {' . "\n";
                $code .= "\t\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
                $code .= "\t" . '}' . "\n";
            } else {
                $code .= "\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
            }
        }

        if ($this instanceof DataObject\ClassDefinition\Data\PreSetDataInterface) {
            $code .= "\t" . '$this->' . $key . ' = ' . '$fd->preSetData($this, $' . $key . ');' . "\n";
        } else {
            $code .= "\t" . '$this->' . $key . ' = ' . '$' . $key . ";\n\n";
        }

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for localized fields in classes using this data type
     */
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
        $code .= 'public function get' . ucfirst($key) . '(?string $language = null)' . $typeDeclaration . "\n";
        $code .= '{' . "\n";

        $code .= "\t" . '$data = $this->getLocalizedfields()->getLocalizedValue("' . $key . '", $language);' . "\n";

        if (!$class instanceof DataObject\Fieldcollection\Definition) {
            $code .= $this->getPreGetValueHookCode($key);
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . 'return $data->getPlain();' . "\n";
        $code .= "\t" . '}' . "\n\n";

        // we don't need to consider preGetData, because this is already managed directly by the localized fields within getLocalizedValue()

        $code .= "\t" . 'return $data;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for localized fields in classes using this data type
     */
    public function getSetterCodeLocalizedfields(DataObject\Objectbrick\Definition|DataObject\ClassDefinition|DataObject\Fieldcollection\Definition $class): string
    {
        $key = $this->getName();
        if ($class instanceof DataObject\Objectbrick\Definition) {
            $classname = 'Objectbrick\\Data\\' . ucfirst($class->getKey());
            $containerGetter = 'getDefinition';
        } elseif ($class instanceof DataObject\Fieldcollection\Definition) {
            $classname = 'Fieldcollection\\Data\\' . ucfirst($class->getKey());
            $containerGetter = 'getDefinition';
        } else {
            $classname = $class->getName();
            $containerGetter = 'getClass';
        }

        if ($this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface && $this->getParameterTypeDeclaration()) {
            $typeDeclaration = $this->getParameterTypeDeclaration() . ' ';
        } else {
            $typeDeclaration = '';
        }

        $code = '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return $this' . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . $typeDeclaration . '$' . $key . ', ?string $language = null): static' . "\n";
        $code .= '{' . "\n";

        if ($this->supportsDirtyDetection()) {
            $code .= "\t" . '$fd = $this->' . $containerGetter . '()->getFieldDefinition("localizedfields")->getFieldDefinition("' . $key . '");' . "\n";
        }

        if ($this instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            if ($this->getDelegate()) {
                $code .= "\t" . '$encryptedFd = $this->getClass()->getFieldDefinition("' . $key . '");' . "\n";
                $code .= "\t" . '$delegate = $encryptedFd->getDelegate();' . "\n";
                $code .= "\t" . 'if ($delegate && !($' . $key . ' instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField)) {' . "\n";
                $code .= "\t\t" . '$' . $key . ' = new \\Pimcore\\Model\\DataObject\\Data\\EncryptedField($delegate, $' . $key . ');' . "\n";
                $code .= "\t" . '}' . "\n";
            }
        }

        if ($this->supportsDirtyDetection()) {
            $code .= "\t" . '$hideUnpublished = \\Pimcore\\Model\\DataObject\\Concrete::getHideUnpublished();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished(false);' . "\n";

            if ($class instanceof DataObject\ClassDefinition && $class->getAllowInherit()) {
                $code .= "\t" . '$currentData = \\Pimcore\\Model\\DataObject\\Service::useInheritedValues(false, function() use ($language) {' . "\n";
                $code .= "\t\t" . 'return $this->get' . ucfirst($this->getName()) . '($language);' . "\n";
                $code .= "\t" . '});' . "\n";
            } else {
                $code .= "\t" . '$currentData = $this->get' . ucfirst($this->getName()) . '($language);' . "\n";
            }
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished($hideUnpublished);' . "\n";

            if ($this instanceof DataObject\ClassDefinition\Data\EqualComparisonInterface) {
                $code .= "\t" . '$isEqual = $fd->isEqual($currentData, $' . $key . ');' . "\n";
            } else {
                $code .= "\t" . '$isEqual = false;' . "\n";
            }

            $code .= "\t" . 'if (!$isEqual) {' . "\n";
            $code .= "\t\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
            $code .= "\t" . '}' . "\n";
        } else {
            $code .= "\t" . '$isEqual = false;' . "\n";
        }

        $code .= "\t" . '$this->getLocalizedfields()->setLocalizedValue("' . $key . '", $' . $key . ', $language, !$isEqual)' . ";\n\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates filter method code for listing classes
     */
    public function getFilterCode(): string
    {
        $key = $this->getName();

        $code = '/**' . "\n";
        $code .= '* Filter by ' . str_replace(['/**', '*/', '//'], '', $key) . ' (' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . ")\n";

        $dataParamDoc = 'mixed $data';
        $reflectionMethod = new ReflectionMethod($this, 'addListingFilter');
        $docComment = $reflectionMethod->getDocComment();
        if ($docComment && preg_match('/@param\s+([^\s]+)\s+\$data(.*)/', $docComment, $dataParam)) {
            $dataParamDoc = $dataParam[1].' $data '.$dataParam[2];
        }

        $operatorParamDoc = 'string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"';
        if ($docComment && preg_match('/@param\s+([^\s]+)\s+\$operator(.*)/', $docComment, $dataParam)) {
            $operatorParamDoc = $dataParam[1].' $operator '.$dataParam[2];
        }

        $code .= '* @param '.$dataParamDoc."\n";
        $code .= '* @param '.$operatorParamDoc."\n";
        $code .= '* @return $this'."\n";
        $code .= '*/' . "\n";

        $code .= 'public function filterBy' . ucfirst($key) .' ($data, $operator = \'=\'): static' . "\n";
        $code .= '{' . "\n";
        $code .= "\t" . '$this->getClass()->getFieldDefinition("' . $key . '")->addListingFilter($this, $data, $operator);' . "\n";
        $code .= "\treturn " . '$this' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * @deprecated Will be removed in Pimcore 12
     */
    public function getAsIntegerCast(mixed $number): ?int
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.2',
            'Using "%s" is deprecated and will be removed in Pimcore 12.',
            __METHOD__
        );

        return strlen((string) $number) === 0 ? null : (int)$number;
    }

    /**
     * @deprecated Will be removed in Pimcore 12
     */
    public function getAsFloatCast(mixed $number): ?float
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.2',
            'Using "%s" is deprecated and will be removed in Pimcore 12.',
            __METHOD__
        );

        return strlen((string) $number) === 0 ? null : (float)$number;
    }

    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return 'no preview';
    }

    public function isEmpty(mixed $data): bool
    {
        return empty($data);
    }

    /** True if change is allowed in edit mode.
     *
     */
    public function isDiffChangeAllowed(DataObject\Concrete $object, array $params = []): bool
    {
        return false;
    }

    /** Converts the data sent from the object merger back to the internal object. Similar to
     * getDiffDataForEditMode() an array of data elements is passed in containing the following attributes:
     *  - "field" => the name of (this) field
     *  - "key" => the key of the data element
     *  - "data" => the data
     */
    public function getDiffDataFromEditmode(array $data, DataObject\Concrete $object = null, array $params = []): mixed
    {
        $thedata = $this->getDataFromEditmode($data[0]['data'], $object, $params);

        return $thedata;
    }

    /**
     * Returns the data for the editmode in the format expected by the object merger plugin.
     * The return value is a list of data definitions containing the following attributes:
     *      - "field" => the name of the object field
     *      - "key" => a unique key identifying the data element
     *      - "type" => the type of the data component
     *      - "value" => the value used as preview
     *      - "data" => the actual data which is then sent back again by the editor. Note that the data is opaque
     *                          and will not be touched by the editor in any way.
     *      - "disabled" => whether the data element can be edited or not
     *      - "title" => pretty name describing the data element
     */
    public function getDiffDataForEditMode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $diffdata = [];
        $diffdata['data'] = $this->getDataForEditmode($data, $object, $params);
        $diffdata['disabled'] = !($this->isDiffChangeAllowed($object));
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->getFieldType();

        if (method_exists($this, 'getDiffVersionPreview')) {
            $value = $this->getDiffVersionPreview($data, $object, $params);
        } else {
            $value = $this->getVersionPreview($data, $object, $params);
        }

        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;
        $diffdata['value'] = $value;

        $result = [];
        $result[] = $diffdata;

        return $result;
    }

    public function getUnique(): bool
    {
        return false;
    }

    /**
     * @throws Exception
     */
    protected function getDataFromObjectParam(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): mixed
    {
        $data = null;

        if (array_key_exists('injectedData', $params)) {
            return $params['injectedData'];
        }

        $context = $params['context'] ?? null;

        if (isset($context['containerType'])) {
            if ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'block') {
                if ($this instanceof DataObject\ClassDefinition\Data\Localizedfields || $object instanceof DataObject\Localizedfield) {
                    $fieldname = $context['fieldname'];
                    $index = $context['index'] ?? null;

                    if ($object instanceof DataObject\Concrete) {
                        $containerGetter = 'get' . ucfirst($fieldname);
                        $container = $object->$containerGetter();
                        if (!$container && $context['containerType'] === 'block') {
                            // no data, so check if inheritance is enabled + there is parent value
                            if ($object->getClass()->getAllowInherit()) {
                                try {
                                    $container = $object->getValueFromParent($fieldname);
                                } catch (InheritanceParentNotFoundException $e) {
                                    //nothing to do here - just no parent data available
                                }
                            }
                        }

                        if ($container) {
                            $originalIndex = $context['oIndex'] ?? null;

                            // field collection or block items
                            if ($originalIndex !== null) {
                                if ($context['containerType'] === 'block') {
                                    $items = $container;
                                } else {
                                    $items = $container->getItems();
                                }

                                if ($items && (count($items) > $originalIndex || count($items) > --$originalIndex)) {
                                    $item = $items[$originalIndex];

                                    if ($context['containerType'] === 'block') {
                                        $data = $item[$this->getName()] ?? null;
                                        if ($data instanceof DataObject\Data\BlockElement) {
                                            $data = $data->getData();

                                            return $data;
                                        }
                                    } else {
                                        $getter = 'get' . ucfirst($this->getName());
                                        $data = $item->$getter();
                                    }

                                    return $data;
                                }

                                throw new Exception('object seems to be modified, item with orginal index ' . $originalIndex . ' not found, new index: ' . $index);
                            } else {
                                return null;
                            }
                        } else {
                            return null;
                        }
                    } elseif ($object instanceof DataObject\Localizedfield) {
                        $data = $object->getLocalizedValue($this->getName(), $params['language'], true);

                        return $data;
                    }
                }
            } elseif ($context['containerType'] === 'objectbrick' && ($this instanceof DataObject\ClassDefinition\Data\Localizedfields || $object instanceof DataObject\Localizedfield)) {
                $fieldname = $context['fieldname'];

                if ($object instanceof DataObject\Concrete) {
                    $containerGetter = 'get' . ucfirst($fieldname);
                    $container = $object->$containerGetter();
                    if ($container) {
                        $brickGetter = 'get' . ucfirst($context['containerKey']);
                        $brickData = $container->$brickGetter();

                        if ($brickData instanceof DataObject\Objectbrick\Data\AbstractData) {
                            return $brickData->get('localizedfields');
                        }
                    }

                    return null;
                } elseif ($object instanceof DataObject\Localizedfield) {
                    $data = $object->getLocalizedValue($this->getName(), $params['language'], true);

                    return $data;
                }
            } elseif ($context['containerType'] === 'classificationstore') {
                $fieldname = $context['fieldname'];
                $getter = 'get' . ucfirst($fieldname);
                if (method_exists($object, $getter)) {
                    $groupId = $context['groupId'];
                    $keyId = $context['keyId'];
                    $language = $context['language'];

                    /** @var DataObject\Classificationstore $classificationStoreData */
                    $classificationStoreData = $object->$getter();
                    $data = $classificationStoreData->getLocalizedKeyValue($groupId, $keyId, $language, true, true);

                    return $data;
                }
            }
        }

        $container = $object;

        $getter = 'get' . ucfirst($this->getName());
        if (method_exists($container, $getter)) { // for DataObject\Concrete, DataObject\Fieldcollection\Data\AbstractData, DataObject\Objectbrick\Data\AbstractData
            if (!isset($params['language']) || $params['language'] === 'default') {
                $data = $container->$getter();
            } else {
                $data = $container->$getter($params['language']);
            }
        } elseif ($object instanceof DataObject\Localizedfield) {
            $data = $object->getLocalizedValue($this->getName(), $params['language'], true);
        }

        return $data;
    }

    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        // implement in child classes
    }

    public function adoptMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $vars = get_object_vars($this);
        $protectedFields = ['noteditable', 'invisible'];
        foreach ($vars as $name => $value) {
            if (!in_array($name, $protectedFields)) {
                unset($this->$name);
            }
        }
        $vars = get_object_vars($mainDefinition);
        foreach ($vars as $name => $value) {
            if (!in_array($name, $protectedFields)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * @throws LogicException
     *
     * TODO Change return type to array in Pimcore 12
     */
    public function appendData(?array $existingData, array $additionalData): ?array
    {
        return $existingData;
    }

    /**
     * @throws LogicException
     *
     * TODO Change return type to array in Pimcore 12
     */
    public function removeData(?array $existingData, array $removeData): mixed
    {
        return $existingData;
    }

    /**
     * Returns if datatype supports data inheritance
     */
    public function supportsInheritance(): bool
    {
        return true;
    }

    public function supportsDirtyDetection(): bool
    {
        return false;
    }

    public function markLazyloadedFieldAsLoaded(Localizedfield|AbstractData|Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object): void
    {
        if ($object instanceof DataObject\LazyLoadedFieldsInterface) {
            $object->markLazyKeyAsLoaded($this->getName());
        }
    }

    /**
     * Returns if datatype supports listing filters: getBy, filterBy
     */
    public function isFilterable(): bool
    {
        return false;
    }

    /**
     * @param string|int|float|array|Model\Element\ElementInterface $data comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     */
    public function addListingFilter(DataObject\Listing $listing, float|array|int|string|Model\Element\ElementInterface $data, string $operator = '='): DataObject\Listing
    {
        return $listing->addFilterByField($this->getName(), $operator, $data);
    }

    public function isForbiddenName(): bool
    {
        $name = $this->getName();
        if ($name === null || $name === '') {
            return true;
        }
        $lowerCasedName = strtolower($name);
        if (
            !$this instanceof DataObject\ClassDefinition\Data\Localizedfields &&
            $lowerCasedName === 'localizedfields'
        ) {
            return true;
        }

        return in_array($lowerCasedName, self::FORBIDDEN_NAMES);
    }

    public function jsonSerialize(): mixed
    {
        $data = Closure::bind(fn ($obj) => get_object_vars($obj), null, null)($this); // only get public properties
        $data['fieldtype'] = $this->getFieldType();
        $data['datatype'] = 'data';
        unset($data['blockedVarsForExport']);

        return $data;
    }
}
