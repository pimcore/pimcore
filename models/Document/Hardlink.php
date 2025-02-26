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

namespace Pimcore\Model\Document;

use Pimcore;
use Pimcore\Model;
use Pimcore\Model\Document;

/**
 * @method \Pimcore\Model\Document\Hardlink\Dao getDao()
 */
class Hardlink extends Document
{
    use Model\Element\Traits\ScheduledTasksTrait;

    protected string $type = 'hardlink';

    /**
     * @internal
     *
     */
    protected ?int $sourceId = null;

    /**
     * @internal
     *
     */
    protected bool $propertiesFromSource = false;

    /**
     * @internal
     *
     */
    protected bool $childrenFromSource = false;

    public function getSourceDocument(): ?Document
    {
        if ($this->getSourceId()) {
            return Document::getById($this->getSourceId());
        }

        return null;
    }

    public function resolveDependencies(): array
    {
        $dependencies = parent::resolveDependencies();
        $sourceDocument = $this->getSourceDocument();

        if ($sourceDocument instanceof Document) {
            $key = 'document_' . $sourceDocument->getId();

            $dependencies[$key] = [
                'id' => $sourceDocument->getId(),
                'type' => 'document',
            ];
        }

        return $dependencies;
    }

    public function getCacheTags(array $tags = []): array
    {
        $tags = parent::getCacheTags($tags);

        if ($this->getSourceDocument()) {
            if ($this->getSourceDocument()->getId() != $this->getId() && !array_key_exists($this->getSourceDocument()->getCacheTag(), $tags)) {
                $tags = $this->getSourceDocument()->getCacheTags($tags);
            }
        }

        return $tags;
    }

    public function setChildrenFromSource(bool $childrenFromSource): static
    {
        $this->childrenFromSource = $childrenFromSource;

        return $this;
    }

    public function getChildrenFromSource(): bool
    {
        return $this->childrenFromSource;
    }

    public function setSourceId(?int $sourceId): static
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function setPropertiesFromSource(bool $propertiesFromSource): static
    {
        $this->propertiesFromSource = $propertiesFromSource;

        return $this;
    }

    public function getPropertiesFromSource(): bool
    {
        return $this->propertiesFromSource;
    }

    public function getProperties(): array
    {
        if ($this->properties === null) {
            $properties = parent::getProperties();

            if ($this->getPropertiesFromSource() && $this->getSourceDocument()) {
                $sourceProperties = $this->getSourceDocument()->getProperties();
                foreach ($sourceProperties as &$prop) {
                    $prop = clone $prop; // because of cache
                    $prop->setInherited(true);
                }
                $properties = array_merge($sourceProperties, $properties);
            } elseif ($this->getSourceDocument()) {
                $sourceProperties = $this->getSourceDocument()->getDao()->getProperties(false, true);
                foreach ($sourceProperties as &$prop) {
                    /**
                     * @var Model\Property $prop
                     */
                    $prop = clone $prop; // because of cache
                    $prop->setInherited(true);
                }
                $properties = array_merge($sourceProperties, $properties);
            }

            $this->setProperties($properties);
        }

        return $this->properties;
    }

    public function getChildren(bool $includingUnpublished = false): Listing
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());
        if (!isset($this->children[$cacheKey])) {
            $children = parent::getChildren($includingUnpublished);

            $wrappedSourceChildren = [];
            if ($this->getChildrenFromSource() && $this->getSourceDocument() && !Pimcore::inAdmin()) {
                $sourceChildren = $this->getSourceDocument()->getChildren($includingUnpublished)->getDocuments();
                foreach ($sourceChildren as $key => $c) {
                    $wrappedChild = Document\Hardlink\Service::wrap($c);
                    $wrappedChild->setHardLinkSource($this);
                    $wrappedChild->setPath(preg_replace('@^' . preg_quote($this->getSourceDocument()->getRealFullPath(), '@') . '@', $this->getRealFullPath(), $c->getRealPath()));
                    $wrappedSourceChildren[$key] = $wrappedChild;
                }
            }

            $children->setData(array_merge($wrappedSourceChildren, $children->load()));

            $this->setChildren($children, $includingUnpublished);
        }

        return $this->children[$cacheKey];
    }

    public function hasChildren(?bool $includingUnpublished = null): bool
    {
        return count($this->getChildren((bool)$includingUnpublished)) > 0;
    }

    protected function update(array $params = []): void
    {
        parent::update($params);
        $this->saveScheduledTasks();
    }
}
