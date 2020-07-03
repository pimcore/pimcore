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

final class OrderAgentEvents
{
    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderAgentEvent")
     *
     * @var string
     */
    const PRE_INIT_PAYMENT = 'pimcore.ecommerce.orderagent.preInitPayment';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderAgentEvent")
     *
     * @var string
     */
    const POST_INIT_PAYMENT = 'pimcore.ecommerce.orderagent.postInitPayment';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderAgentEvent")
     *
     * @var string
     */
    const PRE_START_PAYMENT = 'pimcore.ecommerce.orderagent.preStartPayment';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderAgentEvent")
     *
     * @var string
     */
    const POST_START_PAYMENT = 'pimcore.ecommerce.orderagent.postStartPayment';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderAgentEvent")
     *
     * @var string
     */
    const PRE_CANCEL_PAYMENT = 'pimcore.ecommerce.orderagent.preCancelPayment';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderAgentEvent")
     *
     * @var string
     */
    const POST_CANCEL_PAYMENT = 'pimcore.ecommerce.orderagent.postCancelPayment';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderAgentEvent")
     *
     * @var string
     */
    const FINGERPRINT_GENERATED = 'pimcore.ecommerce.orderagent.fingerPrintGenerated';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderAgentEvent")
     *
     * @var string
     */
    const PRE_UPDATE_PAYMENT = 'pimcore.ecommerce.orderagent.preUpdatePayment';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\OrderAgentEvent")
     *
     * @var string
     */
    const POST_UPDATE_PAYMENT = 'pimcore.ecommerce.orderagent.postUpdatePayment';
}
