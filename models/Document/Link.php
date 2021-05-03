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

namespace Pimcore\Model\Document;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;

/**
 * @method \Pimcore\Model\Document\Link\Dao getDao()
 */
class Link extends Model\Document
{
    use Document\Traits\ScheduledTasksTrait;

    /**
     * Contains the ID of the internal ID
     *
     * @internal
     *
     * @var int|null
     */
    protected $internal;

    /**
     * Contains the type of the internal ID
     *
     * @internal
     *
     * @var string
     */
    protected $internalType;

    /**
     * Contains object of linked Document|Asset|DataObject
     *
     * @internal
     *
     * @var Model\Element\ElementInterface|null
     */
    protected $object;

    /**
     * Contains the direct link as plain text
     *
     * @internal
     *
     * @var string
     */
    protected string $direct = '';

    /**
     * Type of the link (internal/direct)
     *
     * @internal
     *
     * @var string
     */
    protected string $linktype = 'internal';

    /**
     * {@inheritdoc}
     */
    protected string $type = 'link';

    /**
     * path of the link
     *
     * @internal
     *
     * @var string
     */
    protected string $href = '';

    /**
     * {@inheritdoc}
     */
    protected function resolveDependencies(): array
    {
        $dependencies = parent::resolveDependencies();

        if ($this->getLinktype() == 'internal') {
            $element = $this->getObject();

            if ($element instanceof Document || $element instanceof Asset) {
                $key = $this->getInternalType() . '_' . $element->getId();

                $dependencies[$key] = [
                    'id' => $element->getId(),
                    'type' => $this->getInternalType(),
                ];
            }
        }

        return $dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags(array $tags = []): array
    {
        $tags = parent::getCacheTags($tags);

        if ($this->getLinktype() === 'internal') {
            if ($this->getObject() instanceof Document || $this->getObject() instanceof Asset) {
                if ($this->getObject()->getId() != $this->getId() and !array_key_exists($this->getObject()->getCacheTag(), $tags)) {
                    $tags = $this->getObject()->getCacheTags($tags);
                }
            }
        }

        return $tags;
    }

    /**
     * Returns the plain text path of the link
     *
     * @return string
     */
    public function getHref()
    {
        $path = '';
        if ($this->getLinktype() == 'internal') {
            if ($this->getObject() instanceof Document || $this->getObject() instanceof Asset) {
                $path = $this->getObject()->getFullPath();
            } else {
                if ($this->getObject() instanceof Model\DataObject\Concrete) {
                    if ($linkGenerator = $this->getObject()->getClass()->getLinkGenerator()) {
                        $path = $linkGenerator->generate(
                            $this->getObject(),
                            [
                                'document' => $this,
                                'context' => $this,
                            ]
                        );
                    }
                }
            }
        } else {
            $path = $this->getDirect();
        }

        $this->href = $path;

        return $path;
    }

    /**
     * Returns the plain text path of the link needed for the editmode
     *
     * @return string
     */
    public function getRawHref()
    {
        $rawHref = '';
        if ($this->getLinktype() == 'internal') {
            if ($this->getObject() instanceof Document || $this->getObject() instanceof Asset ||
                ($this->getObject() instanceof Model\DataObject\Concrete)
            ) {
                $rawHref = $this->getObject()->getFullPath();
            }
        } else {
            $rawHref = $this->getDirect();
        }

        return $rawHref;
    }

    /**
     * Returns the path of the link including the anchor and parameters
     *
     * @return string
     */
    public function getLink()
    {
        $path = $this->getHref();

        $parameters = $this->getProperty('navigation_parameters');
        if (strlen($parameters) > 0) {
            $path .= '?' . str_replace('?', '', $parameters);
        }

        $anchor = $this->getProperty('navigation_anchor');
        if (strlen($anchor) > 0) {
            $path .= '#' . str_replace('#', '', $anchor);
        }

        return $path;
    }

    /**
     * Returns the id of the internal document|asset which is linked
     *
     * @return int
     */
    public function getInternal()
    {
        return $this->internal;
    }

    /**
     * Returns the direct link (eg. http://www.pimcore.org/test)
     *
     * @return string
     */
    public function getDirect()
    {
        return $this->direct;
    }

    /**
     * Returns the type of the link (internal/direct)
     *
     * @return string
     */
    public function getLinktype()
    {
        return $this->linktype;
    }

    /**
     * @param int $internal
     *
     * @return $this
     */
    public function setInternal($internal)
    {
        if (!empty($internal)) {
            $this->internal = (int) $internal;
            $this->setObjectFromId();
        } else {
            $this->internal = null;
        }

        return $this;
    }

    /**
     * @param string $direct
     *
     * @return $this
     */
    public function setDirect($direct)
    {
        $this->direct = $direct;

        return $this;
    }

    /**
     * @param string $linktype
     *
     * @return $this
     */
    public function setLinktype($linktype)
    {
        $this->linktype = $linktype;

        return $this;
    }

    /**
     * @return string
     */
    public function getInternalType()
    {
        return $this->internalType;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setInternalType($type)
    {
        $this->internalType = $type;

        return $this;
    }

    /**
     * @return Model\Element\ElementInterface|null
     */
    public function getElement()
    {
        if ($this->object instanceof Model\Element\ElementInterface) {
            return $this->object;
        }
        if ($this->setObjectFromId()) {
            return $this->object;
        }

        return null;
    }

    /**
     * @param Model\Element\ElementInterface $element
     *
     * @return $this
     */
    public function setElement($element)
    {
        $this->object = $element;

        return $this;
    }

    /**
     * @deprecated use getElement() instead, will be removed in Pimcore 11
     *
     * @return Model\Element\ElementInterface|null
     */
    public function getObject()
    {
        return $this->getElement();
    }

    /**
     * @deprecated use getElement() instead, will be removed in Pimcore 11
     *
     * @param Model\Element\ElementInterface $object
     *
     * @return $this
     */
    public function setObject($object)
    {
        return $this->setElement($object);
    }

    /**
     * @return Model\Element\ElementInterface|null
     */
    private function setObjectFromId()
    {
        try {
            if ($this->internal) {
                if ($this->internalType == 'document') {
                    $this->object = Document::getById($this->internal);
                } elseif ($this->internalType == 'asset') {
                    $this->object = Asset::getById($this->internal);
                } elseif ($this->internalType == 'object') {
                    $this->object = Model\DataObject\Concrete::getById($this->internal);
                }
            }
        } catch (\Exception $e) {
            Logger::warn($e);
            $this->internalType = '';
            $this->internal = null;
            $this->object = null;
        }

        return $this->object;
    }

    /**
     * returns the ready-use html for this link
     *
     * @return string
     */
    public function getHtml()
    {
        $attributes = [
            'class',
            'target',
            'title',
            'accesskey',
            'tabindex',
            'rel' => 'relation',
        ];

        $link = $this->getLink();
        $link .= $this->getProperty('navigation_parameters') . $this->getProperty('navigation_anchor');

        $attribs = [];
        foreach ($attributes as $key => $name) {
            $key = is_numeric($key) ? $name : $key;
            $value = $this->getProperty('navigation_' . $name);
            if ($value) {
                $attribs[] = $key . '="' . $value . '"';
            }
        }

        return '<a href="' . $link . '" ' . implode(' ', $attribs) . '>' . htmlspecialchars($this->getProperty('navigation_name')) . '</a>';
    }

    /**
     * {@inheritdoc}
     */
    protected function update($params = [])
    {
        parent::update($params);

        $this->saveScheduledTasks();
    }

    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();

        $blockedVars = ['object'];

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }
}
