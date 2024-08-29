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
use Pimcore\Normalizer\NormalizerInterface;

class Checkbox extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\DefaultValueTrait;
    use DataObject\Traits\SimpleNormalizerTrait;

    /**
     * @internal
     *
     */
    public ?int $defaultValue = null;

    public function getDefaultValue(): ?int
    {
        return $this->defaultValue;
    }

    /**
     * @return $this
     */
    public function setDefaultValue(mixed $defaultValue): static
    {
        if (!is_numeric($defaultValue)) {
            $defaultValue = null;
        }
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * @param null|DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, Concrete $object = null, array $params = []): ?int
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        return is_null($data) ? null : (int)$data;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?bool
    {
        if (!is_null($data)) {
            $data = (bool) $data;
        }

        return $data;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?int
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?int
    {
        return $this->getDataForResource($data, $object, $params);
    }

    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?bool
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return (string)$data;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $data === null) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }

        /* @todo seems to cause problems with old installations
        if(!is_bool($data) and $data !== 1 and $data !== 0){
        throw new \Exception(get_class($this).": invalid data");
        }*/
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params) ?? '';

        return (string)$data;
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Checkbox $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->defaultValue = $mainDefinition->defaultValue;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     *
     *
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
     *
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $db = \Pimcore\Db::get();
        $value = $db->quote($value);
        $key = $db->quoteIdentifier($this->name);

        $brickPrefix = $params['brickPrefix'] ? $db->quoteIdentifier($params['brickPrefix']) . '.' : '';

        return 'IFNULL(' . $brickPrefix . $key . ', 0) = ' . $value . ' ';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function isEmpty(mixed $data): bool
    {
        return $data === null;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    protected function doGetDefaultValue(Concrete $object, array $context = []): ?int
    {
        return $this->getDefaultValue() ?? null;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
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

    public function getColumnType(): string
    {
        return 'tinyint(1)';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'checkbox';
    }
}
