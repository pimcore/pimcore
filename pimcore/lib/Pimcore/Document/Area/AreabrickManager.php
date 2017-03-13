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

namespace Pimcore\Document\Area;

use Pimcore\Document\Area\Exception\ConfigurationException;

class AreabrickManager implements AreabrickManagerInterface
{
    /**
     * @var AreabrickInterface[]
     */
    protected $bricks = [];

    /**
     * @param AreabrickInterface $brick
     */
    public function register(AreabrickInterface $brick)
    {
        if (array_key_exists($brick->getId(), $this->bricks)) {
            throw new ConfigurationException(sprintf('Areabrick %s is already registered', $brick->getId()));
        }

        $this->bricks[$brick->getId()] = $brick;
    }

    /**
     * @param string $id
     *
     * @return AreabrickInterface
     */
    public function getBrick($id)
    {
        if (!isset($this->bricks[$id])) {
            throw new ConfigurationException(sprintf('Areabrick %s is not registered', $id));
        }

        return $this->bricks[$id];
    }

    /**
     * @return AreabrickInterface[]
     */
    public function getBricks()
    {
        return $this->bricks;
    }
}
