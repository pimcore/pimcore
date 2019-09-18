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

namespace Pimcore\Event\Ecommerce;

final class OrderManagerEvents
{
    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderManagerEvent")
     *
     * @var string
     */
    const PRE_GET_OR_CREATE_ORDER_FROM_CART = 'pimcore.ecommerce.ordermanager.preGetOrCreateOrderFromCart';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderManagerEvent")
     *
     * @var string
     */
    const PRE_UPDATE_ORDER = 'pimcore.ecommerce.ordermanager.preUpdateOrder';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderManagerEvent")
     *
     * @var string
     */
    const POST_UPDATE_ORDER = 'pimcore.ecommerce.ordermanager.postUpdateOrder';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderManagerItemEvent")
     *
     * @var string
     */
    const POST_CREATE_ORDER_ITEM = 'pimcore.ecommerce.ordermanager.postCreateOrderItem';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderManagerItemEvent")
     *
     * @var string
     */
    const BUILD_ORDER_ITEM_KEY = 'pimcore.ecommerce.ordermanager.buildOrderItemKey';
}
