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
use InvalidArgumentException;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;

class ManyToManyRelation extends AbstractRelations implements QueryResourcePersistenceAwareInterface, OptimizedAdminLoadingInterface, VarExporterInterface, NormalizerInterface, PreGetDataInterface, PreSetDataInterface
{
    use Model\DataObject\ClassDefinition\Data\Extension\Relation;
    use DataObject\ClassDefinition\Data\Relations\AllowObjectRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\AllowAssetRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\AllowDocumentRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\ManyToManyRelationTrait;
    use DataObject\ClassDefinition\Data\Extension\RelationFilterConditionParser;
    use DataObject\Traits\DataWidthTrait;
    use DataObject\Traits\DataHeightTrait;

    /**
     * @internal
     *
     */
    public ?int $maxItems = null;

    /**
     * @internal
     */
    public bool $assetInlineDownloadAllowed = false;

    /**
     * @internal
     *
     */
    public string $assetUploadPath;

    /**
     * @internal
     */
    public bool $allowToClearRelation = true;

    /**
     * @internal
     */
    public bool $relationType = true;

    /**
     * @internal
     */
    public bool $objectsAllowed = false;

    /**
     * @internal
     */
    public bool $assetsAllowed = false;

    /**
     * Allowed asset types
     *
     * @internal
     *
     */
    public array $assetTypes = [];

    /**
     * @internal
     */
    public bool $documentsAllowed = false;

    /**
     * Allowed document types
     *
     * @internal
     *
     */
    public array $documentTypes = [];

    /**
     * @internal
     */
    public bool $enableTextSelection = false;

    public function getObjectsAllowed(): bool
    {
        return $this->objectsAllowed;
    }

    /**
     * @return $this
     */
    public function setObjectsAllowed(bool $objectsAllowed): static
    {
        $this->objectsAllowed = $objectsAllowed;

        return $this;
    }

    public function getDocumentsAllowed(): bool
    {
        return $this->documentsAllowed;
    }

    /**
     * @return $this
     */
    public function setDocumentsAllowed(bool $documentsAllowed): static
    {
        $this->documentsAllowed = $documentsAllowed;

        return $this;
    }

    public function getDocumentTypes(): array
    {
        return $this->documentTypes ?: [];
    }

    /**
     * @return $this
     */
    public function setDocumentTypes(array $documentTypes): static
    {
        $this->documentTypes = Element\Service::fixAllowedTypes($documentTypes, 'documentTypes');

        return $this;
    }

    public function getAssetsAllowed(): bool
    {
        return $this->assetsAllowed;
    }

    /**
     * @return $this
     */
    public function setAssetsAllowed(bool $assetsAllowed): static
    {
        $this->assetsAllowed = $assetsAllowed;

        return $this;
    }

    /**
     * @return array<array{assetTypes: string}>
     */
    public function getAssetTypes(): array
    {
        return $this->assetTypes;
    }

    /**
     * @return $this
     */
    public function setAssetTypes(array $assetTypes): static
    {
        $this->assetTypes = Element\Service::fixAllowedTypes($assetTypes, 'assetTypes');

        return $this;
    }

    protected function prepareDataForPersistence(array|Element\ElementInterface $data, Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object = null, array $params = []): mixed
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $object) {
                if ($object instanceof Element\ElementInterface) {
                    $return[] = [
                        'dest_id' => $object->getId(),
                        'type' => Element\Service::getElementType($object),
                        'fieldname' => $this->getName(),
                        'index' => $counter,
                    ];
                }
                $counter++;
            }

            return $return;
        } elseif (is_array($data) && count($data) === 0) {
            //give empty array if data was not null
            return [];
        } else {
            //return null if data was null  - this indicates data was not loaded
            return null;
        }
    }

    protected function loadData(array $data, Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object = null, array $params = []): mixed
    {
        $elements = [
            'dirty' => false,
            'data' => [],
        ];
        foreach ($data as $element) {
            $e = null;
            if ($element['type'] === 'object') {
                $e = DataObject::getById($element['dest_id']);
            } elseif ($element['type'] === 'asset') {
                $e = Asset::getById($element['dest_id']);
            } elseif ($element['type'] === 'document') {
                $e = Document::getById($element['dest_id']);
            }

            if ($e instanceof Element\ElementInterface) {
                $elements['data'][] = $e;
            } else {
                $elements['dirty'] = true;
            }
        }

        //must return array - otherwise this means data is not loaded
        return $elements;
    }

    /**
     *
     *
     * @throws Exception
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        //return null when data is not set
        if (!$data) {
            return null;
        }

        $d = [];

        if (is_array($data)) {
            foreach ($data as $element) {
                if ($element instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($element);
                    $d[] = $elementType . '|' . $element->getId();
                }
            }

            return ',' . implode(',', $d) . ',';
        }

        throw new Exception('invalid data passed to getDataForQueryResource - must be array');
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                if ($element instanceof DataObject\Concrete) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), DataObject::OBJECT_TYPE_OBJECT, $element->getClassName(), $element->getPublished()];
                } elseif ($element instanceof DataObject\AbstractObject) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_FOLDER];
                } elseif ($element instanceof Asset) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), 'asset', $element->getType()];
                } elseif ($element instanceof Document) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), 'document', $element->getType(), $element->getPublished()];
                }
            }
            if (empty($return)) {
                $return = null;
            }

            return $return;
        }

        return null;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        //if not set, return null
        if ($data === null || $data === false) {
            return null;
        }

        $elements = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                $e = null;
                if ($element['type'] == 'object') {
                    $e = DataObject::getById($element['id']);
                } elseif ($element['type'] == 'asset') {
                    $e = Asset::getById($element['id']);
                } elseif ($element['type'] == 'document') {
                    $e = Document::getById($element['id']);
                }

                if ($e instanceof Element\ElementInterface) {
                    $elements[] = $e;
                }
            }
        }

        //must return array if data shall be set
        return $elements;
    }

    /**
     * @param null|DataObject\Concrete $object
     *
     */
    public function getDataFromGridEditor(array $data, Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param DataObject\Concrete|null $object
     *
     * @todo: $pathes is undefined
     */
    public function getDataForGrid(?array $data, Concrete $object = null, array $params = []): ?array
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
        if (is_array($data) && count($data) > 0) {
            $paths = [];

            foreach ($data as $element) {
                if ($element instanceof Element\ElementInterface) {
                    $paths[] = Element\Service::getElementType($element) .' '. $element->getRealFullPath();
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

        $allow = true;
        if (is_array($data)) {
            $this->performMultipleAssignmentCheck($data);
            foreach ($data as $d) {
                if ($d instanceof Document) {
                    $allow = $this->allowDocumentRelation($d);
                } elseif ($d instanceof Asset) {
                    $allow = $this->allowAssetRelation($d);
                } elseif ($d instanceof DataObject\AbstractObject) {
                    $allow = $this->allowObjectRelation($d);
                } elseif (empty($d)) {
                    $allow = true;
                } else {
                    $allow = false;
                }
                if (!$allow) {
                    throw new Element\ValidationException(sprintf('Invalid relation in field `%s` [type: %s]', $this->getName(), $this->getFieldtype()));
                }
            }

            if ($this->getMaxItems() && count($data) > $this->getMaxItems()) {
                throw new Element\ValidationException('Number of allowed relations in field `' . $this->getName() . '` exceeded (max. ' . $this->getMaxItems() . ')');
            }
        }
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $paths = [];
            foreach ($data as $eo) {
                if ($eo instanceof Element\ElementInterface) {
                    $paths[] = Element\Service::getElementType($eo) . ':' . $eo->getRealFullPath();
                }
            }

            return implode(',', $paths);
        }

        return '';
    }

    public function getCacheTags(mixed $data, array $tags = []): array
    {
        return $tags;
    }

    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $e) {
                if ($e instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($e);
                    $dependencies[$elementType . '_' . $e->getId()] = [
                        'id' => $e->getId(),
                        'type' => $elementType,
                    ];
                }
            }
        }

        return $dependencies;
    }

    public function preGetData(mixed $container, array $params = []): mixed
    {
        $data = null;
        if ($container instanceof DataObject\Concrete) {
            $data = $container->getObjectVar($this->getName());
            if (!$container->isLazyKeyLoaded($this->getName())) {
                $data = $this->load($container);

                $container->setObjectVar($this->getName(), $data);
                $this->markLazyloadedFieldAsLoaded($container);

                if ($container instanceof Element\DirtyIndicatorInterface) {
                    $container->markFieldDirty($this->getName(), false);
                }
            }
        } elseif ($container instanceof DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($container instanceof DataObject\Fieldcollection\Data\AbstractData) {
            parent::loadLazyFieldcollectionField($container);
            $data = $container->getObjectVar($this->getName());
        } elseif ($container instanceof DataObject\Objectbrick\Data\AbstractData) {
            parent::loadLazyBrickField($container);
            $data = $container->getObjectVar($this->getName());
        }

        return $this->filterUnpublishedElements($data);
    }

    public function preSetData(mixed $container, mixed $data, array $params = []): mixed
    {
        if ($data === null) {
            $data = [];
        }

        $this->markLazyloadedFieldAsLoaded($container);

        return $data;
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
    public function setAssetInlineDownloadAllowed(bool $assetInlineDownloadAllowed): static
    {
        $this->assetInlineDownloadAllowed = $assetInlineDownloadAllowed;

        return $this;
    }

    public function getAssetInlineDownloadAllowed(): bool
    {
        return $this->assetInlineDownloadAllowed;
    }

    /**
     * @return $this
     */
    public function setAssetUploadPath(string $assetUploadPath): static
    {
        $this->assetUploadPath = $assetUploadPath;

        return $this;
    }

    public function getAssetUploadPath(): string
    {
        return $this->assetUploadPath;
    }

    public function isAllowedToClearRelation(): bool
    {
        return $this->allowToClearRelation;
    }

    public function setAllowToClearRelation(bool $allowToClearRelation): void
    {
        $this->allowToClearRelation = $allowToClearRelation;
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
    public function getDiffVersionPreview(?array $data, Concrete $object = null, array $params = []): array
    {
        $value = [];
        $value['type'] = 'html';
        $value['html'] = '';

        if ($data) {
            $html = $this->getVersionPreview($data, $object, $params);
            $value['html'] = $html;
        }

        return $value;
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);
        $data = $this->rewriteIdsService($data, $idMapping);

        return $data;
    }

    /**
     * @param DataObject\ClassDefinition\Data\ManyToManyRelation $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->maxItems = $mainDefinition->maxItems;
        $this->assetUploadPath = $mainDefinition->assetUploadPath;
        $this->relationType = $mainDefinition->relationType;
        $this->objectsAllowed = $mainDefinition->objectsAllowed;
        $this->assetsAllowed = $mainDefinition->assetsAllowed;
        $this->assetTypes = $mainDefinition->assetTypes;
        $this->documentsAllowed = $mainDefinition->documentsAllowed;
        $this->documentTypes = $mainDefinition->documentTypes;
    }

    protected function getPhpdocType(): string
    {
        return $this->getPhpDocClassString(true);
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $element) {
                $type = Element\Service::getElementType($element);
                $id = $element->getId();
                $result[] = [
                    'type' => $type,
                    'id' => $id,
                ];
            }

            return $result;
        }

        return null;
    }

    /** See marshal
     *
     *
     */
    public function denormalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $elementData) {
                $type = $elementData['type'];
                $id = $elementData['id'];
                $element = Element\Service::getElementById($type, $id);
                if ($element) {
                    $result[] = $element;
                }
            }

            return $result;
        }

        return null;
    }

    /**
     * Returns a ID which must be unique across the grid rows
     *
     *
     */
    public function buildUniqueKeyForDiffEditor(array $item): string
    {
        $parts = [
            $item['id'],
            $item['path'],
            $item['type'],
            $item['subtype'],
        ];

        return json_encode($parts);
    }

    /**
     * @param Element\ElementInterface[]|null $originalData
     * @param null|DataObject\Concrete $object
     *
     */
    protected function processDiffDataForEditMode(?array $originalData, ?array $data, Concrete $object = null, array $params = []): ?array
    {
        if ($data) {
            $data = $data[0];

            $items = $data['data'];
            $newItems = [];
            if ($items) {
                foreach ($items as $in) {
                    $item = [];
                    $item['id'] = $in[0];
                    $item['path'] = $in[1];
                    $item['type'] = $in[2];
                    $item['subtype'] = $in[3];

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

    public function getDiffDataForEditMode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $originalData = $data;
        $data = parent::getDiffDataForEditMode($data, $object, $params);
        $data = $this->processDiffDataForEditMode($originalData, $data, $object, $params);

        return $data;
    }

    public function getDiffDataFromEditmode(array $data, DataObject\Concrete $object = null, array $params = []): ?array
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

    public function isOptimizedAdminLoading(): bool
    {
        return true;
    }

    public function isEnableTextSelection(): bool
    {
        return $this->enableTextSelection;
    }

    public function setEnableTextSelection(bool $enableTextSelection): void
    {
        $this->enableTextSelection = $enableTextSelection;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public function addListingFilter(DataObject\Listing $listing, float|array|int|string|Model\Element\ElementInterface $data, string $operator = '='): DataObject\Listing
    {
        if ($data instanceof Element\ElementInterface) {
            $data = [
                'id' => $data->getId(),
                'type' => Element\Service::getElementType($data),
            ];
        }

        if (!isset($data['id'], $data['type'])) {
            throw new InvalidArgumentException('Please provide an array with keys "id" and "type" or an object which implements '.Element\ElementInterface::class);
        }

        if ($operator === '=') {
            $listing->addConditionParam('`'.$this->getName().'` LIKE ?', '%,'.$data['type'].'|'.$data['id'].',%');

            return $listing;
        }

        throw new InvalidArgumentException('Filtering '.__CLASS__.' does only support "=" operator');
    }

    /**
     * Filter by relation feature
     *
     *
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $name = $params['name'] ?: $this->name;

        return $this->getRelationFilterCondition($value, $operator, $name);
    }

    public function getQueryColumnType(): string
    {
        return 'text';
    }

    public function getFieldType(): string
    {
        return 'manyToManyRelation';
    }
}
