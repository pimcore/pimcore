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
