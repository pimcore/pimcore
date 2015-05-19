<?php

/**
 * Class OnlineShop_Framework_VoucherService_Default
 */
class OnlineShop_Framework_VoucherService_Default implements OnlineShop_Framework_IVoucherService
{


    public $sysConfig;

    public function __construct($config){
        $this->sysConfig = $config;
    }
    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function checkToken($code, OnlineShop_Framework_ICart $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->checkToken($code, $cart);
        }
        return false;
    }

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function reserveToken($code, OnlineShop_Framework_ICart $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->reserveToken($code, $cart);
        }
        return false;
    }

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @param OnlineShop_Framework_AbstractOrder $order
     * @return bool
     */
    public function applyToken($code, OnlineShop_Framework_ICart $cart, Onlineshop_Framework_AbstractOrder $order)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            if ($orderToken = $tokenManager->applyToken($code, $cart, $order)) {

                $voucherTokens = $order->getVoucherTokens();
                $voucherTokens[] = $orderToken;
                $order->setVoucherTokens($voucherTokens);

                $this->releaseToken($code, $cart);
            }
        }
        return false;
    }

    /**
     * @param string $code
     * @param OnlineShop_Framework_ICart $cart
     * @return bool
     */
    public function releaseToken($code, OnlineShop_Framework_ICart $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->releaseToken($code, $cart);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function cleanUpReservations($duration =  null){
        if(isset($duration)){
            return OnlineShop_Framework_VoucherService_Reservation::cleanUpReservations($duration);
        }else{
            return OnlineShop_Framework_VoucherService_Reservation::cleanUpReservations($this->sysConfig->reservations->duration);
        }

    }

    /**
     * @param $code
     * @return bool|OnlineShop_Framework_VoucherService_ITokenManager
     */
    public function getTokenManager($code)
    {
        if ($token = OnlineShop_Framework_VoucherService_Token::getByCode($code)) {
            if ($series = \Pimcore\Model\Object\OnlineShopVoucherSeries::getById($token->getVoucherSeriesId())) {
                return $series->getTokenManager();
            }
        }
        return false;
    }


}