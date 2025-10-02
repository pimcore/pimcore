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

namespace Pimcore\Event\BundleManager;

use Symfony\Contracts\EventDispatcher\Event;

class PathsEvent extends Event
{
    /**
     * @var string[]
     */
    protected array $paths = [];

    /**
     * @param string[] $paths
     */
    public function __construct(array $paths = [])
    {
        $this->setPaths($paths);
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @param string[] $paths
     */
    public function setPaths(array $paths): void
    {
        $this->paths = [];
        $this->addPaths($paths);
    }

    /**
     * @param string[] $paths
     */
    public function addPaths(array $paths): void
    {
        $this->paths = array_merge($this->paths, $paths);
        $this->paths = array_unique($this->paths);
    }
}
