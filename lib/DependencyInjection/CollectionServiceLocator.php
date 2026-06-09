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

namespace Pimcore\DependencyInjection;

use IteratorAggregate;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Traversable;

/**
 * Service locator exposing all of its services as collection
 *
 * @internal
 */
class CollectionServiceLocator extends ServiceLocator implements IteratorAggregate
{
    private array $ids;

    public function __construct($factories)
    {
        $this->ids = array_keys($factories);

        parent::__construct($factories);
    }

    public function all(): array
    {
        return array_map(function ($id) {
            return $this->get($id);
        }, $this->ids);
    }

    public function getIterator(): Traversable
    {
        foreach ($this->ids as $id) {
            yield $id => $this->get($id);
        }
    }
}
