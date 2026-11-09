<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationMessageKey;

class Time extends Model\DataObject\ClassDefinition\Data\Input
{
    /**
     * Column length
     *
     * @internal
     *
     */
    public int $columnLength = 5;

    /**
     * @internal
     *
     */
    public ?string $minValue = null;

    /**
     * @internal
     *
     */
    public ?string $maxValue = null;

    /**
     * @internal
     *
     */
    public int $increment = 15 ;

    public function getMinValue(): ?string
    {
        return $this->minValue;
    }

    public function setMinValue(?string $minValue): void
    {
        if (is_string($minValue) && strlen($minValue)) {
            $this->minValue = $this->toTime($minValue);
        } else {
            $this->minValue = null;
        }
    }

    public function getMaxValue(): ?string
    {
        return $this->maxValue;
    }

    public function setMaxValue(?string $maxValue): void
    {
        if (is_string($maxValue) && strlen($maxValue)) {
            $this->maxValue = $this->toTime($maxValue);
        } else {
            $this->maxValue = null;
        }
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        parent::checkValidity($data, $omitMandatoryCheck);

        if (is_string($data)) {
            if (!preg_match('/^(2[0-3]|[01][0-9]):[0-5][0-9]$/', $data) && $data !== '') {
                throw (new StructuredValidationException(
                    'Wrong time format given must be a 5 digit string (eg: 06:49) [ '.$this->getName().' ]'
                ))
                    ->setTranslation(ValidationMessageKey::INVALID_TIME, ['value' => $data]);
            }
        } elseif (!empty($data)) {
            throw (new StructuredValidationException(
                'Wrong time format given must be a 5 digit string (eg: 06:49) [ '.$this->getName().' ]'
            ))
                ->setTranslation(ValidationMessageKey::INVALID_TIME, ['value' => $data]);
        }

        if (!$omitMandatoryCheck && $data) {
            if (!$this->toTime($data)) {
                throw (new StructuredValidationException(
                    'Wrong time format given must be a 5 digit string (eg: 06:49) [ '.$this->getName().' ]'
                ))
                    ->setTranslation(ValidationMessageKey::INVALID_TIME, ['value' => $data]);
            }

            if ($this->getMinValue() && $this->isEarlier($this->getMinValue(), $data)) {
                throw (new StructuredValidationException(
                    'Value in field [ '.$this->getName().' ] is not at least ' . $this->getMinValue()
                ))
                    ->setTranslation(
                        ValidationMessageKey::MIN_VALUE,
                        [
                            'min' => $this->getMinValue(),
                            'value' => $data,
                        ],
                    );
            }

            if ($this->getMaxValue() && $this->isLater($this->getMaxValue(), $data)) {
                throw (new StructuredValidationException(
                    'Value in field [ ' . $this->getName() . ' ] is bigger than ' . $this->getMaxValue()
                ))
                    ->setTranslation(
                        ValidationMessageKey::MAX_VALUE,
                        [
                            'max' => $this->getMaxValue(),
                            'value' => $data,
                        ],
                    );
            }
        }
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    public function isEmpty(mixed $data): bool
    {
        return !is_string($data) || !preg_match('/^(2[0-3]|[01][0-9]):[0-5][0-9]$/', $data);
    }

    /**
     * Returns a 5 digit time string of a given time
     *
     *
     */
    private function toTime(string $timestamp): ?string
    {
        $timestamp = strtotime($timestamp);
        if (!$timestamp) {
            return null;
        }

        return date('H:i', $timestamp);
    }

    /**
     * Returns a timestamp representation of a given time
     *
     *
     */
    private function toTimestamp(string $string, ?int $baseTimestamp = null): int
    {
        if ($baseTimestamp === null) {
            $baseTimestamp = time();
        }

        return strtotime($string, $baseTimestamp);
    }

    /**
     * Returns whether or not a time is earlier than the subject
     *
     *
     */
    private function isEarlier(string $subject, string $comparison): bool
    {
        $baseTs = time();

        return $this->toTimestamp($subject, $baseTs) > $this->toTimestamp($comparison, $baseTs);
    }

    /**
     * Returns whether or not a time is later than the subject
     *
     *
     */
    private function isLater(string $subject, string $comparison): bool
    {
        $baseTs = time();

        return $this->toTimestamp($subject, $baseTs) < $this->toTimestamp($comparison, $baseTs);
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function getIncrement(): int
    {
        return $this->increment;
    }

    public function setIncrement(int $increment): void
    {
        $this->increment = $increment;
    }

    public function getFieldType(): string
    {
        return 'time';
    }
}
