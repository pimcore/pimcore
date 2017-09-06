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

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Link extends Model\Document\Tag
{
    /**
     * Contains the data for the link
     *
     * @var array
     */
    public $data;

    /**
     * @see Document\Tag\TagInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'link';
    }

    /**
     * @see Document\Tag\TagInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        // update path if internal link
        $this->updatePathFromInternal(true);

        return $this->data;
    }

    /**
     * @see Document\Tag\TagInterface::getDataEditmode
     *
     * @return mixed
     */
    public function getDataEditmode()
    {
        // update path if internal link
        $this->updatePathFromInternal(true,true);

        return $this->data;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     *
     * @return string
     */
    public function frontend()
    {
        $url = $this->getHref();

        if (strlen($url) > 0) {
            // add attributes to link
            $attribs = [];
            if (is_array($this->options)) {
                foreach ($this->options as $key => $value) {
                    if (is_string($value) || is_numeric($value)) {
                        $attribs[] = $key.'="'.$value.'"';
                    }
                }
            }
            // add attributes to link
            $allowedAttributes = [
                'charset',
                'coords',
                'hreflang',
                'name',
                'rel',
                'rev',
                'shape',
                'target',
                'accesskey',
                'class',
                'dir',
                'id',
                'lang',
                'style',
                'tabindex',
                'title',
                'xml:lang',
                'onblur',
                'onclick',
                'ondblclick',
                'onfocus',
                'onmousedown',
                'onmousemove',
                'onmouseout',
                'onmouseover',
                'onmouseup',
                'onkeydown',
                'onkeypress',
                'onkeyup',
            ];
            $defaultAttributes = [];

            if (!is_array($this->options)) {
                $this->options = [];
            }
            if (!is_array($this->data)) {
                $this->data = [];
            }

            $availableAttribs = array_merge($defaultAttributes, $this->data, $this->options);

            foreach ($availableAttribs as $key => $value) {
                if ((is_string($value) || is_numeric($value)) && in_array($key, $allowedAttributes)) {
                    if (!empty($value)) {
                        $attribs[] = $key.'="'.$value.'"';
                    }
                }
            }

            $attribs = array_unique($attribs);

            if (array_key_exists('attributes', $this->data) && !empty($this->data['attributes'])) {
                $attribs[] = $this->data['attributes'];
            }

            return '<a href="'.$url.'" '.implode(' ', $attribs).'>'.htmlspecialchars($this->data['text']).'</a>';
        }

        return '';
    }

    /**
     * @return bool
     */
    public function checkValidity()
    {
        $sane = true;
        if (is_array($this->data) && $this->data['internal']) {
            if ($this->data['internalType'] == 'document') {
                $doc = Document::getById($this->data['internalId']);
                if (!$doc) {
                    $sane = false;
                    Logger::notice(
                        'Detected insane relation, removing reference to non existent document with id ['.$this->getDocumentId(
                        ).']'
                    );
                    $new = Document\Tag::factory($this->getType(), $this->getName(), $this->getDocumentId());
                    $this->data = $new->getData();
                }
            } elseif ($this->data['internalType'] == 'asset') {
                $asset = Asset::getById($this->data['internalId']);
                if (!$asset) {
                    $sane = false;
                    Logger::notice(
                        'Detected insane relation, removing reference to non existent asset with id ['.$this->getDocumentId(
                        ).']'
                    );
                    $new = Document\Tag::factory($this->getType(), $this->getName(), $this->getDocumentId());
                    $this->data = $new->getData();
                }
            } elseif ($this->data['internalType'] == 'object') {
                $object = Model\DataObject\Concrete::getById($this->data['internalId']);
                if (!$object) {
                    $sane = false;
                    Logger::notice(
                        'Detected insane relation, removing reference to non existent object with id ['.$this->getDocumentId(
                        ).']'
                    );
                    $new = Document\Tag::factory($this->getType(), $this->getName(), $this->getDocumentId());
                    $this->data = $new->getData();
                }
            }
        }

        return $sane;
    }

    /**
     * @return string
     */
    public function getHref()
    {
        $this->updatePathFromInternal();

        $url = $this->data['path'] ?? '';

        if (strlen($this->data['parameters'] ?? '') > 0) {
            $url .= '?'.str_replace('?', '', $this->getParameters());
        }

        if (strlen($this->data['anchor'] ?? '') > 0) {
            $url .= '#'.str_replace('#', '', $this->getAnchor());
        }

        return $url;
    }

    /**
     * @param bool $realPath
     */
    protected function updatePathFromInternal($realPath = false, $editmode = false)
    {
        $method = 'getFullPath';
        if ($realPath) {
            $method = 'getRealFullPath';
        }

        if (isset($this->data['internal']) && $this->data['internal']) {
            if ($this->data['internalType'] == 'document') {
                if ($doc = Document::getById($this->data['internalId'])) {
                    if ($editmode || (!Document::doHideUnpublished() || $doc->isPublished())) {
                        $this->data['path'] = $doc->$method();
                    } else {
                        $this->data['path'] = '';
                    }
                }
            } elseif ($this->data['internalType'] == 'asset') {
                if ($asset = Asset::getById($this->data['internalId'])) {
                    $this->data['path'] = $asset->$method();
                }
            } elseif ($this->data['internalType'] == 'object') {
                if ($object = Model\DataObject::getById($this->data['internalId'])) {
                    if ($editmode) {
                        $this->data['path'] = $object->getFullPath();
                    } else {
                        if ($linkGenerator = $object->getClass()->getLinkGenerator()) {
                            if ($realPath) {
                                $this->data['path'] = $object->getFullPath();
                            } else {
                                $this->data['path'] = $linkGenerator->generate(
                                    $object,
                                    [
                                        'document' => Document::getById($this->getDocumentId()),
                                        'context' => $this,
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->data['text'];
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->data['text'] = $text;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->data['target'];
    }

    /**
     * @return string
     */
    public function getParameters()
    {
        return $this->data['parameters'];
    }

    /**
     * @return string
     */
    public function getAnchor()
    {
        return $this->data['anchor'];
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->data['title'];
    }

    /**
     * @return string
     */
    public function getRel()
    {
        return $this->data['rel'];
    }

    /**
     * @return string
     */
    public function getTabindex()
    {
        return $this->data['tabindex'];
    }

    /**
     * @return string
     */
    public function getAccesskey()
    {
        return $this->data['accesskey'];
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
        $this->data = \Pimcore\Tool\Serialize::unserialize($data);
        if (!is_array($this->data)) {
            $this->data = [];
        }

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
        if (!is_array($data)) {
            $data = [];
        }

        $path = $data['path'];

        if (!empty($path)) {
            if ($data["linktype"] == "internal" && $data["internalType"]) {
                $target = Model\Element\Service::getElementByPath($data["internalType"], $path);
                if ($target) {
                    $data['internal'] = true;
                    $data['internalId'] = $target->getId();
                    $data['internalType'] = $data["internalType"];
                }
            }

            if (!$target) {
                if ($target = Document::getByPath($path)) {
                    $data['internal'] = true;
                    $data['internalId'] = $target->getId();
                    $data['internalType'] = 'document';
                } elseif ($target = Asset::getByPath($path)) {
                    $data['internal'] = true;
                    $data['internalId'] = $target->getId();
                    $data['internalType'] = 'asset';
                } elseif ($target = Model\DataObject\Concrete::getByPath($path)) {
                    $data['internal'] = true;
                    $data['internalId'] = $target->getId();
                    $data['internalType'] = 'object';
                } else {
                    $data['internal'] = false;
                    $data['internalId'] = null;
                    $data['internalType'] = null;
                    $data['linktype'] = 'direct';
                }

                if ($target) {
                    $data['linktype'] = 'internal';
                }
            }
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return strlen($this->getHref()) < 1;
    }

    /**
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = [];

        if (is_array($this->data) && $this->data['internal']) {
            if (intval($this->data['internalId']) > 0) {
                if ($this->data['internalType'] == 'document') {
                    if ($doc = Document::getById($this->data['internalId'])) {
                        $key = 'document_'.$doc->getId();

                        $dependencies[$key] = [
                            'id' => $doc->getId(),
                            'type' => 'document',
                        ];
                    }
                } elseif ($this->data['internalType'] == 'asset') {
                    if ($asset = Asset::getById($this->data['internalId'])) {
                        $key = 'asset_'.$asset->getId();

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
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param $document
     * @param mixed $params
     * @param null $idMapper
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        if ($wsElement->value->data instanceof \stdClass) {
            $wsElement->value->data = (array)$wsElement->value->data;
        }

        if (empty($wsElement->value->data) or is_array($wsElement->value->data)) {
            $this->data = $wsElement->value->data;
            if ($this->data['internal']) {
                if (intval($this->data['internalId']) > 0) {
                    $id = $this->data['internalId'];

                    if ($this->data['internalType'] == 'document') {
                        if ($idMapper) {
                            $id = $idMapper->getMappedId('document', $id);
                        }
                        $referencedDocument = Document::getById($id);
                        if (!$referencedDocument instanceof Document) {
                            if ($idMapper && $idMapper->ignoreMappingFailures()) {
                                $idMapper->recordMappingFailure(
                                    'document',
                                    $this->getDocumentId(),
                                    $this->data['internalType'],
                                    $this->data['internalId']
                                );
                            } else {
                                throw new \Exception(
                                    'cannot get values from web service import - link references unknown document with id [ '.$this->data['internalId'].' ] '
                                );
                            }
                        }
                    } elseif ($this->data['internalType'] == 'asset') {
                        if ($idMapper) {
                            $id = $idMapper->getMappedId('document', $id);
                        }
                        $referencedAsset = Asset::getById($id);
                        if (!$referencedAsset instanceof Asset) {
                            if ($idMapper && $idMapper->ignoreMappingFailures()) {
                                $idMapper->recordMappingFailure(
                                    'document',
                                    $this->getDocumentId(),
                                    $this->data['internalType'],
                                    $this->data['internalId']
                                );
                            } else {
                                throw new \Exception(
                                    'cannot get values from web service import - link references unknown asset with id [ '.$this->data['internalId'].' ] '
                                );
                            }
                        }
                    }
                }
            }
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
    }

    /**
     * Returns the current tag's data for web service export
     *
     * @param $document
     * @param mixed $params
     * @abstract
     *
     * @return array
     */
    public function getForWebserviceExport($document = null, $params = [])
    {
        $el = parent::getForWebserviceExport($document, $params);
        if ($this->data['internal']) {
            if (intval($this->data['internalId']) > 0) {
                if ($this->data['internalType'] == 'document') {
                    $referencedDocument = Document::getById($this->data['internalId']);
                    if (!$referencedDocument instanceof Document) {
                        //detected broken link
                        $document = Document::getById($this->getDocumentId());
                    }
                } elseif ($this->data['internalType'] == 'asset') {
                    $referencedAsset = Asset::getById($this->data['internalId']);
                    if (!$referencedAsset instanceof Asset) {
                        //detected broken link
                        $document = Document::getById($this->getDocumentId());
                    }
                }
            }
        }

        $el->data = $this->data;

        return $el;
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
        if ($this->data['internal']) {
            $type = $this->data['internalType'];
            $id = (int)$this->data['internalId'];

            if (array_key_exists($type, $idMapping)) {
                if (array_key_exists($id, $idMapping[$type])) {
                    $this->data['internalId'] = $idMapping[$type][$id];
                    $this->getHref();
                }
            }
        }
    }
}
