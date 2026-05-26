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

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element;
use Pimcore\Model\Metadata\Predefined;

class AdvancedManyToManyAssetRelation extends AdvancedManyToManyRelation implements LayoutDefinitionEnrichmentInterface
{
    /**
     * @internal
     *
     * @var string[]|string|null
     */
    public array|string|null $visibleFields = null;

    /**
     * @internal
     *
     * @var array<string, array<string, mixed>>
     */
    public array $visibleFieldDefinitions = [];

    public function __construct()
    {
        $this->setAssetsAllowed(true);
        $this->setObjectsAllowed(false);
        $this->setDocumentsAllowed(false);
        $this->assetUploadPath = '';
    }

    public function getObjectsAllowed(): bool
    {
        return false;
    }

    public function getDocumentsAllowed(): bool
    {
        return false;
    }

    public function getAssetsAllowed(): bool
    {
        return true;
    }

    /**
     * @param string[]|string|null $visibleFields
     *
     * @return $this
     */
    public function setVisibleFields(array|string|null $visibleFields): static
    {
        if (is_array($visibleFields) && count($visibleFields)) {
            $visibleFields = implode(',', $visibleFields);
        }
        $this->visibleFields = $visibleFields;

        return $this;
    }

    public function getVisibleFields(): array|null|string
    {
        return $this->visibleFields;
    }

    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if (!$data) {
            return null;
        }

        $ids = [];

        if (is_array($data)) {
            foreach ($data as $metaAsset) {
                $asset = $metaAsset->getElement();
                if ($asset instanceof Asset) {
                    $ids[] = $asset->getId();
                }
            }

            return ',' . implode(',', $ids) . ',';
        }

        throw new Exception('invalid data passed to getDataForQueryResource - must be array');
    }

    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        $return = parent::getDataForEditmode($data, $object, $params);

        $visibleFieldsArray = $this->getVisibleFields() ? explode(',', (string) $this->getVisibleFields()) : [];
        if (!is_array($return) || empty($visibleFieldsArray)) {
            return $return;
        }

        foreach ($return as &$row) {
            $asset = Asset::getById($row['id']);
            if (!$asset instanceof Asset) {
                continue;
            }

            foreach ($visibleFieldsArray as $field) {
                if (array_key_exists($field, $row)) {
                    continue;
                }

                $getter = 'get' . ucfirst($field);
                $row[$field] = method_exists($asset, $getter)
                    ? $asset->{$getter}()
                    : $asset->getMetadata($field);
            }
        }
        unset($row);

        return $return;
    }

    public function getDataForGrid(?array $data, ?Concrete $object = null, array $params = []): ?array
    {
        $gridData = $this->getDataForEditmode($data, $object, $params);

        if ($this->getPathFormatterClass() && !empty($gridData)) {
            $params['fd'] = $object->getClass()->getFieldDefinition($this->getName(), $params['context'] ?? []);
            foreach ($gridData as &$relatedElementData) {
                $nicePath = $this->getNicePath($relatedElementData, $object, $params);
                if ($nicePath) {
                    $relatedElementData['path'] = $nicePath;
                }
            }
            unset($relatedElementData);
        }

        return $gridData;
    }

    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        $items = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaAsset) {
                if (!($metaAsset instanceof DataObject\Data\ElementMetadata)) {
                    continue;
                }

                $asset = $metaAsset->getElement();
                if (!$asset instanceof Asset) {
                    continue;
                }

                $item = $asset->getRealFullPath();

                if (count($metaAsset->getData())) {
                    $subItems = [];
                    foreach ($metaAsset->getData() as $key => $value) {
                        if (!$value) {
                            continue;
                        }
                        $subItems[] = $key . ': ' . $value;
                    }

                    if (count($subItems)) {
                        $item .= ' <br/><span class="preview-metadata">[' . implode(' | ', $subItems) . ']</span>';
                    }
                }

                $items[] = $item;
            }

            return implode('<br />', $items);
        }

        return '';
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $paths = [];
            foreach ($data as $metaAsset) {
                $asset = $metaAsset->getElement();
                if ($asset instanceof Element\ElementInterface) {
                    $paths[] = $asset->getRealFullPath();
                }
            }

            return implode(',', $paths);
        }

        return '';
    }

    public function denormalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            $object = $params['object'] ?? null;
            foreach ($value as $elementMetadata) {
                $elementData = $elementMetadata['element'];

                $type = $elementData['type'];
                $id = $elementData['id'];
                $element = Element\Service::getElementById($type, $id);
                if ($element instanceof Asset) {
                    $columns = $elementMetadata['columns'];
                    $fieldname = $elementMetadata['fieldname'];
                    $data = $elementMetadata['data'];

                    $item = new DataObject\Data\ElementMetadata($fieldname, $columns, $element);
                    $item->_setOwner($object);
                    $item->_setOwnerFieldname($this->getName());
                    $item->setData($data);
                    $result[] = $item;
                }
            }

            return $result;
        }

        return null;
    }

    protected function processDiffDataForEditMode(?array $originalData, ?array $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data) {
            $data = $data[0];

            $items = $data['data'];
            $newItems = [];
            if ($items) {
                $columns = array_merge(['id', 'fullpath'], $this->getColumnKeys());
                foreach ($items as $itemBeforeCleanup) {
                    $unique = $this->buildUniqueKeyForDiffEditor($itemBeforeCleanup);
                    $item = [];

                    foreach ($itemBeforeCleanup as $key => $value) {
                        if (in_array($key, $columns)) {
                            $item[$key] = $value;
                        }
                    }

                    $itemId = json_encode($item);
                    $raw = $itemId;

                    $newItems[] = [
                        'itemId' => $itemId,
                        'title' => $item['fullpath'] ?? '',
                        'raw' => $raw,
                        'gridrow' => $item,
                        'unique' => $unique,
                    ];
                }
                $data['data'] = $newItems;
            }

            $data['value'] = [
                'type' => 'grid',
                'columnConfig' => [
                    'id' => [
                        'width' => 60,
                    ],
                    'fullpath' => [
                        'flex' => 2,
                    ],
                ],
                'html' => $this->getVersionPreview($originalData, $object, $params),
            ];

            $newData = [];
            $newData[] = $data;

            return $newData;
        }

        return $data;
    }

    public function enrichLayoutDefinition(?DataObject\Concrete $object, array $context = []): static
    {
        if (!$this->visibleFields) {
            return $this;
        }

        $translator = Pimcore::getContainer()->get('translator');
        $this->visibleFieldDefinitions = [];
        $visibleFields = explode(',', (string) $this->visibleFields);

        foreach ($visibleFields as $field) {
            $field = trim($field);
            $this->visibleFieldDefinitions[$field]['name'] = $field;
            $this->visibleFieldDefinitions[$field]['title'] = $translator->trans($field, [], 'admin');
            $this->visibleFieldDefinitions[$field]['fieldtype'] = 'input';

            try {
                $predefined = Predefined::getByName($field);
                if ($predefined && $predefined->getType()) {
                    $metaType = $predefined->getType();
                    $typeMap = [
                        'input' => 'input',
                        'textarea' => 'textarea',
                        'checkbox' => 'checkbox',
                        'date' => 'date',
                    ];

                    if (isset($typeMap[$metaType])) {
                        $this->visibleFieldDefinitions[$field]['fieldtype'] = $typeMap[$metaType];
                    } elseif ($metaType === 'select') {
                        $this->visibleFieldDefinitions[$field]['fieldtype'] = 'select';
                        $config = $predefined->getConfig();
                        if ($config) {
                            $options = [];
                            foreach (explode(',', $config) as $option) {
                                $option = trim($option);
                                if ($option !== '') {
                                    $options[] = ['key' => $option, 'value' => $option];
                                }
                            }
                            $this->visibleFieldDefinitions[$field]['options'] = $options;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Predefined metadata not found or error -- keep default 'input'
            }
        }

        return $this;
    }

    public function addListingFilter(DataObject\Listing $listing, float|array|int|string|Model\Element\ElementInterface $data, string $operator = '='): DataObject\Listing
    {
        if ($data instanceof Asset) {
            $data = $data->getId();
        } elseif (is_array($data)) {
            $data = $data['id'] ?? null;
        }

        if ($data === null) {
            throw new InvalidArgumentException('Please provide an asset id, an asset, or an array containing the key "id".');
        }

        if ($operator === '=') {
            $listing->addConditionParam('`'.$this->getName().'` LIKE ?', '%,'.$data.',%');

            return $listing;
        }

        throw new InvalidArgumentException('Filtering '.__CLASS__.' does only support "=" operator');
    }

    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        parent::synchronizeWithMainDefinition($mainDefinition);

        if ($mainDefinition instanceof self) {
            $this->visibleFields = $mainDefinition->getVisibleFields();
            $this->enableBatchEdit = $mainDefinition->getEnableBatchEdit();
            $this->allowMultipleAssignments = $mainDefinition->getAllowMultipleAssignments();
        }
    }

    public function getFieldType(): string
    {
        return 'advancedManyToManyAssetRelation';
    }
}
