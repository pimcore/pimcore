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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Event;

final class CommitOrderProcessorEvents
{
    /**
     * @Event("Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\CommitOrderProcessorEvent")
     *
     * @var string
     */
    const PRE_COMMIT_ORDER_PAYMENT = 'pimcore.ecommerce.commitorderprocessor.preCommitOrderPayment';

    /**
     * @Event("Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\CommitOrderProcessorEvent")
     *
     * @var string
     */
    const POST_COMMIT_ORDER_PAYMENT = 'pimcore.ecommerce.commitorderprocessor.postCommitOrderPayment';

    /**
     * @Event("Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\CommitOrderProcessorEvent")
     *
     * @var string
     */
    const PRE_COMMIT_ORDER = 'pimcore.ecommerce.commitorderprocessor.preCommitOrder';

    /**
     * @Event("Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\CommitOrderProcessorEvent")
     *
     * @var string
     */
    const POST_COMMIT_ORDER = 'pimcore.ecommerce.commitorderprocessor.postCommitOrder';

    /**
     * @Event("Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\SendConfirmationMailEvent")
     *
     * @var string
     */
    const SEND_CONFIRMATION_MAILS = 'pimcore.ecommerce.commitorderprocessor.sendConfirmationMails';
}
