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


class OnlineShop_VoucherController extends Pimcore\Controller\Action\Admin
{

    /**
     *  Loads and shows voucherservice backend tab
     */
    public function voucherCodeTabAction()
    {
        $onlineShopVoucherSeries = \Pimcore\Model\Object\AbstractObject::getById($this->getParam('id'));
        if ($onlineShopVoucherSeries instanceof \Pimcore\Model\Object\OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                $this->view->series = $onlineShopVoucherSeries;
                $renderScript = $tokenManager->prepareConfigurationView($this->view, $this->getAllParams());
                $this->renderScript($renderScript);
            } else {
                $this->view->errors = array($this->view->ts('plugin_onlineshop_voucherservice_msg-error-config-missing'));
                $this->renderScript('voucher/voucher-code-tab-error.php');
            }
        }
    }

    /**
     * @param \Pimcore\Model\Object\OnlineShopVoucherSeries $onlineShopVoucherSeries
     * @param \OnlineShop\Framework\VoucherService\ITokenManager $tokenManager
     * @param array $params
     */
    public function renderTab(\Pimcore\Model\Object\OnlineShopVoucherSeries $onlineShopVoucherSeries, \OnlineShop\Framework\VoucherService\ITokenManager $tokenManager, $params = [])
    {
        $this->view->series = $onlineShopVoucherSeries;
        $viewParams = array_merge($params, $this->getAllParams());
        $renderScript = $tokenManager->prepareConfigurationView($this->view, $viewParams);
        $this->renderScript($renderScript);
    }

    /**
     * Generates new Tokens or Applies single token settings.
     */
    public function generateAction()
    {
        $onlineShopVoucherSeries = \Pimcore\Model\Object\AbstractObject::getById($this->getParam('id'));
        if ($onlineShopVoucherSeries instanceof \Pimcore\Model\Object\OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                $result = $tokenManager->insertOrUpdateVoucherSeries();
                if ($result == true) {
                    $this->renderTab($onlineShopVoucherSeries, $tokenManager, array('success' => $this->view->ts('plugin_onlineshop_voucherservice_msg-success-generation')));
                } else {
                    $this->renderTab($onlineShopVoucherSeries, $tokenManager, array('error' => $this->view->ts('plugin_onlineshop_voucherservice_msg-error-generation')));
                }
            }
        } else {
            throw new Exception('Could not get voucher series, probably you did not provide a correct id.');
        }
    }

    /**
     * Removes tokens due to given filter parameters.
     */
    public function cleanupAction()
    {
        $onlineShopVoucherSeries = \Pimcore\Model\Object\AbstractObject::getById($this->getParam('id'));
        if ($onlineShopVoucherSeries instanceof \Pimcore\Model\Object\OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {

                // Prepare cleanUp parameter array.
                $params = [];
                $this->getParam('usage') ? $params['usage'] = $this->getParam('usage') : '';
                $this->getParam('olderThan') ? $params['olderThan'] = $this->getParam('olderThan') : '';

                if (empty($params['usage'])) {
                    $this->renderTab($onlineShopVoucherSeries, $tokenManager, array('error' => $this->view->ts('plugin_onlineshop_voucherservice_msg-error-required-missing')));
                    return;
                }

                if ($tokenManager->cleanUpCodes($params)) {
                    $this->renderTab($onlineShopVoucherSeries, $tokenManager, array('success' => $this->view->ts('plugin_onlineshop_voucherservice_msg-success-cleanup')));
                } else {
                    $this->renderTab($onlineShopVoucherSeries, $tokenManager, array('error' => $this->view->ts('plugin_onlineshop_voucherservice_msg-error-cleanup')));
                }
            }
        } else {
            throw new Exception('Could not get voucher series, probably you did not provide a correct id.');
        }
    }

    /**
     * Removes token reservations due to given duration.
     *
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function cleanupReservationsAction()
    {
        $duration = $this->getParam('duration');
        $id = $this->getParam('id');

        if (isset($duration)) {
            $onlineShopVoucherSeries = \Pimcore\Model\Object\AbstractObject::getById($this->getParam('id'));
            if ($onlineShopVoucherSeries instanceof \Pimcore\Model\Object\OnlineShopVoucherSeries) {
                if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                    if ($tokenManager->cleanUpReservations($duration)) {
                        $this->forward('voucher-Code-Tab', 'Voucher', null, ['success' => $this->view->ts('plugin_onlineshop_voucherservice_msg-success-cleanup-reservations'), 'id' => $id]);
                    }
                }
            } else {
                $this->forward('voucher-Code-Tab', 'Voucher', null, ['error' => $this->view->ts('plugin_onlineshop_voucherservice_msg-error-cleanup-reservations'), 'id' => $id]);
            }
        } else {
            $this->forward('voucher-Code-Tab', 'Voucher', null, ['error' => $this->view->ts('plugin_onlineshop_voucherservice_msg-error-cleanup-reservations-duration-missing'), 'id' => $id]);
        }
    }

}



