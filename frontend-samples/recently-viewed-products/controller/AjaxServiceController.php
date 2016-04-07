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


class OnlineShop_AjaxServiceController extends Website_Controller_Action {

    const RECENTLY_VIEWED_PRODUCTS = "RECENTLY_VIEWED_PRODUCTS";
    const MAX_PRODUCTS = 2;

    public function recentlyViewedProductsAction() {
        $env = \OnlineShop\Framework\Factory::getInstance()->getEnvironment();

        $recentlyViewed = $env->getCustomItem(self::RECENTLY_VIEWED_PRODUCTS);
        if (!$recentlyViewed) {
            $recentlyViewed = array();
        }

        $exists = false;
        if (in_array($this->_getParam('id'), $recentlyViewed)) {
            $exists = true;
        }
        if (!$exists && $this->_getParam('id')) {
            array_push($recentlyViewed, $this->_getParam('id'));
            if (count($recentlyViewed) > self::MAX_PRODUCTS + 1) {
                array_shift($recentlyViewed);
            }

        }

        $env->setCustomItem(self::RECENTLY_VIEWED_PRODUCTS, $recentlyViewed);
        $env->save();


        unset($recentlyViewed[array_search($this->_getParam('id'), $recentlyViewed)]);

        $products = array();
        foreach ($recentlyViewed as $productId) {
            $products[] = Website_DefaultProduct::getById($productId);
        }


        $this->view->products = $products;
        $this->view->currentId = htmlentities($this->_getParam("id"));

        $this->renderScript('includes/recently-viewed-products.php');
    }

	
}
