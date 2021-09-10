<?php

namespace Pimcore\Model\DataObject\OnlineShopOrder;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;

class PaymentProvider extends \Pimcore\Model\DataObject\Objectbrick {

protected $brickGetters = ['PaymentProviderUnzer'];


protected $PaymentProviderUnzer = null;

/**
* @return \Pimcore\Model\DataObject\Objectbrick\Data\PaymentProviderUnzer|null
*/
public function getPaymentProviderUnzer()
{
	return $this->PaymentProviderUnzer;
}

/**
* @param \Pimcore\Model\DataObject\Objectbrick\Data\PaymentProviderUnzer $PaymentProviderUnzer
* @return \Pimcore\Model\DataObject\OnlineShopOrder\PaymentProvider
*/
public function setPaymentProviderUnzer($PaymentProviderUnzer)
{
	$this->PaymentProviderUnzer = $PaymentProviderUnzer;
	return $this;
}

}

