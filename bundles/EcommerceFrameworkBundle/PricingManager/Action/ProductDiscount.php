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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ActionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

// TODO use Decimal for amounts?
class ProductDiscount implements ProductDiscountInterface
{
    /**
     * @var float
     */
    protected $amount = 0;

    /**
     * @var float
     */
    protected $percent = 0;

    /**
     * @param EnvironmentInterface $environment
     *
     * @return ActionInterface
     */
    public function executeOnProduct(EnvironmentInterface $environment)
    {
        $priceinfo = $environment->getPriceInfo();

        $amount = Decimal::create($this->amount);

        // TODO use discount()?
        if ($amount->equals(Decimal::create(0))) {
            $amount = $priceinfo->getAmount()->mul($this->getPercent() / 100);
        }

        $amount = $priceinfo->getAmount()->sub($amount);
        $priceinfo->setAmount($amount->isPositive() ? $amount : Decimal::zero());

        return $this;
    }

    /**
     * @param EnvironmentInterface $environment
     *
     * @return ActionInterface
     */
    public function executeOnCart(EnvironmentInterface $environment)
    {
        //nothing to to here
        return $this;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode([
            'type' => 'ProductDiscount',
            'amount' => $this->getAmount(),
            'percent' => $this->getPercent(),
        ]);
    }

    /**
     * @param string $string
     *
     * @return ActionInterface
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        if ($json->amount) {
            $this->setAmount($json->amount);
        }
        if ($json->percent) {
            $this->setPercent($json->percent);
        }

        return $this;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $percent
     */
    public function setPercent($percent)
    {
        $this->percent = $percent;
    }

    /**
     * @return float
     */
    public function getPercent()
    {
        return $this->percent;
    }
}
