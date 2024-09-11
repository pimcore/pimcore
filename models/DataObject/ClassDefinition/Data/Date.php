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

use Carbon\Carbon;
use DateTimeInterface;
use Pimcore\Db;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\UserTimezone;

class Date extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\DefaultValueTrait;

    /**
     * @internal
     *
     */
    public ?int $defaultValue = null;

    /**
     * @internal
     */
    public bool $useCurrentDate = false;

    /**
     * @internal
     */
    public string $columnType = 'bigint(20)';

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): int|string|null
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        if ($data) {
            $result = $data->getTimestamp();
            if ($this->getColumnType() == 'date') {
                $result = date('Y-m-d', $result);
            }

            return $result;
        }

        return null;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?Carbon
    {
        if ($data) {
            if ($this->getColumnType() == 'date') {
                $data = strtotime($data);
                if ($data === false) {
                    return null;
                }
            }

            $result = $this->getDateFromTimestamp($data);

            return $result;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): int|null|string
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
        if ($data) {
            return $data->getTimestamp();
        }

        return null;
    }

    private function getDateFromTimestamp(float|int|string $timestamp): Carbon
    {
        $date = new Carbon();
        $date->setTimestamp($timestamp);

        return $date;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?Carbon
    {
        if (is_numeric($data)) {
            return $this->getDateFromTimestamp($data / 1000);
        }

        if (is_string($data)) {
            return Carbon::parse($data);
        }

        return null;
    }

    /**
     * @param Model\DataObject\Concrete|null $object
     *
     */
    public function getDataFromGridEditor(float|string $data, Concrete $object = null, array $params = []): ?Carbon
    {
        if ($data && is_float($data)) {
            $data = $data * 1000;
        }

        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDataForGrid(?Carbon $data, Concrete $object = null, array $params = []): ?int
    {
        if ($data) {
            return $data->getTimestamp();
        }

        return null;
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        if ($data instanceof DateTimeInterface) {
            return $this->applyTimezone($data)->format('Y-m-d');
        }

        return '';
    }

    public function getDefaultValue(): int
    {
        if ($this->defaultValue !== null) {
            return $this->defaultValue;
        }

        return 0;
    }

    /**
     * @return $this
     */
    public function setDefaultValue(mixed $defaultValue): static
    {
        if (strlen((string)$defaultValue) > 0) {
            if (is_numeric($defaultValue)) {
                $this->defaultValue = (int)$defaultValue;
            } else {
                $this->defaultValue = strtotime($defaultValue);
            }
        }

        return $this;
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DateTimeInterface) {
            return $this->applyTimezone($data)->format('Y-m-d');
        }

        return '';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    /**
     * @return $this
     */
    public function setUseCurrentDate(bool $useCurrentDate): static
    {
        $this->useCurrentDate = $useCurrentDate;

        return $this;
    }

    public function isUseCurrentDate(): bool
    {
        return $this->useCurrentDate;
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    public function getDiffDataFromEditmode(array $data, DataObject\Concrete $object = null, array $params = []): ?Carbon
    {
        $thedata = $data[0]['data'];
        if ($thedata) {
            return $this->getDateFromTimestamp($thedata);
        }

        return null;
    }

    /** See parent class.
     *
     */
    public function getDiffDataForEditMode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $result = [];

        $thedata = null;
        if ($data) {
            $thedata = $data->getTimestamp();
        }
        $diffdata = [];
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->getFieldType();
        $diffdata['value'] = $this->getVersionPreview($data, $object, $params);
        $diffdata['data'] = $thedata;
        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;
        $diffdata['disabled'] = false;

        $result[] = $diffdata;

        return $result;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param array $params optional params used to change the behavior
     *
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $timestamp = $value;

        if ($this->getColumnType() == 'date') {
            $value = date('Y-m-d', $value);
        }

        if ($operator == '=') {
            $db = Db::get();

            if ($this->getColumnType() == 'date') {
                $condition = $db->quoteIdentifier($params['name']) . ' = '. $db->quote($value);

                return $condition;
            } else {
                $maxTime = $timestamp + (86400 - 1); //specifies the top point of the range used in the condition
                $filterField = $params['name'] ? $params['name'] : $this->getName();
                $condition = '`' . $filterField . '` BETWEEN ' . $db->quote($value) . ' AND ' . $db->quote($maxTime);

                return $condition;
            }
        }

        return parent::getFilterConditionExt($value, $operator, $params);
    }

    public function isFilterable(): bool
    {
        return true;
    }

    protected function doGetDefaultValue(Concrete $object, array $context = []): ?Carbon
    {
        if ($this->getDefaultValue()) {
            $date = new \Carbon\Carbon();
            $date->setTimestamp($this->getDefaultValue());

            return $date;
        } elseif ($this->isUseCurrentDate()) {
            return new \Carbon\Carbon();
        }

        return null;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $oldValue instanceof DateTimeInterface ? $oldValue->format('Y-m-d') : null;
        $newValue = $newValue instanceof DateTimeInterface ? $newValue->format('Y-m-d') : null;

        return $oldValue === $newValue;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Carbon::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Carbon::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . Carbon::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Carbon::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): mixed
    {
        if ($value instanceof Carbon) {
            return $value->getTimestamp();
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): mixed
    {
        if ($value !== null) {
            return $this->getDateFromTimestamp($value);
        }

        return null;
    }

    /**
     * overwrite default implementation to consider columnType & queryColumnType from class config
     *
     */
    public function resolveBlockedVars(): array
    {
        $defaultBlockedVars = [
            'fieldDefinitionsCache',
        ];

        return array_merge($defaultBlockedVars, $this->getBlockedVarsForExport());
    }

    public function getColumnType(): string
    {
        return $this->columnType;
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'date';
    }

    public function setColumnType(string $columnType): void
    {
        $this->columnType = $columnType;
    }

    private function applyTimezone(DateTimeInterface $date): DateTimeInterface
    {
        if ($this->columnType !== 'date') {
            $date = UserTimezone::applyTimezone($date);
        }

        return $date;
    }
}
