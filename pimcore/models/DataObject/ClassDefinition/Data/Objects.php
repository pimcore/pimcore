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
use Pimcore\Model\DataObject;
use Pimcore\Model\Element;

class Objects extends Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations
{
    use Model\DataObject\ClassDefinition\Data\Extension\Relation;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'objects';

    /**
     * @var int
     */
    public $width;

    /**
     * Type for the column to query
     *
     * @var int
     */
    public $height;

    /**
     * @var int
     */
    public $maxItems;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'text';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = 'array';

    /**
     * @var bool
     */
    public $relationType = true;

    /**
     * @return bool
     */
    public function getObjectsAllowed()
    {
        return true;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForResource
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $object) {
                if ($object instanceof DataObject\Concrete) {
                    $return[] = [
                        'dest_id' => $object->getId(),
                        'type' => 'object',
                        'fieldname' => $this->getName(),
                        'index' => $counter
                    ];
                }
                $counter++;
            }

            return $return;
        } elseif (is_array($data) and count($data) === 0) {
            //give empty array if data was not null
            return [];
        } else {
            //return null if data was null - this indicates data was not loaded
            return null;
        }
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataFromResource
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        $objects = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {
                $o = DataObject::getById($object['dest_id']);
                if ($o instanceof DataObject\Concrete) {
                    $objects[] = $o;
                }
            }
        }
        //must return array - otherwise this means data is not loaded
        return $objects;
    }

    /**
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @throws \Exception
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {

        //return null when data is not set
        if (!$data) {
            return null;
        }

        $ids = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {
                if ($object instanceof DataObject\Concrete) {
                    $ids[] = $object->getId();
                }
            }

            return ',' . implode(',', $ids) . ',';
        } elseif (is_array($data) && count($data) === 0) {
            return '';
        } else {
            throw new \Exception('invalid data passed to getDataForQueryResource - must be array and it is: ' . print_r($data, true));
        }
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForEditmode
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {
                if ($object instanceof DataObject\Concrete) {
                    $return[] = [$object->getId(), $object->getRealFullPath(), $object->getClassName()];
                }
            }
            if (empty($return)) {
                $return = false;
            }

            return $return;
        }

        return false;
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {

        //if not set, return null
        if ($data === null or $data === false) {
            return null;
        }

        $objects = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {
                $o = DataObject::getById($object['id']);
                if ($o) {
                    $objects[] = $o;
                }
            }
        }
        //must return array if data shall be set
        return $objects;
    }

    /**
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            $pathes = [];
            foreach ($data as $eo) {
                if ($eo instanceof Element\ElementInterface) {
                    $pathes[] = $eo->getRealFullPath();
                }
            }

            return $pathes;
        }
    }

    /**
     * @see DataObject\ClassDefinition\Data::getVersionPreview
     *
     * @param array $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $o) {
                if ($o instanceof Element\ElementInterface) {
                    $pathes[] = $o->getRealFullPath();
                }
            }

            return implode('<br />', $pathes);
        }
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);

        return $this;
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
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (is_array($data)) {
            foreach ($data as $o) {
                if (empty($o)) {
                    continue;
                }

                $allowClass = $this->allowObjectRelation($o);
                if (!$allowClass or !($o instanceof DataObject\Concrete)) {
                    if (!$allowClass && $o instanceof DataObject\Concrete) {
                        $id = $o->getId();
                    } else {
                        $id = '??';
                    }
                    throw new Element\ValidationException('Invalid object relation to object ['.$id.'] in field ' . $this->getName(), null, null);
                }
            }

            if ($this->getMaxItems() && count($data) > $this->getMaxItems()) {
                throw new Element\ValidationException('Number of allowed relations in field `' . $this->getName() . '` exceeded (max. ' . $this->getMaxItems() . ')');
            }
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
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
        } else {
            return null;
        }
    }

    /**
     * @param $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $values = explode(',', $importValue);

        $value = [];
        foreach ($values as $element) {
            if ($el = DataObject::getByPath($element)) {
                $value[] = $el;
            }
        }

        return $value;
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

        if ($this->getLazyLoading()) {
            return $tags;
        }

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {
                if ($object instanceof Element\ElementInterface && !array_key_exists($object->getCacheTag(), $tags)) {
                    $tags = $object->getCacheTags($tags);
                }
            }
        }

        return $tags;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $o) {
                if ($o instanceof DataObject\AbstractObject) {
                    $dependencies['object_' . $o->getId()] = [
                        'id' => $o->getId(),
                        'type' => 'object'
                    ];
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|mixed|null
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $items = [];
            foreach ($data as $eo) {
                if ($eo instanceof Element\ElementInterface) {
                    $items[] = [
                        'type' => $eo->getType(),
                        'id' => $eo->getId()
                    ];
                }
            }

            return $items;
        } else {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param null $object
     * @param mixed $params
     * @param null $idMapper
     *
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        $relatedObjects = [];
        if (empty($value)) {
            return null;
        } elseif (is_array($value)) {
            foreach ($value as $key => $item) {
                $item = (array) $item;
                $id = $item['id'];

                if ($idMapper) {
                    $id = $idMapper->getMappedId('object', $id);
                }

                $relatedObject = null;
                if ($id) {
                    $relatedObject = DataObject::getById($id);
                }

                if ($relatedObject instanceof DataObject\AbstractObject) {
                    $relatedObjects[] = $relatedObject;
                } else {
                    if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                        throw new \Exception('cannot get values from web service import - references unknown object with id [ '.$item['id'].' ]');
                    } else {
                        $idMapper->recordMappingFailure('object', $object->getId(), 'object', $item['id']);
                    }
                }
            }
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }

        return $relatedObjects;
    }

    /**
     * @param $object
     * @param array $params
     *
     * @return array|mixed|null
     */
    public function preGetData($object, $params = [])
    {
        $data = null;
        if ($object instanceof DataObject\Concrete) {
            $data = $object->{$this->getName()};
            if ($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())) {
                //$data = $this->getDataFromResource($object->getRelationData($this->getName(),true,null));
                $data = $this->load($object, ['force' => true]);

                $setter = 'set' . ucfirst($this->getName());
                if (method_exists($object, $setter)) {
                    $object->$setter($data);
                }
            }
        } elseif ($object instanceof DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $data = $object->{$this->getName()};
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData) {
            $data = $object->{$this->getName()};
        }

        if (DataObject\AbstractObject::doHideUnpublished() and is_array($data)) {
            $publishedList = [];
            foreach ($data as $listElement) {
                if (Element\Service::isPublished($listElement)) {
                    $publishedList[] = $listElement;
                }
            }

            return $publishedList;
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param $object
     * @param $data
     * @param array $params
     *
     * @return array|null
     */
    public function preSetData($object, $data, $params = [])
    {
        if ($data === null) {
            $data = [];
        }

        if ($object instanceof DataObject\Concrete) {
            if ($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())) {
                $object->addO__loadedLazyField($this->getName());
            }
        }

        return $data;
    }

    /**
     * @param string $fieldtype
     *
     * @return $this
     */
    public function setFieldtype($fieldtype)
    {
        $this->fieldtype = $fieldtype;

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldtype()
    {
        return $this->fieldtype;
    }

    /**
     * @param $maxItems
     *
     * @return $this
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $this->getAsIntegerCast($maxItems);

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxItems()
    {
        return $this->maxItems;
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     *
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
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

    /** See parent class.
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return null|\Pimcore_Date
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data) {
            $tabledata = $data[0]['data'];

            if (!$tabledata) {
                return;
            }

            $result = [];
            foreach ($tabledata as $in) {
                $out = [];
                $out['id'] = $in[0];
                $out['path'] = $in[1];
                $out['type'] = $in[2];
                $result[] = $out;
            }

            return $this->getDataFromEditmode($result, $object, $params);
        }

        return;
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
        $data = $this->rewriteIdsService($data, $idMapping);

        return $data;
    }

    /**
     * @param DataObject\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->maxItems = $masterDefinition->maxItems;
        $this->relationType = $masterDefinition->relationType;
    }

    /** Override point for Enriching the layout definition before the layout is returned to the admin interface.
     * @param $object DataObject\Concrete
     * @param array $context additional contextual data
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
    }

    /**
     * @return string
     */
    public function getPhpdocType()
    {
        return implode(' | ', $this->getPhpDocClassString(true));
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $element) {
                $type = Element\Service::getType($element);
                $id = $element->getId();
                $result[] = [
                    'type' => $type,
                    'id' => $id
                ];
            }

            return $result;
        }

        return null;
    }

    /** See marshal
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
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
    }
}
