<?php

namespace Pimcore\Model\DataObject\OnlineShopOrder;

class PaymentProvider extends \Pimcore\Model\DataObject\Objectbrick
{

    protected array $brickGetters = ['PaymentProviderUnzer'];


    protected ?\Pimcore\Model\DataObject\Objectbrick\Data\PaymentProviderUnzer $PaymentProviderUnzer = null;

    public function getPaymentProviderUnzer(): ?\Pimcore\Model\DataObject\Objectbrick\Data\PaymentProviderUnzer
    {
        return $this->PaymentProviderUnzer;
    }

    public function setPaymentProviderUnzer(\Pimcore\Model\DataObject\Objectbrick\Data\PaymentProviderUnzer $PaymentProviderUnzer): static
    {
        $this->PaymentProviderUnzer = $PaymentProviderUnzer;
        return $this;
    }

}

