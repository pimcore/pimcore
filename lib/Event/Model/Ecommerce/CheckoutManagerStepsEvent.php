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

namespace Pimcore\Event\Model\Ecommerce;

use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutStepInterface;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Component\EventDispatcher\Event;

class CheckoutManagerStepsEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var CheckoutStepInterface
     */
    protected $currentStep;

    /**
     * @var CheckoutManagerInterface
     */
    protected $checkoutManager;

    /**
     * @param CheckoutManagerInterface $checkoutManager
     * @param CheckoutStepInterface|null $currentStep
     * @param array $arguments
     */
    public function __construct(CheckoutManagerInterface $checkoutManager, ?CheckoutStepInterface $currentStep, array $arguments = [])
    {
        $this->checkoutManager = $checkoutManager;
        $this->currentStep = $currentStep;
        $this->arguments = $arguments;
    }

    /**
     * @return CheckoutStepInterface|null
     */
    public function getCurrentStep(): ?CheckoutStepInterface
    {
        return $this->currentStep;
    }

    /**
     * @param CheckoutStepInterface|null $currentStep
     */
    public function setCurrentStep(?CheckoutStepInterface $currentStep): void
    {
        $this->currentStep = $currentStep;
    }

    /**
     * @return CheckoutManagerInterface
     */
    public function getCheckoutManager(): CheckoutManagerInterface
    {
        return $this->checkoutManager;
    }

    /**
     * @param CheckoutManagerInterface $checkoutManager
     */
    public function setCheckoutManager(CheckoutManagerInterface $checkoutManager): void
    {
        $this->checkoutManager = $checkoutManager;
    }
}
