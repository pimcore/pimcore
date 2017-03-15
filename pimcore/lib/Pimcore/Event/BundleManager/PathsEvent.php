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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */ 

namespace Pimcore\Event\BundleManager;

use Symfony\Component\EventDispatcher\Event;

class PathsEvent extends Event
{
    protected $paths = [];

    /**
     * @param array $paths
     */
    public function __construct(array $paths = [])
    {
        $this->setPaths($paths);
    }

    /**
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * @param array $paths
     */
    public function setPaths(array $paths)
    {
        $this->paths = [];
        $this->addPaths($paths);
    }

    /**
     * @param array $paths
     */
    public function addPaths(array $paths)
    {
        $this->paths = array_merge($this->paths, $paths);
        $this->paths = array_unique($this->paths);
    }
}
