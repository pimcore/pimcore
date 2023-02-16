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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ClassDefinition;

use Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

class IndexFieldSelection extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, NormalizerInterface
{
    public mixed $width = null;

    public bool $considerTenants = false;

    public string $multiPreSelect = '';

    public array $filterGroups = [];

    public array $predefinedPreSelectOptions = [];

    public function __construct()
    {
    }

    public function setConsiderTenants(bool $considerTenants): void
    {
        $this->considerTenants = $considerTenants;
    }

    public function getConsiderTenants(): bool
    {
        return $this->considerTenants;
    }

    public function setFilterGroups(array $filterGroups): void
    {
        $this->filterGroups = $filterGroups;
    }

    public function getFilterGroups(): array
    {
        return $this->filterGroups;
    }

    public function setMultiPreSelect(string $multiPreSelect): void
    {
        $this->multiPreSelect = $multiPreSelect;
    }

    public function getMultiPreSelect(): string
    {
        return $this->multiPreSelect;
    }

    public function setPredefinedPreSelectOptions(array $predefinedPreSelectOptions): void
    {
        $this->predefinedPreSelectOptions = $predefinedPreSelectOptions;
    }

    public function getPredefinedPreSelectOptions(): array
    {
        return $this->predefinedPreSelectOptions;
    }

    /**
     * @param mixed $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return array
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, $object = null, array $params = []): array
    {
        if ($data instanceof ObjectData\IndexFieldSelection) {
            return [
                $this->getName() . '__tenant' => $data->getTenant(),
                $this->getName() . '__field' => $data->getField(),
                $this->getName() . '__preSelect' => $data->getPreSelect(),
            ];
        }

        return [
            $this->getName() . '__tenant' => null,
            $this->getName() . '__field' => null,
            $this->getName() . '__preSelect' => null,
        ];
    }

    /**
     * @param mixed $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return ObjectData\IndexFieldSelection|null
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, $object = null, array $params = []): ?ObjectData\IndexFieldSelection
    {
        if ($data[$this->getName() . '__field']) {
            return new ObjectData\IndexFieldSelection($data[$this->getName() . '__tenant'], $data[$this->getName() . '__field'], $data[$this->getName() . '__preSelect']);
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return array
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return array|null
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, $object = null, array $params = []): ?array
    {
        if ($data instanceof ObjectData\IndexFieldSelection) {
            return [
                'tenant' => $data->getTenant(),
                'field' => $data->getField(),
                'preSelect' => $data->getPreSelect(),
            ];
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param null|\Pimcore\Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return ObjectData\IndexFieldSelection|null
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, $object = null, array $params = []): ?ObjectData\IndexFieldSelection
    {
        if ($data['field']) {
            if (is_array($data['preSelect'])) {
                $data['preSelect'] = implode(',', $data['preSelect']);
            }

            return new ObjectData\IndexFieldSelection($data['tenant'], $data['field'], $data['preSelect']);
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param Concrete|null $object
     * @param array $params
     *
     * @return string
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, $object = null, array $params = []): string
    {
        if ($data instanceof ObjectData\IndexFieldSelection) {
            return $data->getTenant() . ' ' . $data->getField() . ' ' . $data->getPreSelect();
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() &&
            ($data === null || $data->getField() === null)) {
            throw new \Exception(get_class($this).': Empty mandatory field [ '.$this->getName().' ]');
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return string
     *
     * @internal
     */
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $key = $this->getName();
        $getter = 'get'.ucfirst($key);
        if ($object->$getter() instanceof ObjectData\IndexFieldSelection) {
            $preSelect = $object->$getter()->getPreSelect();
            if (is_array($preSelect)) {
                $preSelect = implode('%%', $preSelect);
            }

            return $object->$getter()->getTenant() . '%%%%' . $object->$getter()->getField() . '%%%%' . $preSelect ;
        }

        return '';
    }

    /**
     * True if change is allowed in edit mode.
     *
     * @param Concrete $object
     * @param array $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return false;
    }

    public function getWidth(): mixed
    {
        return $this->width;
    }

    public function setWidth(mixed $width): void
    {
        $this->width = (int)$width;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . ObjectData\IndexFieldSelection::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . ObjectData\IndexFieldSelection::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . ObjectData\IndexFieldSelection::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . ObjectData\IndexFieldSelection::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof ObjectData\IndexFieldSelection) {
            return [
                'tenant' => $value->getTenant(),
                'field' => $value->getField(),
                'preSelect' => $value->getPreSelect(),
            ];
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            $tenant = $value['tenant'];
            $field = $value['field'];
            $preSelect = $value['preSelect'];

            return new ObjectData\IndexFieldSelection($tenant, $field, $preSelect);
        }
        if ($value instanceof ObjectData\IndexFieldSelection) {
            return $value;
        }

        return null;
    }

    public function getColumnType(): array
    {
        return [
            'tenant' => 'varchar(100)',
            'field' => 'varchar(200)',
            'preSelect' => 'text',
        ];
    }

    public function getQueryColumnType(): array
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'indexFieldSelection';
    }
}
