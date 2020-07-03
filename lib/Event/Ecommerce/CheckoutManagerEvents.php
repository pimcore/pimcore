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

final class CheckoutManagerEvents
{
    /**
     * @Event("Pimcore\Event\Model\Ecommerce\CheckoutManagerStepsEvent")
     *
     * @var string
     */
    const PRE_COMMIT_STEP = 'pimcore.ecommerce.checkoutmanager.preCommitStep';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\CheckoutManagerStepsEvent")
     *
     * @var string
     */
    const POST_COMMIT_STEP = 'pimcore.ecommerce.checkoutmanager.postCommitStep';

    /**
     * @Event("Pimcore\Event\Model\Ecommerce\CheckoutManagerStepsEvent")
     *
     * @var string
     */
    const INITIALIZE_STEP_STATE = 'pimcore.ecommerce.checkoutmanager.initializeStepState';
}
