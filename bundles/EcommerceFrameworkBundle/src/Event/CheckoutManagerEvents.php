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

final class CheckoutManagerEvents
{
    /**
     * @Event("Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\CheckoutManagerStepsEvent")
     *
     * @var string
     */
    const PRE_COMMIT_STEP = 'pimcore.ecommerce.checkoutmanager.preCommitStep';

    /**
     * @Event("Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\CheckoutManagerStepsEvent")
     *
     * @var string
     */
    const POST_COMMIT_STEP = 'pimcore.ecommerce.checkoutmanager.postCommitStep';

    /**
     * @Event("Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model\CheckoutManagerStepsEvent")
     *
     * @var string
     */
    const INITIALIZE_STEP_STATE = 'pimcore.ecommerce.checkoutmanager.initializeStepState';
}
