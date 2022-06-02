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

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Exception;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Normalizer\NormalizerInterface;

class DateRange extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'dateRange';

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var array
     */
    public $queryColumnType = [
        'start_date' => 'bigint(20)',
        'end_date' => 'bigint(20)',
    ];

    /**
     * Type for the column
     *
     * @internal
     *
     * @var array
     */
    public $columnType = [
        'start_date' => 'bigint(20)',
        'end_date' => 'bigint(20)',
    ];

    /**
     * @internal
     */
    public string|int $width = 0;

    public function getWidth(): int|string
    {
        return $this->width;
    }

    public function setWidth(int|string $width): void
    {
        if (\is_numeric($width)) {
            $width = (int) $width;
        }

        $this->width = $width;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param CarbonPeriod|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     */
    public function getDataForResource($data, $object = null, $params = []): array
    {
        $startDateKey = $this->getName() . '__start_date';
        $endDateKey = $this->getName() . '__end_date';

        if ($data instanceof CarbonPeriod) {
            $startDate = $data->getStartDate();
            $endDate = $data->getEndDate();

            $result = [
                $startDateKey => $startDate->getTimestamp(),
                $endDateKey => $endDate instanceof CarbonInterface ? $endDate->getTimestamp() : null,
            ];

            if ($this->getColumnType() === 'date') {
                $result = [
                    $startDateKey => \date('Y-m-d', $result[$startDateKey]),
                    $endDateKey => null !== $result[$endDateKey] ? \date('Y-m-d', $result[$endDateKey]) : null,
                ];
            }

            return $result;
        }

        return [
            $startDateKey => null,
            $endDateKey => null,
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     */
    public function getDataFromResource($data, $object = null, $params = []): ?CarbonPeriod
    {
        $startDateKey = $this->getName() . '__start_date';
        $endDateKey = $this->getName() . '__end_date';

        if (isset($data[$startDateKey], $data[$endDateKey])) {
            if ($this->getColumnType() === 'date') {
                return CarbonPeriod::create($data[$startDateKey], $data[$endDateKey]);
            }

            $startDate = $this->getDateFromTimestamp($data[$startDateKey]);
            $endDate = $this->getDateFromTimestamp($data[$endDateKey]);

            if (!$startDate instanceof Carbon) {
                return null;
            }

            $period = CarbonPeriod::start($startDate);

            if ($endDate instanceof Carbon) {
                $period->setEndDate($endDate);
            }

            return $period;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param CarbonPeriod|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForQueryResource($data, $object = null, $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param CarbonPeriod|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = []): ?array
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
     * @see Data::getDataFromEditmode
     *
     * @param array|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return CarbonPeriod|null
     */
    public function getDataFromEditmode($data, $object = null, $params = []): ?CarbonPeriod
    {
        if (\is_array($data) && isset($data['start_date'], $data['end_date'])) {
            $startDate = $this->getDateFromTimestamp($data['start_date'] / 1000);
            $endDate = $this->getDateFromTimestamp($data['end_date'] / 1000);

            if (!$startDate instanceof Carbon || !$endDate instanceof Carbon) {
                return null;
            }

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
     * @see Data::getVersionPreview
     *
     * @param CarbonPeriod|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = []): string
    {
        if ($data instanceof CarbonPeriod) {
            return $data->toString();
        }

        return '';
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function getForCsvExport($object, $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof CarbonPeriod) {
            $dates = $data->map(static fn (Carbon $date) => $date->format('Y-m-d'));

            return \implode(',', \iterator_to_array($dates));
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getDataForSearchIndex($object, $params = []): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function isDiffChangeAllowed($object, $params = []): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof CarbonPeriod) {
            return $value->toArray();
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($value, $params = [])
    {
        if (\is_array($value)) {
            return CarbonPeriod::create(\reset($value), \end($value));
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = []): void
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
            throw new ValidationException(\sprintf('Empty mandatory field [ %s ]', $fieldName));
        }

        if (false === $isEmpty && false === $omitMandatoryCheck) {
            $startDate = $data->getStartDate();
            $endDate = $data->getEndDate();

            if (!$startDate instanceof CarbonInterface || !$endDate instanceof CarbonInterface) {
                throw new ValidationException(
                    \sprintf('Either the start or end value in field [ %s ] is not a date', $fieldName)
                );
            }

            if ($startDate->greaterThan($endDate)) {
                throw new ValidationException(
                    \sprintf('Start value in field [ %s ] is bigger than the end value', $fieldName)
                );
            }
        }
    }

    /**
     *
     * @param CarbonPeriod|null $oldValue
     * @param CarbonPeriod|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
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

    /**
     * {@inheritDoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . CarbonPeriod::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . CarbonPeriod::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . CarbonPeriod::class . '|null';
    }

    /**
     * {@inheritDoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . CarbonPeriod::class . '|null';
    }

    /**
     * Returns a CarbonInterface for the given timestamp.
     */
    private function getDateFromTimestamp(int $timestamp): CarbonInterface
    {
        $date = new Carbon();
        $date->setTimestamp($timestamp);

        return $date;
    }
}
