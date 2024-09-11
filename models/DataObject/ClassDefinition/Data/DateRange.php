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
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Exception;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\UserTimezone;

class DateRange extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface
{
    use DataObject\Traits\DataWidthTrait;

    /**
     * @internal
     *
     * @var string[] $columnType
     */
    public array $columnType = [
        'start_date' => 'bigint(20)',
        'end_date' => 'bigint(20)',
    ];

    /**
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        $startDateKey = $this->getName() . '__start_date';
        $endDateKey = $this->getName() . '__end_date';

        if ($data instanceof CarbonPeriod) {
            $startDate = $data->getStartDate();
            $endDate = $data->getEndDate();

            $result = [
                $startDateKey =>  $startDate->getTimestamp(),
                $endDateKey => $endDate instanceof CarbonInterface ? $endDate->getTimestamp() : null,
            ];

            return $result;
        }

        return [
            $startDateKey => null,
            $endDateKey => null,
        ];
    }

    /**
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?CarbonPeriod
    {
        $startDateKey = $this->getName() . '__start_date';
        $endDateKey = $this->getName() . '__end_date';

        if (isset($data[$startDateKey], $data[$endDateKey])) {
            $startDate = $this->getDateFromTimestamp($data[$startDateKey]);
            $endDate = $this->getDateFromTimestamp($data[$endDateKey]);
            $period = CarbonPeriod::create()->setStartDate($startDate);

            if ($endDate instanceof Carbon) {
                $period->setEndDate($endDate);
            }

            return $period;
        }

        return null;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data instanceof CarbonPeriod) {
            $endDate = $data->getEndDate();

            return [
                'start_date' => $data->getStartDate()->getTimestamp(),
                'end_date' => $endDate instanceof CarbonInterface ? $endDate->getTimestamp() : null,
            ];
        }

        return null;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?CarbonPeriod
    {
        if (is_array($data) && isset($data['start_date'], $data['end_date'])) {
            $startDate = $this->getDateFromTimestamp($data['start_date'] / 1000);
            $endDate = $this->getDateFromTimestamp($data['end_date'] / 1000);

            return CarbonPeriod::create($startDate, $endDate);
        }

        return null;
    }

    public function getDataFromGridEditor(array $data, DataObject\Concrete $object = null, array $params = []): ?CarbonPeriod
    {
        if ($data['start_date']) {
            $data['start_date'] *= 1000;
        }

        if ($data['end_date']) {
            $data['end_date'] *= 1000;
        }

        return $this->getDataFromEditmode($data, $object, $params);
    }

    public function getDataForGrid(?CarbonPeriod $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        if ($data instanceof CarbonPeriod) {
            /** @var CarbonInterface $startDate */
            $startDate = UserTimezone::applyTimezone($data->getStartDate());
            /** @var CarbonInterface $endDate */
            $endDate = UserTimezone::applyTimezone($data->getEndDate());

            return 'From ' . $startDate->toDateString() . ' to ' . $endDate->toDateString();
        }

        return '';
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof CarbonPeriod) {
            $dates = $data->map(static fn (Carbon $date) => UserTimezone::applyTimezone($date)->format('Y-m-d'));

            return implode(',', iterator_to_array($dates));
        }

        return '';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof CarbonPeriod) {
            return $value->toArray();
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?CarbonPeriod
    {
        if (is_array($value)) {
            return CarbonPeriod::create(reset($value), end($value));
        }

        return null;
    }

    /**
     * overwrite default implementation to consider columnType & queryColumnType from class config
     */
    public function resolveBlockedVars(): array
    {
        $defaultBlockedVars = [
            'fieldDefinitionsCache',
        ];

        return array_merge($defaultBlockedVars, $this->getBlockedVarsForExport());
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        $isEmpty = true;

        if ($data) {
            if (!$data instanceof CarbonPeriod) {
                throw new ValidationException('Expected an instance of CarbonPeriod');
            }

            $isEmpty = false;
        }

        $fieldName = $this->getName();

        if (true === $isEmpty && false === $omitMandatoryCheck && $this->getMandatory()) {
            throw new ValidationException(sprintf('Empty mandatory field [ %s ]', $fieldName));
        }

        if (false === $isEmpty && false === $omitMandatoryCheck) {
            $startDate = $data->getStartDate();
            $endDate = $data->getEndDate();

            if (!$startDate instanceof CarbonInterface || !$endDate instanceof CarbonInterface) {
                throw new ValidationException(
                    sprintf('Either the start or end value in field [ %s ] is not a date', $fieldName)
                );
            }

            if ($startDate->greaterThan($endDate)) {
                throw new ValidationException(
                    sprintf('Start value in field [ %s ] is bigger than the end value', $fieldName)
                );
            }
        }
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof CarbonPeriod || !$newValue instanceof CarbonPeriod) {
            return false;
        }

        $oldStartDate = $oldValue->getStartDate();
        $oldEndDate = $oldValue->getEndDate();
        $newStartDate = $newValue->getStartDate();
        $newEndDate = $newValue->getEndDate();

        if ($oldStartDate->format('Y-m-d') === $newStartDate->format('Y-m-d')) {
            if ($oldEndDate === null && $newEndDate === null) {
                return true;
            }

            if (!$oldEndDate instanceof CarbonInterface || !$newEndDate instanceof CarbonInterface) {
                return false;
            }

            return $oldEndDate->format('Y-m-d') === $newEndDate->format('Y-m-d');
        }

        return false;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . CarbonPeriod::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . CarbonPeriod::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . CarbonPeriod::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . CarbonPeriod::class . '|null';
    }

    /**
     * Returns a CarbonInterface for the given timestamp.
     */
    private function getDateFromTimestamp(float|int|string $timestamp): Carbon
    {
        $date = new Carbon();
        $date->setTimestamp($timestamp);

        return $date;
    }

    /**
     * @return string[]
     */
    public function getColumnType(): array
    {
        return $this->columnType;
    }

    public function getQueryColumnType(): array
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'dateRange';
    }

    /**
     * @param string|string[] $columnType
     */
    public function setColumnType(string|array $columnType): void
    {
        if (is_array($columnType)) {
            $this->columnType = $columnType;
        } else {
            $this->columnType = [
                'start_date' => $columnType,
                'end_date' => $columnType,
            ];
        }
    }
}
