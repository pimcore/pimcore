<?php

abstract class OnlineShop_Framework_AbstractVoucherSeries extends \Pimcore\Model\Object\Concrete
{

    /**
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    public abstract function getTokenSettings();


    /**
     * @return bool|OnlineShop_Framework_VoucherService_ITokenManager
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function getTokenManager()
    {

        $items = $this->getTokenSettings();
        if ($items && $items->get(0)) {

            // name of fieldcollection class
            $configuration = $items->get(0);
            return OnlineShop_Framework_Factory::getInstance()->getTokenManager($configuration);

        }
        return false;
    }
}