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

use Exception;
use JsonSerializable;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;
use Throwable;

class Multiselect extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    TypeDeclarationSupportInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    JsonSerializable,
    NormalizerInterface,
    LayoutDefinitionEnrichmentInterface,
    FieldDefinitionEnrichmentInterface,
    DataContainerAwareInterface,
    OptionsProviderInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use DataObject\Traits\SimpleNormalizerTrait;
    use DataObject\ClassDefinition\DynamicOptionsProvider\SelectionProviderTrait;
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\DataWidthTrait;
    use DataObject\Traits\DefaultValueTrait;
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
    public ?int $maxItems = null;

    /**
     * @internal
     *
     */
    public ?string $renderType = null;

    /**
     * @internal
     */
    public bool $dynamicOptions = false;

    /**
     * @internal
     */
    public ?array $defaultValue = null;

    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @return $this
     */
    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return $this
     */
    public function setMaxItems(?int $maxItems): static
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    /**
     * @return $this
     */
    public function setRenderType(?string $renderType): static
    {
        $this->renderType = $renderType;

        return $this;
    }

    public function getRenderType(): ?string
    {
        return $this->renderType;
    }

    /**
     * @return $this
     */
    public function setDefaultValue(array|string|null $defaultValue): static
    {
        if (is_string($defaultValue)) {
            $defaultValue = $defaultValue !== '' ? [$defaultValue] : null;
        }
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function getDefaultValue(): ?array
    {
        return $this->defaultValue;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     *
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        if (!$this->isEmpty($data) && is_array($data)) {
            return implode(',', $data);
        }

        $defaultValue = $this->handleDefaultValue($data, $object, $params);

        if (is_array($defaultValue)) {
            return implode(',', array_map(fn ($v) => $v['value'] ?? $v, $defaultValue));
        }

        return $defaultValue;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if (strlen((string) $data)) {
            return explode(',', $data);
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     *
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        $dataForResource = $this->getDataForResource($data, $object, $params);
        if ($dataForResource) {
            return ','.$dataForResource.',';
        }

        return null;
    }

    /**
     * @see Data::getDataForEditmode
     *
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    public function getDataForGrid(?array $data, Concrete $object = null, array $params = []): array|string|null
    {
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT
        );

        if ($this->useConfiguredOptions() || $optionsProvider === null) {
            return $this->getDataForEditmode($data, $object, $params);
        }

        $context = $params['context'] ?? [];
        $context['object'] = $object;
        if ($object) {
            $context['class'] = $object->getClass();
        }

        $context['fieldname'] = $this->getName();
        $options = $optionsProvider->getOptions($context, $this);
        $this->setOptions($options);

        if (isset($params['purpose']) && $params['purpose'] === 'editmode') {
            $result = $data;
        } else {
            $result = ['value' => $data, 'options' => $this->getOptions()];
        }

        return $result;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): mixed
    {
        return $data;
    }

    public function getDiffDataFromEditmode(array $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $data = $data[0]['data'];
        if (is_string($data) && $data !== '') {
            return explode(',', $data);
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
        if (is_array($data)) {
            return implode(',', array_map(function ($v) {
                return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
            }, $data));
        }

        return '';
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!is_array($data) && !empty($data)) {
            throw new Model\Element\ValidationException("Invalid multiselect data on field [ {$this->getName()} ]");
        }
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            return implode(',', $data);
        }

        return '';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            return implode(' ', $data);
        }

        return '';
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
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
        if ($operator === '=' || $operator === 'LIKE') {
            $name = $params['name'] ? $params['name'] : $this->name;

            $db = \Pimcore\Db::get();
            $key = $db->quoteIdentifier($name);
            if (!empty($params['brickPrefix'])) {
                $key = $params['brickPrefix'].$key;
            }

            if (str_contains($name, 'cskey') && is_array($value) && !empty($value)) {
                $values = array_map(function ($val) use ($db) {
                    return $db->quote('%' .Helper::escapeLike($val). '%');
                }, $value);

                return $key . ' LIKE ' . implode(' OR ' . $key . ' LIKE ', $values);
            }

            $value = $operator === '='
                ? $db->quote('%,'. $value . ',%')
                : $db->quote('%,%' .Helper::escapeLike($value). '%,%');

            return $key.' LIKE '.$value.' ';
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
            $map = [];
            foreach ($data as $value) {
                $map[$value] = $value;
            }

            $html = '<ul>';

            foreach ((array)$this->options as $option) {
                if ($map[$option['value']] ?? false) {
                    $value = $option['key'];
                    $html .= '<li>' . $value . '</li>';
                }
            }

            $html .= '</ul>';

            $value = [];
            $value['html'] = $html;
            $value['type'] = 'html';

            return $value;
        } else {
            return '';
        }
    }

    /**
     * @param DataObject\ClassDefinition\Data\Multiselect $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->maxItems = $mainDefinition->maxItems;
        $this->options = $mainDefinition->options;
    }

    public function appendData(?array $existingData, array $additionalData): array
    {
        if (!is_array($existingData)) {
            $existingData = [];
        }

        $existingData = array_unique(array_merge($existingData, $additionalData));

        return $existingData;
    }

    public function removeData(?array $existingData, array $removeData): array
    {
        if (!is_array($existingData)) {
            $existingData = [];
        }

        $existingData = array_unique(array_diff($existingData, $removeData));

        return $existingData;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $this->isEqualArray($oldValue, $newValue);
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
        return '?array';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?array';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'string[]|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'string[]|null';
    }

    /**
     * Perform sanity checks, see #5010.
     *
     */
    public function preSave(mixed $containerDefinition, array $params = []): void
    {
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT
        );
        if (!$this->useConfiguredOptions() && $optionsProvider !== null) {
            $context = [];
            $context['fieldname'] = $this->getName();

            try {
                $options = $optionsProvider->getOptions($context, $this);
            } catch (Throwable $e) {
                // error from getOptions => no values => no comma => no problems
                $options = null;
            }
        } else {
            $options = $this->getOptions();
        }
        if (is_array($options) && array_reduce($options, static function ($containsComma, $option) {
            return $containsComma || str_contains((string)$option['value'], ',');
        }, false)) {
            throw new Exception("Field {$this->getName()}: Multiselect option values may not contain commas (,) for now, see <a href='https://github.com/pimcore/pimcore/issues/5010' target='_blank'>issue #5010</a>.");
        }
    }

    public function postSave(mixed $containerDefinition, array $params = []): void
    {
        // nothing to do
    }

    public function enrichFieldDefinition(array $context = []): static
    {
        $this->doEnrichDefinitionDefinition(null, $this->getName(),
            'fielddefinition', DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT, $context);

        return $this;
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        $this->doEnrichDefinitionDefinition($object, $this->getName(),
            'layout', DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT, $context);

        return $this;
    }

    public function getColumnType(): string
    {
        return 'text';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'multiselect';
    }

    protected function doGetDefaultValue(Concrete $object, array $context = []): mixed
    {
        /** @var SelectOptionsProviderInterface|MultiSelectOptionsProviderInterface|null $optionsProvider */
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT
        );
        if ($optionsProvider instanceof SelectOptionsProviderInterface) {
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
}
