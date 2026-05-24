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
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element;
use Pimcore\Model\Metadata\Predefined;

class ManyToManyAssetRelation extends ManyToManyRelation implements LayoutDefinitionEnrichmentInterface
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

    protected function prepareDataForPersistence(array|Element\ElementInterface $data, Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete|null $object = null, array $params = []): mixed
    {
        if (is_array($data)) {
            $return = [];
            $counter = 1;
            foreach ($data as $asset) {
                if ($asset instanceof Asset) {
                    $return[] = [
                        'dest_id' => $asset->getId(),
                        'type' => 'asset',
                        'fieldname' => $this->getName(),
                        'index' => $counter,
                    ];
                }
                $counter++;
            }

            return $return;
        }

        return null;
    }

    protected function loadData(array $data, Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete|null $object = null, array $params = []): mixed
    {
        $assets = [
            'dirty' => false,
            'data' => [],
        ];
        foreach ($data as $relation) {
            $asset = Asset::getById($relation['dest_id']);
            if ($asset instanceof Asset) {
                $assets['data'][] = $asset;
            } else {
                $assets['dirty'] = true;
            }
        }

        //must return array - otherwise this means data is not loaded
        return $assets;
    }

    /**
     *
     *
     * @throws Exception
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        //return null when data is not set
        if (!$data) {
            return null;
        }

        $ids = [];

        if (is_array($data)) {
            foreach ($data as $relation) {
                if ($relation instanceof Asset) {
                    $ids[] = $relation->getId();
                }
            }

            return ',' . implode(',', $ids) . ',';
        }

        throw new Exception('invalid data passed to getDataForQueryResource - must be array and it is: ' . print_r($data, true));
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): array
    {
        $return = [];
        $visibleFieldsArray = $this->getVisibleFields() ? explode(',', (string) $this->getVisibleFields()) : [];

        // add data
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $asset) {
                if ($asset instanceof Asset) {
                    $row = Element\Service::gridElementData($asset);

                    if (!empty($visibleFieldsArray)) {
                        foreach ($visibleFieldsArray as $field) {
                            if (array_key_exists($field, $row)) {
                                continue;
                            }

                            $getter = 'get' . ucfirst($field);
                            if (method_exists($asset, $getter)) {
                                $row[$field] = $asset->{$getter}();
                                continue;
                            }

                            $row[$field] = $asset->getMetadata($field);
                        }
                    }

                    $return[] = $row;
                }
            }
        }

        return $return;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        //if not set, return null
        if ($data === null || $data === false) {
            return null;
        }

        $assets = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                $id = $element['id'] ?? null;
                if ($id === null) {
                    continue;
                }

                $asset = Asset::getById((int) $id);
                if ($asset instanceof Asset) {
                    $assets[] = $asset;
                }
            }
        }

        //must return array if data shall be set
        return $assets;
    }

    public function getDataForGrid(?array $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        $gridData = $this->getDataForEditmode($data, $object, $params);

        if ($this->getPathFormatterClass() && !empty($gridData)) {
            $params['fd'] = $object->getClass()->getFieldDefinition($this->getName(), $params['context'] ?? []);
            foreach ($gridData as &$relatedElementData) {
                $nicePath = $this->getNicePath($relatedElementData, $object, $params);
                if ($nicePath) {
                    $relatedElementData['fullpath'] = $nicePath;
                }
            }
            unset($relatedElementData);
        }

        return $gridData;
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        if (is_array($data) && count($data) > 0) {
            $paths = [];
            foreach ($data as $asset) {
                if ($asset instanceof Element\ElementInterface) {
                    $paths[] = $asset->getRealFullPath();
                }
            }

            return implode('<br />', $paths);
        }

        return '';
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }

        if (is_array($data)) {
            $this->performMultipleAssignmentCheck($data);

            foreach ($data as $asset) {
                if (empty($asset)) {
                    continue;
                }

                $allowAsset = $asset instanceof Asset && $this->allowAssetRelation($asset);
                if (!$allowAsset) {
                    $id = $asset instanceof Asset ? $asset->getId() : '??';
                    throw new Element\ValidationException('Invalid asset relation to asset [' . $id . '] in field ' . $this->getName());
                }
            }

            if ($this->getMaxItems() && count($data) > $this->getMaxItems()) {
                throw new Element\ValidationException('Number of allowed relations in field `' . $this->getName() . '` exceeded (max. ' . $this->getMaxItems() . ')');
            }
        }
    }

    /**
     * @param DataObject\ClassDefinition\Data\ManyToManyAssetRelation $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        parent::synchronizeWithMainDefinition($mainDefinition);

        if ($mainDefinition instanceof self) {
            $this->visibleFields = $mainDefinition->getVisibleFields();
        }
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
                // Predefined metadata not found or error — keep default 'input'
            }
        }

        return $this;
    }

    public function denormalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $elementData) {
                $type = $elementData['type'] ?? 'asset';
                if ($type !== 'asset') {
                    continue;
                }
                $id = $elementData['id'];
                $asset = Asset::getById($id);
                if ($asset instanceof Asset) {
                    $result[] = $asset;
                }
            }

            return $result;
        }

        return null;
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

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $paths = [];
            foreach ($data as $eo) {
                if ($eo instanceof Element\ElementInterface) {
                    $paths[] = $eo->getRealFullPath();
                }
            }

            return implode(',', $paths);
        }

        return '';
    }

    protected function buildUniqueKeyForDiffEditor(array $item): string
    {
        return (string) $item['id'];
    }

    protected function processDiffDataForEditMode(?array $originalData, ?array $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data) {
            $data = $data[0];

            $items = $data['data'];
            $newItems = [];
            if ($items) {
                foreach ($items as $in) {
                    $item = [];
                    $item['id'] = $in['id'];
                    $item['path'] = $in['fullpath'];
                    $item['type'] = $in['type'] ?? 'asset';

                    $unique = $this->buildUniqueKeyForDiffEditor($item);

                    $itemId = json_encode($item);
                    $raw = $itemId;

                    $newItems[] = [
                        'itemId' => $itemId,
                        'title' => $item['path'],
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
                    'path' => [
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

    public function getDiffDataForEditMode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        $originalData = $data;
        $data = parent::getDiffDataForEditMode($data, $object, $params);
        $data = $this->processDiffDataForEditMode($originalData, $data, $object, $params);

        return $data;
    }

    public function getDiffDataFromEditmode(array $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data) {
            $tabledata = $data[0]['data'];

            $result = [];
            if ($tabledata) {
                foreach ($tabledata as $in) {
                    $out = json_decode($in['raw'], true);
                    $result[] = $out;
                }
            }

            return $this->getDataFromEditmode($result, $object, $params);
        }

        return null;
    }

    public function getFieldType(): string
    {
        return 'manyToManyAssetRelation';
    }
}
