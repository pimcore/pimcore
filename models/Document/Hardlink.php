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

use Pimcore\Model;
use Pimcore\Model\Document;

/**
 * @method \Pimcore\Model\Document\Hardlink\Dao getDao()
 */
class Hardlink extends Document
{
    use Model\Element\Traits\ScheduledTasksTrait;

    /**
     * {@inheritdoc}
     */
    protected string $type = 'hardlink';

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $sourceId = null;

    /**
     * @internal
     *
     * @var bool
     */
    protected bool $propertiesFromSource = false;

    /**
     * @internal
     *
     * @var bool
     */
    protected bool $childrenFromSource = false;

    public function getSourceDocument(): ?Document
    {
        if ($this->getSourceId()) {
            return Document::getById($this->getSourceId());
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveDependencies(): array
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getChildren(bool $includingUnpublished = false): Listing
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());
        if (!isset($this->children[$cacheKey])) {
            $children = parent::getChildren($includingUnpublished);

            $sourceChildren = [];
            if ($this->getChildrenFromSource() && $this->getSourceDocument() && !\Pimcore::inAdmin()) {
                $sourceChildren = $this->getSourceDocument()->getChildren($includingUnpublished);
                foreach ($sourceChildren as &$c) {
                    $c = Document\Hardlink\Service::wrap($c);
                    $c->setHardLinkSource($this);
                    $c->setPath(preg_replace('@^' . preg_quote($this->getSourceDocument()->getRealFullPath(), '@') . '@', $this->getRealFullPath(), $c->getRealPath()));
                }
            }

            $children->setData(array_merge($sourceChildren, $children->load()));

            $this->setChildren($children, $includingUnpublished);
        }

        return $this->children[$cacheKey];
    }

    public function hasChildren(?bool $includingUnpublished = null): bool
    {
        return count($this->getChildren((bool)$includingUnpublished)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function update(array $params = []): void
    {
        parent::update($params);
        $this->saveScheduledTasks();
    }
}
