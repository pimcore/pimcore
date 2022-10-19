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
    use Model\DataObject\ClassDefinition\Data\Extension\Text;
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use Model\DataObject\Traits\DefaultValueTrait;
    use Model\DataObject\Traits\SimpleNormalizerTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public string $fieldtype = 'input';

    /**
     * @internal
     *
     * @var string|int
     */
    public string|int $width = 0;

    /**
     * @internal
     *
     * @var string|null
     */
    public ?string $defaultValue;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'varchar';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'varchar';

    /**
     * Column length
     *
     * @internal
     *
     * @var int
     */
    public int $columnLength = 190;

    /**
     * @internal
     *
     * @var string
     */
    public string $regex = '';

    /**
     * @internal
     *
     * @var array
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
     * @return string|int
     */
    public function getWidth(): int|string
    {
        return $this->width;
    }

    /**
     * @param int|string $width
     *
     * @return $this
     */
    public function setWidth(int|string $width): static
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     *@see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, $object = null, array $params = []): ?string
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        return $data;
    }

    /**
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     *@see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, $object = null, array $params = []): ?string
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     *@see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     */
    public function getDataForQueryResource(mixed $data, $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, $object = null, array $params = []): ?string
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @param string $data
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     *
     * @return string|null
     */
    public function getDataFromGridEditor(string $data, Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @return int
     */
    public function getColumnLength(): int
    {
        return $this->columnLength;
    }

    /**
     * @param int|null $columnLength
     *
     * @return $this
     */
    public function setColumnLength(?int $columnLength): static
    {
        if ($columnLength) {
            $this->columnLength = $columnLength;
        }

        return $this;
    }

    public function setRegex(string $regex)
    {
        $this->regex = $regex;
    }

    /**
     * @return string
     */
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

    /**
     * @return bool
     */
    public function getUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique)
    {
        $this->unique = (bool) $unique;
    }

    /**
     * @return bool
     */
    public function getShowCharCount(): bool
    {
        return $this->showCharCount;
    }

    public function setShowCharCount(bool $showCharCount)
    {
        $this->showCharCount = (bool) $showCharCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(): array|string|null
    {
        return $this->columnType . '(' . $this->getColumnLength() . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryColumnType(): array|string|null
    {
        return $this->queryColumnType . '(' . $this->getColumnLength() . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = [])
    {
        if (!$omitMandatoryCheck && $this->getRegex() && strlen($data) > 0) {
            if (!preg_match('#' . $this->getRegex() . '#' . implode('', $this->getRegexFlags()), $data)) {
                throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . " ] doesn't match input validation '" . $this->getRegex() . "'");
            }
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\Input $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->columnLength = $masterDefinition->columnLength;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetDefaultValue(Concrete $object, array $context = []): ?string
    {
        return $this->getDefaultValue();
    }

    /**
     * @return string|null
     */
    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    /**
     * @param string $defaultValue
     *
     * @return $this
     */
    public function setDefaultValue(string $defaultValue): static
    {
        if ((string)$defaultValue !== '') {
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
}
