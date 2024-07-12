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

namespace Pimcore\HttpKernel\BundleCollection;

use InvalidArgumentException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class BundleCollection
{
    /**
     * @var ItemInterface[]
     */
    private array $items = [];

    /**
     * Adds a collection item
     *
     * @return $this
     */
    public function add(ItemInterface $item): static
    {
        $identifier = $item->getBundleIdentifier();

        // a bundle can only be registered once
        if ($this->hasItem($identifier)) {
            $bundle = $this->getItem($identifier);
            // if the new item has a higher priority, we replace the existing item
            if ($bundle->getPriority() >= $item->getPriority()) {
                return $this;
            }
        }

        $this->items[$identifier] = $item;

        // handle DependentBundleInterface by adding a bundle's dependencies to the collection - dependencies
        // are added AFTER the item was added to the collection to avoid circular reference loops, but the
        // sort order can be influenced by specifying a priority on the item
        $item->registerDependencies($this);

        return $this;
    }

    /**
     * Returns a collection item by identifier
     */
    public function getItem(string $identifier): ItemInterface
    {
        if (!$this->hasItem($identifier)) {
            throw new InvalidArgumentException(sprintf('Bundle "%s" is not registered', $identifier));
        }

        return $this->items[$identifier];
    }

    /**
     * Checks if a specific item is registered
     */
    public function hasItem(string $identifier): bool
    {
        return isset($this->items[$identifier]);
    }

    /**
     * Returns all collection items ordered by priority and optionally filtered by matching environment
     *
     * @return ItemInterface[]
     */
    public function getItems(?string $environment = null): array
    {
        $items = array_values($this->items);

        if (null !== $environment) {
            $items = array_filter($items, static fn (ItemInterface $item) => $item->matchesEnvironment($environment));
        }

        usort($items, static fn (ItemInterface $a, ItemInterface $b) => $b->getPriority() <=> $a->getPriority());

        return $items;
    }

    /**
     * Returns all bundle identifiers
     *
     * @return string[]
     */
    public function getIdentifiers(string $environment = null): array
    {
        return array_map(
            static fn (ItemInterface $item): string => $item->getBundleIdentifier(),
            $this->getItems($environment),
        );
    }

    /**
     * Get bundles matching environment ordered by priority
     *
     * @return BundleInterface[]
     */
    public function getBundles(string $environment): array
    {
        return array_map(
            static fn (ItemInterface $item): BundleInterface => $item->getBundle(),
            $this->getItems($environment),
        );
    }

    /**
     * Adds a bundle
     *
     * @throws InvalidArgumentException
     *
     * @return $this
     */
    public function addBundle(BundleInterface|string $bundle, int $priority = 0, array $environments = []): static
    {
        $item = $bundle instanceof BundleInterface
            ? new Item($bundle, $priority, $environments)
            : new LazyLoadedItem($bundle, $priority, $environments);

        return $this->add($item);
    }

    /**
     * Adds a collection of bundles with the same priority and environments
     *
     * @param BundleInterface[]|string[] $bundles
     *
     * @return $this
     */
    public function addBundles(array $bundles, int $priority = 0, array $environments = []): static
    {
        foreach ($bundles as $bundle) {
            $this->addBundle($bundle, $priority, $environments);
        }

        return $this;
    }
}
