<?php

/**
Fields Summary:
- name [input]
- netAmount [numeric]
- pricingRuleId [numeric]
- amount [numeric]
*/

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class OrderPriceModifications extends DataObject\Fieldcollection\Data\AbstractData
{
protected $type = "OrderPriceModifications";
protected $name;
protected $netAmount;
protected $pricingRuleId;
protected $amount;


/**
* Get name - Name
* @return string|null
*/
public function getName(): ?string
{
	$data = $this->name;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set name - Name
* @param string|null $name
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\OrderPriceModifications
*/
public function setName(?string $name)
{
	$this->name = $name;

	return $this;
}

/**
* Get netAmount - NetAmount
* @return string|null
*/
public function getNetAmount(): ?string
{
	$data = $this->netAmount;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set netAmount - NetAmount
* @param string|null $netAmount
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\OrderPriceModifications
*/
public function setNetAmount(?string $netAmount)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("netAmount");
	$this->netAmount = $fd->preSetData($this, $netAmount);

	return $this;
}

/**
* Get pricingRuleId - Applied pricing rule ID
* @return int|null
*/
public function getPricingRuleId(): ?int
{
	$data = $this->pricingRuleId;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set pricingRuleId - Applied pricing rule ID
* @param int|null $pricingRuleId
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\OrderPriceModifications
*/
public function setPricingRuleId(?int $pricingRuleId)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("pricingRuleId");
	$this->pricingRuleId = $fd->preSetData($this, $pricingRuleId);

	return $this;
}

/**
* Get amount - Amount
* @return string|null
*/
public function getAmount(): ?string
{
	$data = $this->amount;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set amount - Amount
* @param string|null $amount
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\OrderPriceModifications
*/
public function setAmount(?string $amount)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("amount");
	$this->amount = $fd->preSetData($this, $amount);

	return $this;
}

}

