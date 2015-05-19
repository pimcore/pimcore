<?php


class OnlineShop_VoucherController extends Pimcore\Controller\Action\Admin
{

    /**
     *
     */
    public function voucherCodeTabAction()
    {
        $onlineShopVoucherSeries = \Pimcore\Model\Object\AbstractObject::getById($this->getParam('id'));
        if ($onlineShopVoucherSeries instanceof \Pimcore\Model\Object\OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                $this->view->series = $onlineShopVoucherSeries;

                $renderScript = $tokenManager->prepareConfigurationView($this->view, $this->getAllParams());
                $this->renderScript($renderScript);
            }
        }
    }

    public function generateAction()
    {
        $onlineShopVoucherSeries = \Pimcore\Model\Object\AbstractObject::getById($this->getParam('id'));
        if ($onlineShopVoucherSeries instanceof \Pimcore\Model\Object\OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                $result = $tokenManager->insertOrUpdateVoucherSeries();
                if ($result !== true) {
                    $this->forward('voucher-Code-Tab', 'Voucher', null, ['error' => $result['error']]);
                } else {
                    $this->forward('voucher-Code-Tab', 'Voucher');
                }
            }
        }
    }

    public function cleanupAction()
    {
        $onlineShopVoucherSeries = \Pimcore\Model\Object\AbstractObject::getById($this->getParam('id'));
        if ($onlineShopVoucherSeries instanceof \Pimcore\Model\Object\OnlineShopVoucherSeries) {
            if ($tokenManager = $onlineShopVoucherSeries->getTokenManager()) {
                // Prepare cleanUp parameter array.
                $params = [];
                $this->getParam('used') ? $params['used'] = $this->getParam('used') : '';
                $this->getParam('unused') ? $params['unused'] = $this->getParam('unused') : '';
                $this->getParam('olderThan') ? $params['olderThan'] = $this->getParam('olderThan') : '';

                if ($tokenManager->cleanUpCodes($params)) {
                    $this->forward('voucher-Code-Tab', 'Voucher');
                }
            }
        }
        $this->forward('voucher-Code-Tab', 'Voucher', null, ['error' => 'Something went wrong.']); //TODO translate
    }

    public function cleanupReservationsAction()
    {
        $duration = $this->getParam('duration');
        if (isset($duration)) {
            $service = OnlineShop_Framework_Factory::getInstance()->getVoucherService();
            if ($service->cleanUpReservations($duration)) {
                $this->forward('voucher-Code-Tab', 'Voucher');
            }
        } else {
            $this->forward('voucher-Code-Tab', 'Voucher', null, ['error' => 'Please specify a duration.']); //TODO translate
        }
        $this->forward('voucher-Code-Tab', 'Voucher', null, ['error' => 'Something went wrong.']); //TODO translate
    }

}



