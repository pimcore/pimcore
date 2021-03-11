<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Tool\Serialize;

class Link extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;
    use DataObject\Traits\ObjectVarTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'link';

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'text';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'text';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\Link';

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\Link $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Link) {
            $data = clone $data;
            $data->setOwner(null, '');

            if ($data->getLinktype() == 'internal' && !$data->getPath()) {
                $data->setLinktype(null);
                $data->setInternalType(null);
                if ($data->isEmpty()) {
                    return null;
                }
            }

            try {
                $this->checkValidity($data, true);
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
                $link->setOwner($params['owner'], $params['fieldname'], $params['language'] ?? null);
            }

            try {
                $this->checkValidity($link, true);
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
     * @param string $data
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
        $data->path = $data->getPath();

        return $data->getObjectVars();
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

    /** @inheritDoc */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value instanceof DataObject\Data\Link) {
            return $value->getObjectVars();
        }
    }

    /** @inheritDoc */
    public function unmarshal($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            $link = new DataObject\Data\Link();
            $link->setValues($data);
            $data = $link;
        }

        if ($data instanceof DataObject\Data\Link) {
            $target = Element\Service::getElementById($data->getInternalType(), $data->getInternal());
            if (!$target) {
                $data->setInternal(0);
                $data->setInternalType(null);
            }
        }

        return parent::unmarshal($data, $object, $params);
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if ($data) {
            if ($data instanceof DataObject\Data\Link) {
                if (intval($data->getInternal()) > 0) {
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
            if (intval($data->getInternal()) > 0) {
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
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     *
     * @return array
     */
    public function getCacheTags($data, $tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        if ($data instanceof DataObject\Data\Link and $data->getInternal()) {
            if (intval($data->getInternal()) > 0) {
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
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return string
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
     * fills object field data values from CSV Import String
     *
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return DataObject\Data\Link|null
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $value = Serialize::unserialize(base64_decode($importValue));
        if ($value instanceof DataObject\Data\Link) {
            return $value;
        }

        return null;
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param mixed $params
     *
     * @return string
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
     * converts data to be exposed via webservices
     *
     * @deprecated
     *
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return array|null
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Link) {
            $keys = $data->getObjectVars();
            foreach ($keys as $key => $value) {
                $method = 'get' . ucfirst($key);
                if (!method_exists($data, $method) or $key == 'object') {
                    unset($keys[$key]);
                }
            }

            return $keys;
        }

        return null;
    }

    /**
     * @deprecated
     *
     * @param mixed $value
     * @param Element\AbstractElement $relatedObject
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $params = [], $idMapper = null)
    {
        if ($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if (empty($value)) {
            return null;
        } elseif (is_array($value) and !empty($value['text']) and !empty($value['direct'])) {
            $link = new DataObject\Data\Link();
            foreach ($value as $key => $v) {
                $method = 'set' . ucfirst($key);
                if (method_exists($link, $method)) {
                    $link->$method($v);
                } else {
                    throw new \Exception('cannot get values from web service import - invalid data. Unknown DataObject\\Data\\Link setter [ ' . $method . ' ]');
                }
            }

            return $link;
        } elseif (is_array($value) and !empty($value['text']) and !empty($value['internalType']) and !empty($value['internal'])) {
            $id = $value['internal'];

            if ($idMapper) {
                $id = $idMapper->getMappedId($value['internalType'], $id);
            }

            $element = Element\Service::getElementById($value['internalType'], $id);
            if (!$element) {
                if ($idMapper && $idMapper->ignoreMappingFailures()) {
                    $idMapper->recordMappingFailure('object', $relatedObject->getId(), $value['internalType'], $value['internal']);

                    return null;
                } else {
                    throw new \Exception('cannot get values from web service import - referencing unknown internal element with type [ '.$value['internalType'].' ] and id [ '.$value['internal'].' ]');
                }
            }

            $link = new DataObject\Data\Link();
            foreach ($value as $key => $v) {
                $method = 'set' . ucfirst($key);
                if (method_exists($link, $method)) {
                    $link->$method($v);
                } else {
                    throw new \Exception('cannot get values from web service import - invalid data. Unknown DataObject\\Data\\Link setter [ ' . $method . ' ]');
                }
            }

            return $link;
        } elseif (is_array($value)) {
            $link = new DataObject\Data\Link();
            foreach ($value as $key => $v) {
                $method = 'set' . ucfirst($key);
                if (method_exists($link, $method)) {
                    $link->$method($v);
                } else {
                    throw new \Exception('cannot get values from web service import - invalid data. Unknown DataObject\\Data\\Link setter [ ' . $method . ' ]');
                }
            }

            return $link;
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
    }

    /** True if change is allowed in edit mode.
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
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
}
