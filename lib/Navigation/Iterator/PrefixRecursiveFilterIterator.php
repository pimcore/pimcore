<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Navigation\Iterator;

use Exception;
use Pimcore\Navigation\Container;
use Pimcore\Navigation\Page;
use RecursiveFilterIterator;
use RecursiveIterator;

/**
 * @internal
 */
final class PrefixRecursiveFilterIterator extends RecursiveFilterIterator
{
    private string $property;

    private string $value;

    /**
     * @param RecursiveIterator $iterator navigation container to iterate
     * @param string $property name of property that acts as needle
     * @param string $value value which acts as haystack
     */
    public function __construct(RecursiveIterator $iterator, string $property, string $value)
    {
        parent::__construct($iterator);
        $this->property = $property;
        $this->value = $value;
    }

    public function accept(): bool
    {
        /** @var Page $page */
        $page = $this->current();

        try {
            $property = $page->get($this->property);
        } catch (Exception) {
            return false;
        }

        return is_string($property) && str_starts_with($this->value, $property);
    }

    public function getChildren(): ?RecursiveFilterIterator
    {
        /** @var Container $container */
        $container = $this->getInnerIterator();

        if ($container->getChildren() === null) {
            return null;
        }

        return new self($container->getChildren(), $this->property, $this->value);
    }
}
