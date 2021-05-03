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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;

class Image extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Extension\ColumnType;
    use ImageTrait;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'image';

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'int(11)';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'int(11)';

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Asset) {
            return $data->getId();
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param int $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Asset|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ((int)$data > 0) {
            return Asset\Image::getById($data);
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param Asset|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        if ($data instanceof Asset) {
            return $data->getId();
        }

        return null;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param Asset\Image|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Asset\Image) {
            return $data->getObjectVars();
        }

        return $data;
    }

    /**
     * @param Asset $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Asset\Image|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data && (int)$data['id'] > 0) {
            return Asset\Image::getById($data['id']);
        }

        return null;
    }

    /**
     * @param int $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Asset
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param Asset\Image|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof Asset\Image) {
            return '<img src="/admin/asset/get-image-thumbnail?id=' . $data->getId() . '&width=100&height=100&aspectratio=true" />';
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Element\ElementInterface) {
            return $data->getRealFullPath();
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags($data, array $tags = [])
    {
        if ($data instanceof Asset\Image) {
            if (!array_key_exists($data->getCacheTag(), $tags)) {
                $tags = $data->getCacheTags($tags);
            }
        }

        return $tags;
    }

    /**
     * @param Asset|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if ($data instanceof Asset) {
            $dependencies['asset_' . $data->getId()] = [
                'id' => $data->getId(),
                'type' => 'asset',
            ];
        }

        return $dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param Asset\Image|null $data
     * @param Model\DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        $versionPreview = null;
        if ($data instanceof Asset\Image) {
            $versionPreview = '/admin/asset/get-image-thumbnail?id=' . $data->getId() . '&width=150&height=150&aspectratio=true';
        }

        if ($versionPreview) {
            $value = [];
            $value['src'] = $versionPreview;
            $value['type'] = 'img';

            return $value;
        } else {
            return '';
        }
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     *
     * @return Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Asset\Image) {
            if (array_key_exists('asset', $idMapping) and array_key_exists($data->getId(), $idMapping['asset'])) {
                return Asset::getById($idMapping['asset'][$data->getId()]);
            }
        }

        return $data;
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\Image $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->uploadPath = $masterDefinition->uploadPath;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * @param Asset|null $oldValue
     * @param Asset|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $oldValue = $oldValue instanceof Asset ? $oldValue->getId() : null;
        $newValue = $newValue instanceof Asset ? $newValue->getId() : null;

        return $oldValue === $newValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Asset\Image::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Asset\Image::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . Asset\Image::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Asset\Image::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof \Pimcore\Model\Asset\Image) {
            return [
                'type' => 'asset',
                'id' => $value->getId(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        $id = $value['id'];
        if (intval($id) > 0) {
            return Asset\Image::getById($id);
        }
    }
}
