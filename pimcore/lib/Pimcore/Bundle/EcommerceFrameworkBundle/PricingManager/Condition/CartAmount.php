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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

class CartAmount implements ICartAmount
{
    /**
     * @var float
     */
    protected $limit;

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return bool
     */
    public function check(\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
    {
        if (!$environment->getCart() || $environment->getProduct() !== null) {
            return false;
        }

        return $environment->getCart()->getPriceCalculator()->getSubTotal()->getAmount() >= $this->getLimit();
    }

    /**
     * @param float $limit
     *
     * @return ICartAmount
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return float
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode([
            'type' => 'CartAmount',
            'limit' => $this->getLimit()
        ]);
    }

    /**
     * @param string $string
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        $this->setLimit($json->limit);

        return $this;
    }
}
