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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model;

/**
 * Class for product entry of a set product - container for product and quantity
 */
class AbstractSetProductEntry {

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var ICheckoutable
     */
    private $product;

    public function __construct(ICheckoutable $product, $quantity = 1) {
        $this->product = $product;
        $this->quantity = $quantity;
    }


    /**
     * @param ICheckoutable $product
     * @return void
     */
    public function setProduct(ICheckoutable $product) {
        $this->product = $product;
    }

    /**
     * @return ICheckoutable
     */
    public function getProduct() {
        return $this->product;
    }

    /**
     * @param  int $quantity
     * @return void
     */
    public function setQuantity($quantity) {
        $this->quantity = $quantity;
    }

    /**
     * @return int
     */
    public function getQuantity() {
        return $this->quantity;
    }

    /**
     * returns id of set product
     *
     * @return int
     */
    public function getId() {
        if($this->getProduct()) {
            return $this->getProduct()->getId();
        }
        return null;
    }
}
