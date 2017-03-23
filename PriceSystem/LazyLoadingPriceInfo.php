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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem;

/**
 * Class LazyLoadingPriceInfo
 *
 * Base implementation for a lazy loading price info
 *
 */
class LazyLoadingPriceInfo extends AbstractPriceInfo implements IPriceInfo
{
    public static function getInstance()
    {
        return parent::getInstance();
    }

    protected $priceRegistry = array();


    public function getPrice()
    {
        parent::getPrice();
    }

    function __call($name, $arg)
    {
        if (array_key_exists($name, $this->priceRegistry)) {
            return $this->priceRegistry[$name];
        } else {
            if (method_exists($this, "_" . $name)) {
                $priceInfo = $this->{"_" . $name}();

            } else if (method_exists($this->getPriceSystem(), $name)) {
                $method = $name;
                $priceInfo = $this->getPriceSystem()->$method($this->getProduct(), $this->getQuantity(), $this->getProducts());

            } else {
                throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException($name . " is not supported for " . get_class($this));
            }
            if ($priceInfo != null && method_exists($priceInfo, "setPriceSystem")) {
                $priceInfo->setPriceSystem($this->getPriceSystem());
            }
            $this->priceRegistry[$name] = $priceInfo;
        }

        return $this->priceRegistry[$name];
    }
}