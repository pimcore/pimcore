<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


class OnlineShop_Framework_Impl_Pricing_Condition_CartAmount implements OnlineShop_Framework_Pricing_Condition_ICartAmount
{
    /**
     * @var float
     */
    protected $limit;

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        if(!$environment->getCart() || $environment->getProduct() !== null) {
            return false;
        }


        return $environment->getCart()->getPriceCalculator()->getSubTotal()->getAmount() >= $this->getLimit();
    }

    /**
     * @param float $limit
     *
     * @return OnlineShop_Framework_Pricing_Condition_ICartAmount
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
        return json_encode(array(
            'type' => 'CartAmount',
            'limit' => $this->getLimit()
        ));
    }

    /**
     * @param string $string
     *
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        $this->setLimit($json->limit);

        return $this;
    }
}