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

namespace Pimcore\Model\Document\Hardlink;

use Exception;
use Pimcore;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Listing;

/**
 * @internal
 *
 * @method Document\Dao getDao()
 */
trait Wrapper
{
    protected Document\Hardlink $hardLinkSource;

    protected ?Document $sourceDocument = null;

    /**
     * OVERWRITTEN METHODS
     *
     * @throws Exception
     */
    public function save(array $parameters = []): static
    {
        throw $this->getHardlinkError();
    }

    /**
     *
     * @throws Exception
     */
    protected function update(array $params = []): void
    {
        throw $this->getHardlinkError();
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        throw $this->getHardlinkError();
    }

    public function getProperties(): array
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

    public function getProperty(string $name, bool $asContainer = false): mixed
    {
        $result = parent::getProperty($name, $asContainer);
        if ($result instanceof Document) {
            $hardLink = $this->getHardLinkSource();
            if (str_starts_with($result->getRealFullPath(), $hardLink->getSourceDocument()->getRealFullPath() . '/')
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

    public function getChildren(bool $includingUnpublished = false): Listing
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());
        if (!isset($this->children[$cacheKey])) {
            $hardLink = $this->getHardLinkSource();
            $children = [];
            if ($hardLink->getChildrenFromSource() && $hardLink->getSourceDocument() && !Pimcore::inAdmin()) {
                foreach (parent::getChildren($includingUnpublished) as $c) {
                    $c = Service::wrap($c);
                    if ($c instanceof Document\Hardlink\Wrapper\WrapperInterface) {
                        $c->setHardLinkSource($hardLink);
                        $c->setPath(preg_replace('@^' . preg_quote($hardLink->getSourceDocument()->getRealFullpath(), '@') . '@', $hardLink->getRealFullpath(), $c->getRealPath()));

                        $children[] = $c;
                    }
                }
            }

            $listing = new Listing;
            $listing->setData($children);
            $this->setChildren($listing, $includingUnpublished);
        }

        return $this->children[$cacheKey];
    }

    public function hasChildren(?bool $includingUnpublished = null): bool
    {
        $hardLink = $this->getHardLinkSource();

        if ($hardLink->getChildrenFromSource() && $hardLink->getSourceDocument() && !Pimcore::inAdmin()) {
            return parent::hasChildren($includingUnpublished);
        }

        return false;
    }

    protected function getHardlinkError(): Exception
    {
        return new Exception('Method not supported by hard linked documents');
    }

    public function setHardLinkSource(Document\Hardlink $hardLinkSource): static
    {
        $this->hardLinkSource = $hardLinkSource;

        return $this;
    }

    public function getHardLinkSource(): Document\Hardlink
    {
        return $this->hardLinkSource;
    }

    public function getSourceDocument(): ?Document
    {
        return $this->sourceDocument;
    }

    public function setSourceDocument(Document $sourceDocument): static
    {
        $this->sourceDocument = $sourceDocument;

        return $this;
    }
}
