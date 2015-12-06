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

namespace OnlineShop\Framework\PriceSystem;

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
                throw new \OnlineShop_Framework_Exception_UnsupportedException($name . " is not supported for " . get_class($this));
            }
            if ($priceInfo != null && method_exists($priceInfo, "setPriceSystem")) {
                $priceInfo->setPriceSystem($this->getPriceSystem());
            }
            $this->priceRegistry[$name] = $priceInfo;
        }

        return $this->priceRegistry[$name];
    }
}