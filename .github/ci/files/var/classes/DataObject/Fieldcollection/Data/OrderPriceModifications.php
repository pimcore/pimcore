<?php
declare(strict_types=1);

/**
 * Fields Summary:
 * - name [input]
 * - netAmount [numeric]
 * - pricingRuleId [numeric]
 * - amount [numeric]
 */

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class OrderPriceModifications extends DataObject\Fieldcollection\Data\AbstractData
{
protected string $type = "OrderPriceModifications";
protected ?string $name;
protected ?string $netAmount;
protected ?int $pricingRuleId;
protected ?string $amount;


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
* @return $this
*/
public function setName(?string $name): static
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
* @return $this
*/
public function setNetAmount(?string $netAmount): static
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
* @return $this
*/
public function setPricingRuleId(?int $pricingRuleId): static
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
* @return $this
*/
public function setAmount(?string $amount): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("amount");
	$this->amount = $fd->preSetData($this, $amount);
	return $this;
}

}

