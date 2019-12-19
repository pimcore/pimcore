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

final class CommitOrderProcessorEvents
{
    /**
     * @Event("Pimcore\Event\Model\Ecommerce\CommitOrderProcessorEvent")
     *
     * @var string
     */
    const PRE_COMMIT_ORDER_PAYMENT = 'pimcore.ecommerce.commitorderprocessor.preCommitOrderPayment';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\CommitOrderProcessorEvent")
     *
     * @var string
     */
    const POST_COMMIT_ORDER_PAYMENT = 'pimcore.ecommerce.commitorderprocessor.postCommitOrderPayment';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\CommitOrderProcessorEvent")
     *
     * @var string
     */
    const PRE_COMMIT_ORDER = 'pimcore.ecommerce.commitorderprocessor.preCommitOrder';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\CommitOrderProcessorEvent")
     *
     * @var string
     */
    const POST_COMMIT_ORDER = 'pimcore.ecommerce.commitorderprocessor.postCommitOrder';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\CommitOrderProcessorEvent")
     *
     * @var string
     */
    const SEND_CONFIRMATION_MAILS = 'pimcore.ecommerce.commitorderprocessor.sendConfirmationMails';
}
