<?php

/**
Fields Summary:
- paymentStart [datetime]
- paymentFinish [datetime]
- paymentReference [input]
- paymentState [select]
- internalPaymentId [input]
- message [textarea]
- providerData [textarea]
- provider_unzer_amount [input]
- provider_unzer_PaymentType [input]
*/

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class PaymentInfo extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation
{
protected $type = "PaymentInfo";
protected $paymentStart;
protected $paymentFinish;
protected $paymentReference;
protected $paymentState;
protected $internalPaymentId;
protected $message;
protected $providerData;
protected $provider_unzer_amount;
protected $provider_unzer_PaymentType;


/**
* Get paymentStart - Payment Start
* @return \Carbon\Carbon|null
*/
public function getPaymentStart(): ?\Carbon\Carbon
{
	$data = $this->paymentStart;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set paymentStart - Payment Start
* @param \Carbon\Carbon|null $paymentStart
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo
*/
public function setPaymentStart(?\Carbon\Carbon $paymentStart)
{
	$this->paymentStart = $paymentStart;

	return $this;
}

/**
* Get paymentFinish - Payment Finish
* @return \Carbon\Carbon|null
*/
public function getPaymentFinish(): ?\Carbon\Carbon
{
	$data = $this->paymentFinish;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set paymentFinish - Payment Finish
* @param \Carbon\Carbon|null $paymentFinish
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo
*/
public function setPaymentFinish(?\Carbon\Carbon $paymentFinish)
{
	$this->paymentFinish = $paymentFinish;

	return $this;
}

/**
* Get paymentReference - Payment Reference
* @return string|null
*/
public function getPaymentReference(): ?string
{
	$data = $this->paymentReference;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set paymentReference - Payment Reference
* @param string|null $paymentReference
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo
*/
public function setPaymentReference(?string $paymentReference)
{
	$this->paymentReference = $paymentReference;

	return $this;
}

/**
* Get paymentState - Payment State
* @return string|null
*/
public function getPaymentState(): ?string
{
	$data = $this->paymentState;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set paymentState - Payment State
* @param string|null $paymentState
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo
*/
public function setPaymentState(?string $paymentState)
{
	$this->paymentState = $paymentState;

	return $this;
}

/**
* Get internalPaymentId - Internal Payment ID
* @return string|null
*/
public function getInternalPaymentId(): ?string
{
	$data = $this->internalPaymentId;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set internalPaymentId - Internal Payment ID
* @param string|null $internalPaymentId
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo
*/
public function setInternalPaymentId(?string $internalPaymentId)
{
	$this->internalPaymentId = $internalPaymentId;

	return $this;
}

/**
* Get message - Message
* @return string|null
*/
public function getMessage(): ?string
{
	$data = $this->message;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set message - Message
* @param string|null $message
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo
*/
public function setMessage(?string $message)
{
	$this->message = $message;

	return $this;
}

/**
* Get providerData - Provider Data
* @return string|null
*/
public function getProviderData(): ?string
{
	$data = $this->providerData;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set providerData - Provider Data
* @param string|null $providerData
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo
*/
public function setProviderData(?string $providerData)
{
	$this->providerData = $providerData;

	return $this;
}

/**
* Get provider_unzer_amount - Amount
* @return string|null
*/
public function getProvider_unzer_amount(): ?string
{
	$data = $this->provider_unzer_amount;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set provider_unzer_amount - Amount
* @param string|null $provider_unzer_amount
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo
*/
public function setProvider_unzer_amount(?string $provider_unzer_amount)
{
	$this->provider_unzer_amount = $provider_unzer_amount;

	return $this;
}

/**
* Get provider_unzer_PaymentType - Payment Type
* @return string|null
*/
public function getProvider_unzer_PaymentType(): ?string
{
	$data = $this->provider_unzer_PaymentType;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set provider_unzer_PaymentType - Payment Type
* @param string|null $provider_unzer_PaymentType
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo
*/
public function setProvider_unzer_PaymentType(?string $provider_unzer_PaymentType)
{
	$this->provider_unzer_PaymentType = $provider_unzer_PaymentType;

	return $this;
}

}

