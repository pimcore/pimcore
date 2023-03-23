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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutStepInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\CheckoutManagerInterface;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class CheckoutManagerStepsEvent extends Event
{
    use ArgumentsAwareTrait;

    protected ?CheckoutStepInterface $currentStep = null;

    protected CheckoutManagerInterface $checkoutManager;

    public function __construct(CheckoutManagerInterface $checkoutManager, ?CheckoutStepInterface $currentStep, array $arguments = [])
    {
        $this->checkoutManager = $checkoutManager;
        $this->currentStep = $currentStep;
        $this->arguments = $arguments;
    }

    public function getCurrentStep(): ?CheckoutStepInterface
    {
        return $this->currentStep;
    }

    public function setCurrentStep(?CheckoutStepInterface $currentStep): void
    {
        $this->currentStep = $currentStep;
    }

    public function getCheckoutManager(): CheckoutManagerInterface
    {
        return $this->checkoutManager;
    }

    public function setCheckoutManager(CheckoutManagerInterface $checkoutManager): void
    {
        $this->checkoutManager = $checkoutManager;
    }
}
