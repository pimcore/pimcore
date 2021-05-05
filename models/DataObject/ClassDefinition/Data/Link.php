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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class Link extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\Traits\ObjectVarTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'link';

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'text';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'text';

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\Link|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Link) {
            $data = clone $data;
            $data->_setOwner(null);
            $data->_setOwnerFieldname('');
            $data->_setOwnerLanguage(null);

            if ($data->getLinktype() == 'internal' && !$data->getPath()) {
                $data->setLinktype(null);
                $data->setInternalType(null);
                if ($data->isEmpty()) {
                    return null;
                }
            }

            try {
                $this->checkValidity($data, true, $params);
            } catch (\Exception $e) {
                $data->setInternalType(null);
                $data->setInternal(null);
            }
        }

        if (is_null($data)) {
            return null;
        }

        return Serialize::serialize($data);
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\Link
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        $link = Serialize::unserialize($data);

        if ($link instanceof DataObject\Data\Link) {
            if (isset($params['owner'])) {
                $link->_setOwner($params['owner']);
                $link->_setOwnerFieldname($params['fieldname']);
                $link->_setOwnerLanguage($params['language'] ?? null);
            }

            try {
                $this->checkValidity($link, true, $params);
            } catch (\Exception $e) {
                $link->setInternalType(null);
                $link->setInternal(null);
            }
        }

        return $link;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param DataObject\Data\Link $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param DataObject\Data\Link|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if (!$data instanceof DataObject\Data\Link) {
            return null;
        }
        $dataArray = $data->getObjectVars();
        $dataArray['path'] = $data->getPath();

        return $dataArray;
    }

    /**
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\Link|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $link = new DataObject\Data\Link();
        $link->setValues($data);

        if ($link->isEmpty()) {
            return null;
        }

        return $link;
    }

    /**
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\Link|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if ($data) {
            if ($data instanceof DataObject\Data\Link) {
                if ((int)$data->getInternal() > 0) {
                    if ($data->getInternalType() == 'document') {
                        $doc = Document::getById($data->getInternal());
                        if (!$doc instanceof Document) {
                            throw new Element\ValidationException('invalid internal link, referenced document with id [' . $data->getInternal() . '] does not exist');
                        }
                    } elseif ($data->getInternalType() == 'asset') {
                        $asset = Asset::getById($data->getInternal());
                        if (!$asset instanceof Asset) {
                            throw new Element\ValidationException('invalid internal link, referenced asset with id [' . $data->getInternal() . '] does not exist');
                        }
                    }
                }
            }
        }
    }

    /**
     * @param DataObject\Data\Link|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if ($data instanceof DataObject\Data\Link and $data->getInternal()) {
            if ((int)$data->getInternal() > 0) {
                if ($data->getInternalType() == 'document') {
                    if ($doc = Document::getById($data->getInternal())) {
                        $key = 'document_' . $doc->getId();
                        $dependencies[$key] = [
                            'id' => $doc->getId(),
                            'type' => 'document',
                        ];
                    }
                } elseif ($data->getInternalType() == 'asset') {
                    if ($asset = Asset::getById($data->getInternal())) {
                        $key = 'asset_' . $asset->getId();

                        $dependencies[$key] = [
                            'id' => $asset->getId(),
                            'type' => 'asset',
                        ];
                    }
                }
            }
        }

        return $dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags($data, array $tags = [])
    {
        if ($data instanceof DataObject\Data\Link and $data->getInternal()) {
            if ((int)$data->getInternal() > 0) {
                if ($data->getInternalType() == 'document') {
                    if ($doc = Document::getById($data->getInternal())) {
                        if (!array_key_exists($doc->getCacheTag(), $tags)) {
                            $tags = $doc->getCacheTags($tags);
                        }
                    }
                } elseif ($data->getInternalType() == 'asset') {
                    if ($asset = Asset::getById($data->getInternal())) {
                        if (!array_key_exists($asset->getCacheTag(), $tags)) {
                            $tags = $asset->getCacheTags($tags);
                        }
                    }
                }
            }
        }

        return $tags;
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Link) {
            return base64_encode(Serialize::serialize($data));
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Link) {
            return $data->getText();
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either HTML or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param DataObject\Data\Link|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|string|null
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Link) {
            if ($data->getText()) {
                return $data->getText();
            } elseif ($data->getDirect()) {
                return $data->getDirect();
            }
        }

        return null;
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
        if ($data instanceof DataObject\Data\Link && $data->getLinktype() == 'internal') {
            $id = $data->getInternal();
            $type = $data->getInternalType();

            if (array_key_exists($type, $idMapping) and array_key_exists($id, $idMapping[$type])) {
                $data->setInternal($idMapping[$type][$id]);
            }
        }

        return $data;
    }

    /**
     *
     * @param DataObject\Data\Link|null $oldValue
     * @param DataObject\Data\Link|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if ($oldValue instanceof DataObject\Data\Link) {
            $oldValue = $oldValue->getObjectVars();
            //clear OwnerawareTrait fields
            unset($oldValue['_owner']);
            unset($oldValue['_fieldname']);
            unset($oldValue['_language']);
        }

        if ($newValue instanceof DataObject\Data\Link) {
            $newValue = $newValue->getObjectVars();
            //clear OwnerawareTrait fields
            unset($newValue['_owner']);
            unset($newValue['_fieldname']);
            unset($newValue['_language']);
        }

        return $this->isEqualArray($oldValue, $newValue);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Link::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Link::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\Link::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\Link::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof DataObject\Data\Link) {
            return $value->getObjectVars();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $link = new DataObject\Data\Link();
            $link->setValues($value);

            return $link;
        }

        return null;
    }
}
