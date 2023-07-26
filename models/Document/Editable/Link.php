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

namespace Pimcore\Model\Document\Editable;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Link extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface
{
    /**
     * Contains the data for the link
     *
     * @internal
     *
     * @var array|null
     */
    protected ?array $data = null;

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'link';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): mixed
    {
        // update path if internal link
        $this->updatePathFromInternal(true);

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataEditmode(): ?array
    {
        // update path if internal link
        $this->updatePathFromInternal(true, true);

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditmodeElementClasses(array $options = []): array
    {
        // we don't want the class attribute being applied to the editable container element (<div>, only to the <a> tag inside
        // the default behavior of the parent method is to include the "class" attribute
        $classes = [
            'pimcore_editable',
            'pimcore_editable_' . $this->getType(),
        ];

        return $classes;
    }

    /**
     * {@inheritdoc}
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
            $disabledText = false;

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

            if (array_key_exists('disabledFields', $this->config) && is_array($this->config['disabledFields'])) {
                $disabledText = in_array('text', $this->config['disabledFields'], true);
                unset($this->config['disabledFields']);
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
                        $attribs[] = $key.'="'. htmlspecialchars($this->data[$key]) .' '. htmlspecialchars($this->config[$key]) .'"';
                    } elseif (!empty($value)) {
                        $attribs[] = $key.'="'.htmlspecialchars($value).'"';
                    }
                }
            }

            $attribs = array_unique($attribs);

            return '<a href="'.$url.'" '.implode(' ', $attribs).'>' . $prefix . ($noText ? '' : htmlspecialchars($disabledText ? $url : $this->data['text'])) . $suffix . '</a>';
        }

        return '';
    }

    public function checkValidity(): bool
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
                    $this->data = null;
                }
            } elseif ($this->data['internalType'] == 'asset') {
                $asset = Asset::getById($this->data['internalId']);
                if (!$asset) {
                    $sane = false;
                    Logger::notice(
                        'Detected insane relation, removing reference to non existent asset with id ['.$this->getDocumentId(
                        ).']'
                    );
                    $this->data = null;
                }
            } elseif ($this->data['internalType'] == 'object') {
                $object = Model\DataObject\Concrete::getById($this->data['internalId']);
                if (!$object) {
                    $sane = false;
                    Logger::notice(
                        'Detected insane relation, removing reference to non existent object with id ['.$this->getDocumentId(
                        ).']'
                    );
                    $this->data = null;
                }
            }
        }

        return $sane;
    }

    public function getHref(): string
    {
        $this->updatePathFromInternal();

        $url = $this->data['path'] ?? '';

        if (strlen($this->data['parameters'] ?? '') > 0) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . htmlspecialchars(str_replace('?', '', $this->getParameters()));
        }

        if (strlen($this->data['anchor'] ?? '') > 0) {
            $anchor = str_replace('"', urlencode('"'), htmlspecialchars($this->getAnchor()));
            $url .= '#' . str_replace('#', '', $anchor);
        }

        return $url;
    }

    private function updatePathFromInternal(bool $realPath = false, bool $editmode = false): void
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

        // deletes unnecessary attribute, which was set by mistake in earlier versions, see also
        // https://github.com/pimcore/pimcore/issues/7394
        if (isset($this->data['type'])) {
            unset($this->data['type']);
        }
    }

    public function getText(): string
    {
        return $this->data['text'] ?? '';
    }

    public function setText(string $text): void
    {
        $this->data['text'] = $text;
    }

    public function getTarget(): string
    {
        return $this->data['target'] ?? '';
    }

    public function getParameters(): string
    {
        return $this->data['parameters'] ?? '';
    }

    public function getAnchor(): string
    {
        return $this->data['anchor'] ?? '';
    }

    public function getTitle(): string
    {
        return $this->data['title'] ?? '';
    }

    public function getRel(): string
    {
        return $this->data['rel'] ?? '';
    }

    public function getTabindex(): string
    {
        return $this->data['tabindex'] ?? '';
    }

    public function getAccesskey(): string
    {
        return $this->data['accesskey'] ?? '';
    }

    public function getClass(): mixed
    {
        return $this->data['class'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource(mixed $data): static
    {
        $this->data = \Pimcore\Tool\Serialize::unserialize($data);
        if (!is_array($this->data)) {
            $this->data = [];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode(mixed $data): static
    {
        if (!is_array($data)) {
            $data = [];
        }

        $path = $data['path'] ?? null;

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

    public function isEmpty(): bool
    {
        return strlen($this->getHref()) < 1;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDependencies(): array
    {
        $dependencies = [];
        $isInternal = $this->data['internal'] ?? false;

        if (is_array($this->data) && $isInternal) {
            if ((int)$this->data['internalId'] > 0) {
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
                        $key = 'asset_' . $asset->getId();

                        $dependencies[$key] = [
                            'id' => $asset->getId(),
                            'type' => 'asset',
                        ];
                    }
                } elseif ($this->data['internalType'] == 'object') {
                    if ($object = DataObject\Concrete::getById($this->data['internalId'])) {
                        $key = 'object_' . $object->getId();

                        $dependencies[$key] = [
                            'id' => $object->getId(),
                            'type' => 'object',
                        ];
                    }
                }
            }
        }

        return $dependencies;
    }

    /**
     * { @inheritdoc }
     */
    public function rewriteIds(array $idMapping): void
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
