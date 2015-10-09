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


class OnlineShop_AdminController extends \Pimcore\Controller\Action\Admin {

    public function settingsAction() {
        if($this->getRequest()->isPost()) {
            OnlineShop_Plugin::setConfig($this->_getParam("onlineshop_config_file"));
            $this->view->onlineshop_config_file = OnlineShop_Plugin::getConfig()->onlineshop_config_file;
        } else {
            $this->view->onlineshop_config_file = OnlineShop_Plugin::getConfig()->onlineshop_config_file;
        }
    }

    public function clearCacheAction() {
        \Pimcore\Model\Cache::clearTag("ecommerceconfig");
        exit;
    }

}
