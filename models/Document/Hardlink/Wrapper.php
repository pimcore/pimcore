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

namespace Pimcore\Model\Document\Hardlink;

use Pimcore\Model\Document;

/**
 * @method Document\Dao getDao()
 */
trait Wrapper
{
    /**
     * @var Document\Hardlink
     */
    protected $hardLinkSource;

    /**
     * @var Document
     */
    protected $sourceDocument;

    /**
     * OVERWRITTEN METHODS
     *
     * @throws \Exception
     */
    public function save()
    {
        throw $this->getHardlinkError();
    }

    /**
     * @param array $params
     *
     * @throws \Exception
     */
    protected function update($params = [])
    {
        throw $this->getHardlinkError();
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        throw $this->getHardlinkError();
    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        if ($this->properties == null) {
            $hardLink = $this->getHardLinkSource();

            if ($hardLink->getPropertiesFromSource()) {
                $sourceProperties = $this->getDao()->getProperties();
            } else {
                $sourceProperties = [];
            }

            if ($this->getSourceDocument()) {
                // if we have a source document, it means that this document is not directly linked, it's a
                // child of a hardlink that uses "childFromSource", so in this case we use the source properties
                // this is especially important for the navigation, otherwise all children will have the same
                // navigation_name as the source hardlink, which doesn't make sense at all
                $sourceProperties = $this->getSourceDocument()->getProperties();
            }

            $hardLinkProperties = [];
            $hardLinkSourceProperties = $hardLink->getProperties();
            foreach ($hardLinkSourceProperties as $key => $prop) {
                $prop = clone $prop;

                // if the property doesn't exist in the source-properties just add it
                if (!array_key_exists($key, $sourceProperties)) {
                    $hardLinkProperties[$key] = $prop;
                } else {
                    // if the property does exist in the source properties but it is inherited, then overwrite it with the hardlink property
                    // or if the property is set directly on the hardlink itself
                    if ($sourceProperties[$key]->isInherited() || !$prop->isInherited()) {
                        $hardLinkProperties[$key] = $prop;
                    }
                }

                $prop->setInherited(true);
            }

            $properties = array_merge($sourceProperties, $hardLinkProperties);
            $this->setProperties($properties);
        }

        return $this->properties;
    }

    public function getProperty($name, $asContainer = false)
    {
        $result = parent::getProperty($name, $asContainer);
        if ($result instanceof Document) {
            $hardLink = $this->getHardLinkSource();
            if (strpos($result->getRealFullPath(), $hardLink->getSourceDocument()->getRealFullPath() . '/') === 0
                || $hardLink->getSourceDocument()->getRealFullPath() === $result->getRealFullPath()
            ) {
                $c = Service::wrap($result);
                if ($c instanceof Document\Hardlink\Wrapper\WrapperInterface) {
                    $c->setHardLinkSource($hardLink);
                    $c->setPath(preg_replace('@^' . preg_quote($hardLink->getSourceDocument()->getRealPath(), '@') . '@',
                        $hardLink->getRealPath(), $c->getRealPath()));

                    return $c;
                }
            }
        }

        return $result;
    }

    /**
     * @param bool $includingUnpublished
     *
     * @return Document[]
     */
    public function getChildren($includingUnpublished = false)
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());
        if (!isset($this->children[$cacheKey])) {
            $hardLink = $this->getHardLinkSource();
            $children = [];
            if ($hardLink->getChildrenFromSource() && $hardLink->getSourceDocument() && !\Pimcore::inAdmin()) {
                foreach (parent::getChildren($includingUnpublished) as $c) {
                    $c = Service::wrap($c);
                    if ($c instanceof Document\Hardlink\Wrapper\WrapperInterface) {
                        $c->setHardLinkSource($hardLink);
                        $c->setPath(preg_replace('@^' . preg_quote($hardLink->getSourceDocument()->getRealFullpath(), '@') . '@', $hardLink->getRealFullpath(), $c->getRealPath()));

                        $children[] = $c;
                    }
                }
            }

            $this->setChildren($children, $includingUnpublished);
        }

        return $this->children[$cacheKey];
    }

    /**
     * @param bool $unpublished
     *
     * @return bool
     */
    public function hasChildren($unpublished = false)
    {
        $hardLink = $this->getHardLinkSource();

        if ($hardLink->getChildrenFromSource() && $hardLink->getSourceDocument() && !\Pimcore::inAdmin()) {
            return parent::hasChildren($unpublished);
        }

        return false;
    }

    /**
     * @return \Exception
     */
    protected function getHardlinkError(): \Exception
    {
        return new \Exception('Method not supported by hard linked documents');
    }

    /**
     * @param Document\Hardlink $hardLinkSource
     *
     * @return $this
     */
    public function setHardLinkSource($hardLinkSource)
    {
        $this->hardLinkSource = $hardLinkSource;

        return $this;
    }

    /**
     * @return Document\Hardlink
     */
    public function getHardLinkSource()
    {
        return $this->hardLinkSource;
    }

    /**
     * @return Document
     */
    public function getSourceDocument()
    {
        return $this->sourceDocument;
    }

    /**
     * @param Document $sourceDocument
     */
    public function setSourceDocument($sourceDocument)
    {
        $this->sourceDocument = $sourceDocument;
    }
}
