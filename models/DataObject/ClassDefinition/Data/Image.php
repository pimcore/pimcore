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
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;

class Image extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, IdRewriterInterface
{
    use Extension\ColumnType;
    use ImageTrait;
    use Extension\QueryColumnType;
    use Data\Extension\RelationFilterConditionParser;

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
     * @param Asset\Image|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Asset\Image) {
            return $data->getId();
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param int|null $data
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
     * @param Asset\Image|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        if ($data instanceof Asset\Image) {
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

        return null;
    }

    /**
     * @param Asset\Image $data
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
     * @param array|null $data
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
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     * @param array $params
     *
     * @throws Element\ValidationException
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && !$data instanceof Asset\Image) {
            throw new Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }
        if ($data !== null && !$data instanceof Asset\Image) {
            throw new Element\ValidationException('Invalid data in field `'.$this->getName().'`');
        }
    }

    /**
     * @param array|null $data
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
     * { @inheritdoc }
     */
    public function rewriteIds(/** mixed */ $container, /** array */ $idMapping, /** array */ $params = []) /** :mixed */
    {
        $data = $this->getDataFromObjectParam($container, $params);
        if ($data instanceof Asset\Image) {
            if (array_key_exists('asset', $idMapping) && array_key_exists($data->getId(), $idMapping['asset'])) {
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

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (isset($value['id'])) {
            return Asset\Image::getById($value['id']);
        }

        return null;
    }

    /**
     * Filter by relation feature
     *
     * @param array|string|null $value
     * @param string            $operator
     * @param array             $params
     *
     * @return string
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        $name = $params['name'] ?: $this->name;

        return $this->getRelationFilterCondition($value, $operator, $name);
    }
}
