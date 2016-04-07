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


class EcommerceFramework_AdminController extends \Pimcore\Controller\Action\Admin {

    public function settingsAction() {
        if($this->getRequest()->isPost()) {
            \OnlineShop\Plugin::setConfig($this->_getParam("onlineshop_config_file"));
            $this->view->onlineshop_config_file = \OnlineShop\Plugin::getConfig()->onlineshop_config_file;
        } else {
            $this->view->onlineshop_config_file = \OnlineShop\Plugin::getConfig()->onlineshop_config_file;
        }
    }

    public function clearCacheAction() {
        \Pimcore\Model\Cache::clearTag("ecommerceconfig");
        exit;
    }

}
