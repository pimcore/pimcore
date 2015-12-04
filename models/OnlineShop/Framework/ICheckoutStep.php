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


/**
 * Interface for checkout step implementations of online shop framework
 */
interface OnlineShop_Framework_ICheckoutStep {

    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     */
    public function __construct(\OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * @return string
     */
    public function getName();

    /**
     * returns saved data of step
     *
     * @return mixed
     */
    public function getData();

    /**
     * sets delivered data and commits step
     *
     * @param  $data
     * @return bool
     */
    public function commit($data);

}
