<?php

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

namespace Pimcore\Model\Staticroute;

use Pimcore\Model;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Listing\FilterListingInterface;
use Pimcore\Model\Listing\OrderListingInterface;
use Pimcore\Model\Listing\Traits\FilterListingTrait;
use Pimcore\Model\Listing\Traits\OrderListingTrait;

/**
 * @method \Pimcore\Model\Staticroute\Listing\Dao getDao()
 * @method int getTotalCount()
 */
class Listing extends AbstractModel implements FilterListingInterface, OrderListingInterface
{
    use FilterListingTrait;
    use OrderListingTrait;

    /**
     * @var \Pimcore\Model\Staticroute[]|null
     */
    protected $routes = null;

    /**
     * @return \Pimcore\Model\Staticroute[]
     */
    public function getRoutes()
    {
        if ($this->routes === null) {
            $this->getDao()->loadList();
        }

        return $this->routes;
    }

    /**
     * @param \Pimcore\Model\Staticroute[]|null $routes
     *
     * @return $this
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @return Model\Staticroute[]
     */
    public function load()
    {
        return $this->getRoutes();
    }
}
