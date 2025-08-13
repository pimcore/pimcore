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
use Psr\Container\ContainerInterface;
use Traversable;

/**
 * @internal
 */
class ServiceCollection implements IteratorAggregate
{
    private ContainerInterface $container;

    private array $ids = [];

    public function __construct(ContainerInterface $container, array $ids)
    {
        $this->container = $container;
        $this->ids = $ids;
    }

    public function getIterator(): Traversable
    {
        foreach ($this->ids as $id) {
            yield $this->container->get($id);
        }
    }
}
