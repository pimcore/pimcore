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

namespace Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

use Pimcore\Model;
use Pimcore\Model\Listing\CallableFilterListingInterface;
use Pimcore\Model\Listing\CallableOrderListingInterface;
use Pimcore\Model\Listing\Traits\FilterListingTrait;

/**
 * @internal
 *
 * @method \Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Listing\Dao getDao()
 * @method Model\DataObject\ClassDefinition\CustomLayout[] load()
 * @method Model\DataObject\ClassDefinition\CustomLayout|false current()
 */
class Listing extends Model\Listing\AbstractListing implements CallableFilterListingInterface, CallableOrderListingInterface
{
    use FilterListingTrait;

    protected ?array $layoutDefinitions = null;

    /**
     * @return array|string|callable|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param array|string|callable|null $order
     */
    public function setOrder($order)
    {
        if (is_array($order) || is_string($order)) {
            trigger_deprecation(
                'pimcore/pimcore',
                '10.5',
                sprintf('Passing array or string to %s is deprecated,
                please pass callable function instead.', __METHOD__)
            );

            return parent::setOrder($order);
        }

        $this->order = $order;

        return $this;
    }

    /**
     * @param Model\DataObject\ClassDefinition\CustomLayout[]|null $layoutDefinitions
     *
     * @return $this
     */
    public function setLayoutDefinitions($layoutDefinitions)
    {
        $this->layoutDefinitions = $layoutDefinitions;

        return $this;
    }

    /**
     * @return Model\DataObject\ClassDefinition\CustomLayout[]
     */
    public function getLayoutDefinitions()
    {
        if ($this->layoutDefinitions === null) {
            $this->layoutDefinitions = $this->load();
        }

        return $this->layoutDefinitions;
    }
}
