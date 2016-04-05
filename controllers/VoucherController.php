<?php
use OnlineShop\Framework\VoucherService\TokenManager\IExportableTokenManager;

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


class EcommerceFramework_VoucherController extends Pimcore\Controller\Action\Admin
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

                if ($tokenManager instanceof IExportableTokenManager) {
                    $this->view->supportsExport = true;
                }

                $renderScript = $tokenManager->prepareConfigurationView($this->view, $this->getAllParams());
                $this->renderScript($renderScript);
            } else {
                $this->view->errors = array($this->view->ts('plugin_onlineshop_voucherservice_msg-error-config-missing'));
                $this->renderScript('voucher/voucher-code-tab-error.php');
            }
        }
    }

    /**
     * Export tokens to file. The action should implement all export formats defined in IExportableTokenManager.
     */
    public function exportTokensAction()
    {
        $this->disableLayout();
        $this->disableViewAutoRender();

        $onlineShopVoucherSeries = \Pimcore\Model\Object\AbstractObject::getById($this->getParam('id'));
        if (!($onlineShopVoucherSeries instanceof \Pimcore\Model\Object\OnlineShopVoucherSeries)) {
            throw new InvalidArgumentException('Voucher series not found');
        }

        /** @var \Pimcore\Model\Object\OnlineShopVoucherSeries $onlineShopVoucherSeries */
        $tokenManager = $onlineShopVoucherSeries->getTokenManager();
        if (!(null !== $tokenManager && $tokenManager instanceof IExportableTokenManager)) {
            throw new InvalidArgumentException('Token manager does not support exporting');
        }

        $format      = $this->getParam('format', IExportableTokenManager::FORMAT_CSV);
        $contentType = null;
        $suffix      = null;
        $download    = true;

        $result = '';
        switch ($format) {
            case IExportableTokenManager::FORMAT_CSV:
                $result      = $tokenManager->exportCsv($this->getAllParams());
                $contentType = 'text/csv';
                $suffix      = 'csv';
                break;

            case IExportableTokenManager::FORMAT_PLAIN:
                $result      = $tokenManager->exportPlain($this->getAllParams());
                $contentType = 'text/plain';
                $suffix      = 'txt';
                $download    = false;
                break;

            default:
                throw new InvalidArgumentException('Invalid format');
        }

        $response = $this->getResponse();
        $response
            ->setBody($result)
            ->setHeader('Content-Type', $contentType)
            ->setHeader('Content-Length', strlen($result));

        if ($download && null !== $suffix) {
            $response->setHeader('Content-Disposition', sprintf('attachment; filename="voucher-export.%s"', $suffix));
        }
    }

    /**
     * @param \Pimcore\Model\Object\OnlineShopVoucherSeries $onlineShopVoucherSeries
     * @param \OnlineShop\Framework\VoucherService\TokenManager\ITokenManager $tokenManager
     * @param array $params
     */
    public function renderTab(\Pimcore\Model\Object\OnlineShopVoucherSeries $onlineShopVoucherSeries, \OnlineShop\Framework\VoucherService\TokenManager\ITokenManager $tokenManager, $params = [])
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



