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

use InvalidArgumentException;
use JsonSerializable;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

class Select extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    TypeDeclarationSupportInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    JsonSerializable,
    NormalizerInterface,
    LayoutDefinitionEnrichmentInterface,
    FieldDefinitionEnrichmentInterface,
    OptionsProviderInterface
{
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use DataObject\Traits\SimpleNormalizerTrait;
    use DataObject\Traits\DefaultValueTrait;
    use DataObject\ClassDefinition\DynamicOptionsProvider\SelectionProviderTrait;
    use DataObject\Traits\DataWidthTrait;
    use OptionsProviderTrait;

    /**
     * Available options to select
     *
     * @internal
     *
     */
    public ?array $options = null;

    /**
     * @internal
     *
     */
    public ?string $defaultValue = null;

    /**
     * Column length
     *
     * @internal
     *
     */
    public int $columnLength = 190;

    /**
     * @internal
     */
    public bool $dynamicOptions = false;

    public function getColumnLength(): int
    {
        return $this->columnLength;
    }

    public function setColumnLength(int $columnLength): static
    {
        if ($columnLength) {
            $this->columnLength = $columnLength;
        }

        return $this;
    }

    public function getColumnType(): string
    {
        return 'varchar(' . $this->getColumnLength() . ')';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): static
    {
        if (is_array($options)) {
            $this->options = [];
            foreach ($options as $option) {
                $option = (array)$option;
                if (!array_key_exists('key', $option) || !array_key_exists('value', $option)) {
                    throw new InvalidArgumentException('Please provide select options as associative array with fields "key" and "value"');
                }

                $this->options[] = $option;
            }
        } else {
            $this->options = null;
        }

        return $this;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): null|string|int
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        return $data;
    }

    /**
     * @param null|DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, Concrete $object = null, array $params = []): null|string|int
    {
        return $data;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): null|string|int
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): null|string|int
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
        return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /** See parent class.
     *
     */
    public function getDiffDataForEditMode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $result = [];

        $diffdata = [];
        $diffdata['data'] = $data;
        $diffdata['disabled'] = false;
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->getFieldType();

        $value = '';
        foreach ($this->options as $option) {
            if ($option['value'] == $data) {
                $value = $option['key'];

                break;
            }
        }

        $diffdata['value'] = $value;
        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;

        $result[] = $diffdata;

        return $result;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    public function isEmpty(mixed $data): bool
    {
        if (is_array($data)) {
            return count($data) < 1;
        }

        return (string) $data === '';
    }

    /**
     * @param DataObject\ClassDefinition\Data\Select $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->options = $mainDefinition->options;
        $this->columnLength = $mainDefinition->columnLength;
        $this->defaultValue = $mainDefinition->defaultValue;
        $this->optionsProviderType = $mainDefinition->optionsProviderType;
        $this->optionsProviderClass = $mainDefinition->optionsProviderClass;
        $this->optionsProviderData = $mainDefinition->optionsProviderData;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function enrichFieldDefinition(array $context = []): static
    {
        $this->doEnrichDefinitionDefinition(null, $this->getName(),
            'fielddefinition', DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_SELECT, $context);

        return $this;
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        $this->doEnrichDefinitionDefinition($object, $this->getName(),
            'layout', DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_SELECT, $context);

        return $this;
    }

    /**
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDataForGrid(mixed $data, Concrete $object = null, array $params = []): array|string|int|null
    {
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_SELECT
        );

        if (!$this->useConfiguredOptions() && $optionsProvider !== null) {
            $context = $params['context'] ?? [];
            $context['object'] = $object;
            if ($object) {
                $context['class'] = $object->getClass();
            }

            $context['fieldname'] = $this->getName();
            $options = $optionsProvider->getOptions($context, $this);
            $this->setOptions($options);

            if (isset($params['purpose']) && $params['purpose'] == 'editmode') {
                $result = $data;
            } else {
                $result = ['value' => $data ?? null, 'options' => $this->getOptions()];
            }

            return $result;
        }

        return $data;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param array $params optional params used to change the behavior
     *
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $value = is_array($value) ? current($value) : $value;
        $name = $params['name'] ?: $this->name;

        $db = \Pimcore\Db::get();
        $key = $db->quoteIdentifier($name);
        if (!empty($params['brickPrefix'])) {
            $key = $params['brickPrefix'].$key;
        }

        if ($operator === '=') {
            return $key.' = '."\"$value\"".' ';
        }
        if ($operator === 'LIKE') {
            return $key.' LIKE '."\"%$value%\"".' ';
        }

        return '';
    }

    public function isFilterable(): bool
    {
        return true;
    }

    protected function doGetDefaultValue(Concrete $object, array $context = []): ?string
    {
        /** @var DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface|null $optionsProvider */
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_SELECT
        );
        if (!$this->useConfiguredOptions() && $optionsProvider !== null) {
            $context['object'] = $object;
            $context['class'] = $object->getClass();

            $context['fieldname'] = $this->getName();
            if (!isset($context['purpose'])) {
                $context['purpose'] = 'layout';
            }

            return $optionsProvider->getDefaultValue($context, $this);
        }

        return $this->getDefaultValue();
    }

    public function jsonSerialize(): mixed
    {
        if (!$this->useConfiguredOptions() && $this->getOptionsProviderClass() && Service::doRemoveDynamicOptions()) {
            $this->options = null;
        }

        return parent::jsonSerialize();
    }

    public function resolveBlockedVars(): array
    {
        $blockedVars = parent::resolveBlockedVars();

        if (!$this->useConfiguredOptions() && $this->getOptionsProviderClass()) {
            $blockedVars[] = 'options';
        }

        return $blockedVars;
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

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $oldValue == $newValue;
    }

    public function getFieldType(): string
    {
        return 'select';
    }
}
