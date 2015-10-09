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


class OnlineShop_AjaxServiceController extends Website_Controller_Action {

    const RECENTLY_VIEWED_PRODUCTS = "RECENTLY_VIEWED_PRODUCTS";
    const MAX_PRODUCTS = 2;

    public function recentlyViewedProductsAction() {
        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();

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
