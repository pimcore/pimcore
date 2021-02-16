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

namespace Pimcore\Model\Document\Editable;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Link extends Model\Document\Editable
{
    /**
     * Contains the data for the link
     *
     * @var array
     */
    public $data;

    /**
     * @see Pimcore\Model\Document\Editable;::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'link';
    }

    /**
     * @see EditableInterface::getData
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
     * @see EditableInterface::getDataEditmode
     *
     * @return mixed
     */
    public function getDataEditmode()
    {
        // update path if internal link
        $this->updatePathFromInternal(true, true);

        return $this->data;
    }

    /**
     * @inheritDoc
     */
    protected function getEditmodeElementClasses($options = []): array
    {
        // we don't want the class attribute being applied to the editable container element (<div>, only to the <a> tag inside
        // the default behavior of the parent method is to include the "class" attribute
        $classes = [
            'pimcore_editable',
            'pimcore_tag_' . $this->getType(),
            'pimcore_editable_' . $this->getType(),
        ];

        return $classes;
    }

    /**
     * @see EditableInterface::frontend
     *
     * @return string
     */
    public function frontend()
    {
        $url = $this->getHref();

        if (strlen($url) > 0) {
            if (!is_array($this->config)) {
                $this->config = [];
            }

            $prefix = '';
            $suffix = '';
            $noText = false;

            if (array_key_exists('textPrefix', $this->config)) {
                $prefix = $this->config['textPrefix'];
                unset($this->config['textPrefix']);
            }

            if (array_key_exists('textSuffix', $this->config)) {
                $suffix = $this->config['textSuffix'];
                unset($this->config['textSuffix']);
            }

            if (isset($this->config['noText']) && $this->config['noText'] == true) {
                $noText = true;
                unset($this->config['noText']);
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
                'draggable',
                'dropzone',
                'contextmenu',
                'id',
                'lang',
                'style',
                'tabindex',
                'title',
                'media',
                'download',
                'ping',
                'type',
                'referrerpolicy',
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

            if (!is_array($this->data)) {
                $this->data = [];
            }

            $availableAttribs = array_merge($defaultAttributes, $this->data, $this->config);

            // add attributes to link
            $attribs = [];
            foreach ($availableAttribs as $key => $value) {
                if ((is_string($value) || is_numeric($value))
                    && (strpos($key, 'data-') === 0 ||
                        strpos($key, 'aria-') === 0 ||
                        in_array($key, $allowedAttributes))) {
                    if (!empty($this->data[$key]) && !empty($this->config[$key])) {
                        $attribs[] = $key.'="'. $this->data[$key] .' '. $this->config[$key] .'"';
                    } elseif (!empty($value)) {
                        $attribs[] = $key.'="'.$value.'"';
                    }
                }
            }

            $attribs = array_unique($attribs);

            if (array_key_exists('attributes', $this->data) && !empty($this->data['attributes'])) {
                $attribs[] = $this->data['attributes'];
            }

            return '<a href="'.$url.'" '.implode(' ', $attribs).'>' . $prefix . ($noText ? '' : htmlspecialchars($this->data['text'])) . $suffix . '</a>';
        }

        return '';
    }

    /**
     * @return bool
     */
    public function checkValidity()
    {
        $sane = true;
        if (is_array($this->data) && isset($this->data['internal']) && $this->data['internal']) {
            if ($this->data['internalType'] == 'document') {
                $doc = Document::getById($this->data['internalId']);
                if (!$doc) {
                    $sane = false;
                    Logger::notice(
                        'Detected insane relation, removing reference to non existent document with id ['.$this->getDocumentId(
                        ).']'
                    );
                    $new = Document\Editable::factory($this->getType(), $this->getName(), $this->getDocumentId());
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
                    $new = Document\Editable::factory($this->getType(), $this->getName(), $this->getDocumentId());
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
                    $new = Document\Editable::factory($this->getType(), $this->getName(), $this->getDocumentId());
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
            $url .= (strpos($url, '?') !== false ? '&' : '?') . str_replace('?', '', $this->getParameters());
        }

        if (strlen($this->data['anchor'] ?? '') > 0) {
            $anchor = $this->getAnchor();
            $anchor = str_replace('"', urlencode('"'), $anchor);
            $url .= '#' . str_replace('#', '', $anchor);
        }

        return $url;
    }

    /**
     * @param bool $realPath
     * @param bool $editmode
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
                        if ($object instanceof Model\DataObject\Concrete) {
                            if ($linkGenerator = $object->getClass()->getLinkGenerator()) {
                                if ($realPath) {
                                    $this->data['path'] = $object->getFullPath();
                                } else {
                                    $this->data['path'] = $linkGenerator->generate(
                                        $object,
                                        [
                                            'document' => $this->getDocument(),
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
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->data['text'] ?? '';
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
        return $this->data['target'] ?? '';
    }

    /**
     * @return string
     */
    public function getParameters()
    {
        return $this->data['parameters'] ?? '';
    }

    /**
     * @return string
     */
    public function getAnchor()
    {
        return $this->data['anchor'] ?? '';
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->data['title'] ?? '';
    }

    /**
     * @return string
     */
    public function getRel()
    {
        return $this->data['rel'] ?? '';
    }

    /**
     * @return string
     */
    public function getTabindex()
    {
        return $this->data['tabindex'] ?? '';
    }

    /**
     * @return string
     */
    public function getAccesskey()
    {
        return $this->data['accesskey'] ?? '';
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->data['class'] ?? '';
    }

    /**
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->data['attributes'] ?? '';
    }

    /**
     * @see EditableInterface::setDataFromResource
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
     * @see EditableInterface::setDataFromEditmode
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
            $target = null;

            if ($data['linktype'] == 'internal' && $data['internalType']) {
                $target = Model\Element\Service::getElementByPath($data['internalType'], $path);
                if ($target) {
                    $data['internal'] = true;
                    $data['internalId'] = $target->getId();
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
        $isInternal = $this->data['internal'] ?? false;

        if (is_array($this->data) && $isInternal) {
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
     * @deprecated
     *
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param Model\Document\PageSnippet $document
     * @param array $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $this->sanitizeWebserviceData($wsElement->value);

        if (empty($data->data) or $data->data instanceof \stdClass) {
            $this->data = $data->data instanceof \stdClass ? get_object_vars($data->data) : null;
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

                    if ($id) {
                        $this->data['internalId'] = $id;
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
     * @deprecated
     *
     * @param Model\Document\PageSnippet|null $document
     * @param array $params
     *
     * @return \stdClass
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
                        $document = $this->getDocument();
                    }
                } elseif ($this->data['internalType'] == 'asset') {
                    $referencedAsset = Asset::getById($this->data['internalId']);
                    if (!$referencedAsset instanceof Asset) {
                        //detected broken link
                        $document = $this->getDocument();
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
        if (isset($this->data['internal']) && $this->data['internal']) {
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

class_alias(Link::class, 'Pimcore\Model\Document\Tag\Link');
