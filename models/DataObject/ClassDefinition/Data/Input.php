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
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

class Input extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    TypeDeclarationSupportInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface
{
    use DataObject\ClassDefinition\Data\Extension\Text;
    use DataObject\Traits\DataWidthTrait;
    use DataObject\Traits\SimpleComparisonTrait;
    use Model\DataObject\Traits\DefaultValueTrait;
    use Model\DataObject\Traits\SimpleNormalizerTrait;

    /**
     * @internal
     */
    public ?string $defaultValue = null;

    /**
     * Column length
     *
     * @internal
     */
    public int $columnLength = 190;

    /**
     * @internal
     */
    public string $regex = '';

    /**
     * @internal
     */
    public array $regexFlags = [];

    /**
     * @internal
     */
    public bool $unique = false;

    /**
     * @internal
     */
    public bool $showCharCount = false;

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, Concrete $object = null, array $params = []): ?string
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        return $data;
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $data;
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data === '') {
            return null;
        }

        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @param Model\DataObject\Concrete|null $object
     *
     */
    public function getDataFromGridEditor(string $data, Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    public function getColumnLength(): int
    {
        return $this->columnLength;
    }

    /**
     * @return $this
     */
    public function setColumnLength(?int $columnLength): static
    {
        if ($columnLength) {
            $this->columnLength = $columnLength;
        }

        return $this;
    }

    public function setRegex(string $regex): void
    {
        $this->regex = $regex;
    }

    public function getRegex(): string
    {
        return $this->regex;
    }

    public function getRegexFlags(): array
    {
        return $this->regexFlags;
    }

    public function setRegexFlags(array $regexFlags): void
    {
        $this->regexFlags = $regexFlags;
    }

    public function getUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
    }

    public function getShowCharCount(): bool
    {
        return $this->showCharCount;
    }

    public function setShowCharCount(bool $showCharCount): void
    {
        $this->showCharCount = $showCharCount;
    }

    public function getColumnType(): string
    {
        return 'varchar(' . $this->getColumnLength() . ')';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (is_string($data)) {
            if (!$omitMandatoryCheck && $this->getRegex() && $data !== '') {
                $throwException = false;
                if (in_array('g', $this->getRegexFlags())) {
                    $flags = str_replace('g', '', implode('', $this->getRegexFlags()));
                    if (!preg_match_all('#' . $this->getRegex() . '#' . $flags, $data)) {
                        $throwException = true;
                    }
                } else {
                    if (!preg_match('#'.$this->getRegex().'#'.implode('', $this->getRegexFlags()), $data)) {
                        $throwException = true;
                    }
                }

                if ($throwException) {
                    throw new Model\Element\ValidationException('Value in field [ '.$this->getName()." ] doesn't match input validation '".$this->getRegex()."'");
                }
            }

            if ($this->getColumnLength() && mb_strlen($data) > $this->getColumnLength()) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is longer than '.$this->getColumnLength().' characters');
            }
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\Input $mainDefinition
     */
    public function synchronizeWithMainDefinition(Model\DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->columnLength = $mainDefinition->columnLength;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    protected function doGetDefaultValue(Concrete $object, array $context = []): ?string
    {
        return $this->getDefaultValue();
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    /**
     * @return $this
     */
    public function setDefaultValue(string $defaultValue): static
    {
        if ($defaultValue !== '') {
            $this->defaultValue = $defaultValue;
        }

        return $this;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?string';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?string';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'string|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'string|null';
    }

    public function getFieldType(): string
    {
        return 'input';
    }
}
