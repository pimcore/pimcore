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

namespace Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;

use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Listing\CallableFilterListingInterface;
use Pimcore\Model\Listing\CallableOrderListingInterface;
use Pimcore\Model\Listing\Traits\FilterListingTrait;
use Pimcore\Model\Listing\Traits\OrderListingTrait;

/**
 * @method Listing\Dao getDao()
 * @method int getTotalCount()
 */
class Listing extends AbstractModel implements CallableFilterListingInterface, CallableOrderListingInterface
{
    use FilterListingTrait;
    use OrderListingTrait;

    /**
     * @var Staticroute[]|null
     */
    protected ?array $routes = null;

    /**
     * @return Staticroute[]
     */
    public function getRoutes(): array
    {
        if ($this->routes === null) {
            $this->getDao()->loadList();
        }

        return $this->routes;
    }

    /**
     * @param Staticroute[]|null $routes
     *
     * @return $this
     */
    public function setRoutes(?array $routes): static
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @return Staticroute[]
     */
    public function load(): array
    {
        return $this->getRoutes();
    }
}
