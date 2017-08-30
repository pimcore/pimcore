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
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\DataObject;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Href extends Model\Document\Tag
{
    /**
     * ID of the source object
     *
     * @var int
     */
    public $id;

    /**
     * Type of the source object (document, asset, object)
     *
     * @var string
     */
    public $type;

    /**
     * Subtype of the source object (eg. page, link, video, news, ...)
     *
     * @var string
     */
    public $subtype;

    /**
     * Contains the source object
     *
     * @var mixed
     */
    public $element;

    /**
     * @see Document\Tag\TagInterface::getType
     *
     * @return string
     */
    public function getType()
    {

        //TODO: getType != $type ... that might be dangerous
        return 'href';
    }

    /**
     * @see Document\Tag\TagInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subtype' => $this->subtype
        ];
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return mixed
     */
    public function getDataEditmode()
    {
        $this->setElement();

        if ($this->element instanceof Element\ElementInterface) {
            return [
                'id' => $this->id,
                'path' => $this->element->getRealFullPath(),
                'elementType' => $this->type,
                'subtype' => $this->subtype
            ];
        }

        return null;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     *
     * @return string
     */
    public function frontend()
    {
        $this->setElement();

        //don't give unpublished elements in frontend
        if (Document::doHideUnpublished() and !Element\Service::isPublished($this->element)) {
            return '';
        }

        if ($this->element instanceof Element\ElementInterface) {
            return $this->element->getFullPath();
        }

        return '';
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromResource($data)
    {
        if (!empty($data)) {
            $data = \Pimcore\Tool\Serialize::unserialize($data);
        }

        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->subtype = $data['subtype'];

        $this->setElement();

        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->subtype = $data['subtype'];

        $this->setElement();

        return $this;
    }

    /**
     * Sets the element by the data stored for the object
     *
     * @return $this
     */
    private function setElement()
    {
        if (!$this->element) {
            $this->element = Element\Service::getElementById($this->type, $this->id);
        }

        return $this;
    }

    /**
     * Returns one of them: Document, Object, Asset
     *
     * @return mixed
     */
    public function getElement()
    {
        $this->setElement();

        //don't give unpublished elements in frontend
        if (Document::doHideUnpublished() and !Element\Service::isPublished($this->element)) {
            return false;
        }

        return $this->element;
    }

    /**
     * Returns teh path of the linked element
     *
     * @return mixed
     */
    public function getFullPath()
    {
        $this->setElement();

        //don't give unpublished elements in frontend
        if (Document::doHideUnpublished() and !Element\Service::isPublished($this->element)) {
            return false;
        }
        if ($this->element instanceof Element\ElementInterface) {
            return $this->element->getFullPath();
        }

        return;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        $this->setElement();

        if ($this->getElement() instanceof Element\ElementInterface) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = [];
        $this->setElement();

        if ($this->element instanceof Element\ElementInterface) {
            $elementType = Element\Service::getElementType($this->element);
            $key = $elementType . '_' . $this->element->getId();
            $dependencies[$key] = [
                'id' => $this->element->getId(),
                'type' => $elementType
            ];
        }

        return $dependencies;
    }

    /**
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param $document
     * @param mixed $params
     * @param null $idMapper
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $wsElement->value;
        if ($data->id !== null) {
            $this->type = $data->type;
            $this->subtype = $data->subtype;
            $this->id = $data->id;

            if (!is_numeric($this->id)) {
                throw new \Exception('cannot get values from web service import - id is not valid');
            }

            if ($idMapper) {
                $this->id = $idMapper->getMappedId($this->type, $data->id);
            }

            if ($this->type == 'asset') {
                $this->element = Asset::getById($this->id);
                if (!$this->element instanceof Asset) {
                    if ($idMapper && $idMapper->ignoreMappingFailures()) {
                        $idMapper->recordMappingFailure('document', $this->getDocumentId(), $data->type, $data->id);
                    } else {
                        throw new \Exception('cannot get values from web service import - referenced asset with id [ '.$data->id.' ] is unknown');
                    }
                }
            } elseif ($this->type == 'document') {
                $this->element = Document::getById($this->id);
                if (!$this->element instanceof Document) {
                    if ($idMapper && $idMapper->ignoreMappingFailures()) {
                        $idMapper->recordMappingFailure('document', $this->getDocumentId(), $data->type, $data->id);
                    } else {
                        throw new \Exception('cannot get values from web service import - referenced document with id [ '.$data->id.' ] is unknown');
                    }
                }
            } elseif ($this->type == 'object') {
                $this->element = DataObject\AbstractObject::getById($this->id);
                if (!$this->element instanceof DataObject\AbstractObject) {
                    if ($idMapper && $idMapper->ignoreMappingFailures()) {
                        $idMapper->recordMappingFailure('document', $this->getDocumentId(), $data->type, $data->id);
                    } else {
                        throw new \Exception('cannot get values from web service import - referenced object with id [ '.$data->id.' ] is unknown');
                    }
                }
            } else {
                if ($idMapper && $idMapper->ignoreMappingFailures()) {
                    $idMapper->recordMappingFailure('document', $this->getDocumentId(), $data->type, $data->id);
                } else {
                    throw new \Exception('cannot get values from web service import - type is not valid');
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function checkValidity()
    {
        $sane = true;
        if ($this->id) {
            $el = Element\Service::getElementById($this->type, $this->id);
            if (!$el instanceof Element\ElementInterface) {
                $sane = false;
                Logger::notice('Detected insane relation, removing reference to non existent '.$this->type.' with id ['.$this->id.']');
                $this->id = null;
                $this->type = null;
                $this->subtype=null;
                $this->element=null;
            }
        }

        return $sane;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();
        $blockedVars = ['element'];
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     * this method is called by Document\Service::loadAllDocumentFields() to load all lazy loading fields
     */
    public function load()
    {
        if (!$this->element) {
            $this->setElement();
        }
    }

    /**
     * @param int $id
     *
     * @return Document\Tag\Href
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * @param string $subtype
     *
     * @return Document\Tag\Href
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
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
     * @param array $idMapping
     */
    public function rewriteIds($idMapping)
    {
        if (array_key_exists($this->type, $idMapping) and array_key_exists($this->getId(), $idMapping[$this->type])) {
            $this->id = $idMapping[$this->type][$this->getId()];
        }
    }
}
