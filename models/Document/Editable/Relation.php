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

namespace Pimcore\Model\Document\Editable;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Relation extends Model\Document\Editable
{
    /**
     * ID of the source object
     *
     * @internal
     *
     * @var int|null
     */
    protected $id;

    /**
     * Type of the source object (document, asset, object)
     *
     * @internal
     *
     * @var string|null
     */
    protected $type;

    /**
     * Subtype of the source object (eg. page, link, video, news, ...)
     *
     * @internal
     *
     * @var string|null
     */
    protected $subtype;

    /**
     * Contains the source object
     *
     * @internal
     *
     * @var mixed
     */
    protected $element;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        //TODO: getType != $type ... that might be dangerous
        return 'relation';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subtype' => $this->subtype,
        ];
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return array|null
     */
    public function getDataEditmode()
    {
        $this->setElement();

        if ($this->element instanceof Element\ElementInterface) {
            return [
                'id' => $this->id,
                'path' => $this->element->getRealFullPath(),
                'elementType' => $this->type,
                'subtype' => $this->subtype,
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function frontend()
    {
        $this->setElement();

        //don't give unpublished elements in frontend
        if (Element\Service::doHideUnpublished($this->element) && !Element\Service::isPublished($this->element)) {
            return '';
        }

        if ($this->element instanceof Element\ElementInterface) {
            return $this->element->getFullPath();
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        if (!empty($data)) {
            $data = \Pimcore\Tool\Serialize::unserialize($data);
        }

        $this->id = $data['id'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->subtype = $data['subtype'] ?? null;

        $this->setElement();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        $this->id = $data['id'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->subtype = $data['subtype'] ?? null;

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
     * @return Element\ElementInterface|false|null
     */
    public function getElement()
    {
        $this->setElement();

        //don't give unpublished elements in frontend
        if (Element\Service::doHideUnpublished($this->element) && !Element\Service::isPublished($this->element)) {
            return false;
        }

        return $this->element;
    }

    /**
     * Returns the path of the linked element
     *
     * @return string|false|null
     */
    public function getFullPath()
    {
        $this->setElement();

        //don't give unpublished elements in frontend
        if (Element\Service::doHideUnpublished($this->element) && !Element\Service::isPublished($this->element)) {
            return false;
        }
        if ($this->element instanceof Element\ElementInterface) {
            return $this->element->getFullPath();
        }

        return null;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
                'type' => $elementType,
            ];
        }

        return $dependencies;
    }

    /**
     * {@inheritdoc}
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
                $this->subtype = null;
                $this->element = null;
            }
        }

        return $sane;
    }

    /**
     * {@inheritdoc}
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
     * @return $this
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
     * @return $this
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
        if (array_key_exists($this->type, $idMapping) && array_key_exists($this->getId(), $idMapping[$this->type])) {
            $this->id = $idMapping[$this->type][$this->getId()];
        }
    }
}
