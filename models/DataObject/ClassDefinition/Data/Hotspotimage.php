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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class Hotspotimage extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, NormalizerInterface, IdRewriterInterface
{
    use ImageTrait;
    use DataObject\Traits\SimpleComparisonTrait;
    use DataObject\ClassDefinition\Data\Extension\RelationFilterConditionParser;

    /**
     * @internal
     *
     */
    public int $ratioX;

    /**
     * @internal
     *
     */
    public int $ratioY;

    /**
     * @internal
     *
     */
    public string $predefinedDataTemplates;

    public function setRatioX(int $ratioX): void
    {
        $this->ratioX = $ratioX;
    }

    public function getRatioX(): int
    {
        return $this->ratioX;
    }

    public function setRatioY(int $ratioY): void
    {
        $this->ratioY = $ratioY;
    }

    public function getRatioY(): int
    {
        return $this->ratioY;
    }

    public function getPredefinedDataTemplates(): string
    {
        return $this->predefinedDataTemplates;
    }

    public function setPredefinedDataTemplates(string $predefinedDataTemplates): void
    {
        $this->predefinedDataTemplates = $predefinedDataTemplates;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        if ($data instanceof DataObject\Data\Hotspotimage) {
            $imageId = null;
            if ($data->getImage()) {
                $imageId = $data->getImage()->getId();
            }

            $metaData = [
                'hotspots' => $data->getHotspots(),
                'marker' => $data->getMarker(),
                'crop' => $data->getCrop(),
            ];

            $metaData = Serialize::serialize($metaData);

            return [
                $this->getName() . '__image' => $imageId,
                $this->getName() . '__hotspots' => $metaData,
            ];
        }

        return [
            $this->getName() . '__image' => null,
            $this->getName() . '__hotspots' => null,
        ];
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\Hotspotimage
    {
        $imageId = $data[$this->getName() . '__image'];
        if ($imageId === null) {
            return null;
        }
        $image = Asset::getById($imageId);
        if ($image) {
            $metaData = $data[$this->getName() . '__hotspots'];

            // check if the data is JSON (backward compatibility)
            $md = json_decode($metaData, true);
            if (!$md) {
                $md = Serialize::unserialize($metaData);
            } elseif (is_array($md)) {
                $md['hotspots'] = $md;
            }

            $hotspots = empty($md['hotspots']) ? [] : $md['hotspots'];
            $marker = empty($md['marker']) ? [] : $md['marker'];
            $crop = empty($md['crop']) ? [] : $md['crop'];

            $rewritePath = function ($data) {
                if (!is_array($data)) {
                    return [];
                }

                foreach ($data as &$element) {
                    if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                        foreach ($element['data'] as &$metaData) {
                            // this is for backward compatibility (Array vs. MarkerHotspotItem)
                            if (is_array($metaData)) {
                                $metaData = new Element\Data\MarkerHotspotItem($metaData);
                            }
                        }
                    }
                }

                return $data;
            };

            $hotspots = $rewritePath($hotspots);
            $marker = $rewritePath($marker);

            $value = new DataObject\Data\Hotspotimage((int)$imageId, $hotspots, $marker, $crop);

            if (isset($params['owner'])) {
                $value->_setOwner($params['owner']);
                $value->_setOwnerFieldname($params['fieldname']);
                $value->_setOwnerLanguage($params['language'] ?? null);
            }

            return $value;
        }

        return null;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data instanceof DataObject\Data\Hotspotimage) {
            $imageId = null;
            if ($data->getImage()) {
                $imageId = $data->getImage()->getId();
            }

            $rewritePath = function ($data) {
                if (!is_array($data)) {
                    return [];
                }

                foreach ($data as &$element) {
                    if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                        foreach ($element['data'] as &$metaData) {
                            if ($metaData['value'] instanceof Element\ElementInterface) {
                                $metaData['value'] = $metaData['value']->getRealFullPath();
                            }
                        }
                    }
                }

                return $data;
            };

            $marker = $rewritePath($data->getMarker());
            $hotspots = $rewritePath($data->getHotspots());

            return [
                'id' => $imageId,
                'hotspots' => $hotspots,
                'marker' => $marker,
                'crop' => $data->getCrop(),
            ];
        }

        return null;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\Hotspotimage
    {
        $rewritePath = function ($data) {
            if (!is_array($data)) {
                return [];
            }

            foreach ($data as &$element) {
                if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                    foreach ($element['data'] as &$metaData) {
                        $metaData = new Element\Data\MarkerHotspotItem($metaData);
                        if (in_array($metaData['type'], ['object', 'asset', 'document']) && $metaData->getValue()) {
                            $el = Element\Service::getElementByPath($metaData['type'], $metaData->getValue());
                            $metaData['value'] = $el;
                        }
                    }
                }
            }

            return $data;
        };

        if (array_key_exists('marker', $data) && is_array($data['marker']) && count($data['marker']) > 0) {
            $data['marker'] = $rewritePath($data['marker']);
        }

        if (array_key_exists('hotspots', $data) && is_array($data['hotspots']) && count($data['hotspots']) > 0) {
            $data['hotspots'] = $rewritePath($data['hotspots']);
        }

        if ($data && isset($data['id']) && (int)$data['id'] > 0) {
            return new DataObject\Data\Hotspotimage($data['id'], $data['hotspots'] ?? [], $data['marker'] ?? [], $data['crop'] ?? []);
        }

        return null;
    }

    public function getDataFromGridEditor(array $data, DataObject\Concrete $object = null, array $params = []): DataObject\Data\Hotspotimage
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            return '<img src="/admin/asset/get-image-thumbnail?id=' . $data->getImage()->getId() . '&width=100&height=100&aspectratio=true" />';
        }

        return '';
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            return $data->getImage()->getFrontendFullPath();
        }

        return '';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function getCacheTags(mixed $data, array $tags = []): array
    {
        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            if (!array_key_exists($data->getImage()->getCacheTag(), $tags)) {
                $tags = $data->getImage()->getCacheTags($tags);
            }

            $getMetaDataCacheTags = function ($d, $tags) {
                if (!is_array($d)) {
                    return $tags;
                }

                foreach ($d as $element) {
                    if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                        foreach ($element['data'] as $metaData) {
                            if ($metaData['value'] instanceof Element\ElementInterface) {
                                if (!array_key_exists($metaData['value']->getCacheTag(), $tags)) {
                                    $tags = $metaData['value']->getCacheTags($tags);
                                }
                            }
                        }
                    }
                }

                return $tags;
            };

            $marker = $data->getMarker();
            $hotspots = $data->getHotspots();

            $tags = $getMetaDataCacheTags($marker, $tags);
            $tags = $getMetaDataCacheTags($hotspots, $tags);
        }

        return $tags;
    }

    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            $dependencies['asset_' . $data->getImage()->getId()] = [
                'id' => $data->getImage()->getId(),
                'type' => 'asset',
            ];

            $getMetaDataDependencies = function ($data, $dependencies) {
                if (!is_array($data)) {
                    return $dependencies;
                }

                foreach ($data as $element) {
                    if (array_key_exists('data', $element) && is_array($element['data']) && count($element['data']) > 0) {
                        foreach ($element['data'] as $metaData) {
                            if ($metaData['value'] instanceof Element\ElementInterface) {
                                $dependencies[$metaData['type'] . '_' . $metaData['value']->getId()] = [
                                    'id' => $metaData['value']->getId(),
                                    'type' => $metaData['type'],
                                ];
                            }
                        }
                    }
                }

                return $dependencies;
            };

            $dependencies = $getMetaDataDependencies($data->getMarker(), $dependencies);
            $dependencies = $getMetaDataDependencies($data->getHotspots(), $dependencies);
        }

        return $dependencies;
    }

    public function getDataForGrid(?DataObject\Data\Hotspotimage $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);
        $this->doRewriteIds($container, $idMapping, $params, $data);

        return $data;
    }

    /**
     * @internal
     */
    public function doRewriteIds(mixed $object, array $idMapping, array $params, mixed $data): mixed
    {
        if ($data instanceof DataObject\Data\Hotspotimage && $data->getImage()) {
            $id = $data->getImage()->getId();
            if (array_key_exists('asset', $idMapping) && array_key_exists($id, $idMapping['asset'])) {
                $data->setImage(Asset\Image::getById($idMapping['asset'][$id]));

                // reset hotspot, marker & crop
                $data->setHotspots(null);
                $data->setMarker(null);
                $data->setCrop(null);
            }

            if ($data->getHotspots()) {
                $data->setHotspots($this->rewriteIdsInDataEntries($data->getHotspots(), $idMapping));
            }
            if ($data->getMarker()) {
                $data->setMarker($this->rewriteIdsInDataEntries($data->getMarker(), $idMapping));
            }
        }

        return $data;
    }

    private function rewriteIdsInDataEntries(?array $dataArray, array $idMapping): array
    {
        $newDataArray = [];
        if ($dataArray) {
            foreach ($dataArray as $dataArrayEntry) {
                if ($dataArrayEntry['data']) {
                    $newData = [];
                    foreach ($dataArrayEntry['data'] as $dataEntry) {
                        //rewrite objects
                        if ($dataEntry['type'] == 'object' && $dataEntry['value']) {
                            $id = $dataEntry['value']->getId();
                            if (array_key_exists('object', $idMapping) && array_key_exists($id, $idMapping['object'])) {
                                $dataEntry['value'] = DataObject::getById($idMapping['object'][$id]);
                            }
                        }
                        //rewrite assets
                        if ($dataEntry['type'] == 'asset' && $dataEntry['value']) {
                            $id = $dataEntry['value']->getId();
                            if (array_key_exists('asset', $idMapping) && array_key_exists($id, $idMapping['asset'])) {
                                $dataEntry['value'] = Asset::getById($idMapping['asset'][$id]);
                            }
                        }
                        //rewrite documents
                        if ($dataEntry['type'] == 'document' && $dataEntry['value']) {
                            $id = $dataEntry['value']->getId();
                            if (array_key_exists('document', $idMapping) && array_key_exists($id, $idMapping['document'])) {
                                $dataEntry['value'] = Document::getById($idMapping['document'][$id]);
                            }
                        }
                        $newData[] = $dataEntry;
                    }
                    $dataArrayEntry['data'] = $newData;
                }
                $newDataArray[] = $dataArrayEntry;
            }
        }

        return $newDataArray;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof DataObject\Data\Hotspotimage
            || !$newValue instanceof DataObject\Data\Hotspotimage) {
            return false;
        }

        $fd = new Image();
        if (!$fd->isEqual($oldValue->getImage(), $newValue->getImage())) {
            return false;
        }

        $oldValue = [
            'hotspots' => $oldValue->getHotspots(),
            'marker' => $oldValue->getMarker(),
            'crop' => $oldValue->getCrop(),
        ];

        $newValue = [
            'hotspots' => $newValue->getHotspots(),
            'marker' => $newValue->getMarker(),
            'crop' => $newValue->getCrop(),
        ];

        if (!$this->isEqualArray($oldValue, $newValue)) {
            return false;
        }

        return true;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' .DataObject\Data\Hotspotimage::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' .DataObject\Data\Hotspotimage::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\Hotspotimage::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\Hotspotimage::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Data\Hotspotimage) {
            $result = [];
            $result['hotspots'] = $value->getHotspots();
            $result['marker'] = $value->getMarker();
            $result['crop'] = $value->getCrop();

            $image = $value->getImage();
            if ($image) {
                $type = Element\Service::getElementType($image);
                $id = $image->getId();
                $result['image'] = [
                    'type' => $type,
                    'id' => $id,
                ];
            }

            return $result;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Data\Hotspotimage
    {
        if (is_array($value)) {
            $image = new DataObject\Data\Hotspotimage();
            $image->setHotspots($value['hotspots']);
            $image->setMarker($value['marker']);
            $image->setCrop($value['crop']);
            if ($value['image'] ?? false) {
                $type = $value['image']['type'];
                $id = $value['image']['id'];
                $asset = Element\Service::getElementById($type, $id);
                if ($asset instanceof Asset\Image) {
                    $image->setImage($asset);
                }
            }

            return $image;
        }

        return null;
    }

    /**
     * Filter by relation feature
     *
     *
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $name = $params['name'] ?: $this->name;
        $name .= '__image';

        return $this->getRelationFilterCondition($value, $operator, $name);
    }

    public function getColumnType(): array
    {
        return [
            'image' => 'int(11)',
            'hotspots' => 'text',
        ];
    }

    public function getQueryColumnType(): array
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'hotspotimage';
    }
}
