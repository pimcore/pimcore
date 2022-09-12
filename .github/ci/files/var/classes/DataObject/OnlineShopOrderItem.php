<?php

/**
* Inheritance: no
* Variants: no


Fields Summary:
- orderState [select]
- product [manyToOneRelation]
- productNumber [input]
- productName [input]
- amount [numeric]
- totalNetPrice [numeric]
- totalPrice [numeric]
- taxInfo [table]
- pricingRules [fieldcollections]
- comment [textarea]
- subItems [manyToManyObjectRelation]
- customized [objectbricks]
*/

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

/**
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing getList()
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByOrderState($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByProduct($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByProductNumber($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByProductName($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByAmount($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByTotalNetPrice($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByTotalPrice($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByComment($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getBySubItems($value, $limit = 0, $offset = 0)
*/

class OnlineShopOrderItem extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem
{
protected $o_classId = "EF_OSOI";
protected $o_className = "OnlineShopOrderItem";
protected $orderState;
protected $product;
protected $productNumber;
protected $productName;
protected $amount;
protected $totalNetPrice;
protected $totalPrice;
protected $taxInfo;
protected $pricingRules;
protected $comment;
protected $subItems;
protected $customized;


/**
* @param array $values
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get orderState - Order Item State
* @return string|null
*/
public function getOrderState(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("orderState");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->orderState;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set orderState - Order Item State
* @param string|null $orderState
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setOrderState(?string $orderState)
{
	$this->orderState = $orderState;

	return $this;
}

/**
* Get product - Produkt
* @return \Pimcore\Model\DataObject\AbstractObject|null
*/
public function getProduct(): ?\Pimcore\Model\Element\AbstractElement
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("product");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("product");
	$data = $fd->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set product - Produkt
* @param \Pimcore\Model\DataObject\AbstractObject $product
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setProduct(?\Pimcore\Model\Element\AbstractElement $product)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("product");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getProduct();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $product);
	if (!$isEqual) {
		$this->markFieldDirty("product", true);
	}
	$this->product = $fd->preSetData($this, $product);

	return $this;
}

/**
* Get productNumber - Produktnummer
* @return string|null
*/
public function getProductNumber(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("productNumber");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->productNumber;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set productNumber - Produktnummer
* @param string|null $productNumber
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setProductNumber(?string $productNumber)
{
	$this->productNumber = $productNumber;

	return $this;
}

/**
* Get productName - Produktname
* @return string|null
*/
public function getProductName(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("productName");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->productName;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set productName - Produktname
* @param string|null $productName
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setProductName(?string $productName)
{
	$this->productName = $productName;

	return $this;
}

/**
* Get amount - Amount
* @return float|null
*/
public function getAmount(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("amount");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->amount;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set amount - Amount
* @param float|null $amount
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setAmount(?float $amount)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("amount");
	$this->amount = $fd->preSetData($this, $amount);

	return $this;
}

/**
* Get totalNetPrice - NetPrice
* @return string|null
*/
public function getTotalNetPrice(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("totalNetPrice");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->totalNetPrice;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set totalNetPrice - NetPrice
* @param string|null $totalNetPrice
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setTotalNetPrice(?string $totalNetPrice)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("totalNetPrice");
	$this->totalNetPrice = $fd->preSetData($this, $totalNetPrice);

	return $this;
}

/**
* Get totalPrice - Price
* @return string|null
*/
public function getTotalPrice(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("totalPrice");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->totalPrice;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set totalPrice - Price
* @param string|null $totalPrice
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setTotalPrice(?string $totalPrice)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("totalPrice");
	$this->totalPrice = $fd->preSetData($this, $totalPrice);

	return $this;
}

/**
* Get taxInfo - Tax Information
* @return array
*/
public function getTaxInfo(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("taxInfo");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->taxInfo;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain() ?? [];
	}

	return $data ?? [];
}

/**
* Set taxInfo - Tax Information
* @param array|null $taxInfo
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setTaxInfo(?array $taxInfo)
{
	$this->taxInfo = $taxInfo;

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getPricingRules()
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("pricingRules");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("pricingRules");
	$data = $fd->preGetData($this);

	return $data;
}

/**
* Set pricingRules - Pricing Rules
* @param \Pimcore\Model\DataObject\Fieldcollection|null $pricingRules
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setPricingRules(?\Pimcore\Model\DataObject\Fieldcollection $pricingRules)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("pricingRules");
	$this->pricingRules = $fd->preSetData($this, $pricingRules);

	return $this;
}

/**
* Get comment - Comment
* @return string|null
*/
public function getComment(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("comment");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->comment;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set comment - Comment
* @param string|null $comment
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setComment(?string $comment)
{
	$this->comment = $comment;

	return $this;
}

/**
* Get subItems - Subitems
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem[]
*/
public function getSubItems(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("subItems");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("subItems");
	$data = $fd->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set subItems - Subitems
* @param \Pimcore\Model\DataObject\OnlineShopOrderItem[] $subItems
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setSubItems(?array $subItems)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("subItems");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getSubItems();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $subItems);
	if (!$isEqual) {
		$this->markFieldDirty("subItems", true);
	}
	$this->subItems = $fd->preSetData($this, $subItems);

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem\Customized
*/
public function getCustomized(): ?\Pimcore\Model\DataObject\Objectbrick
{
	$data = $this->customized;
	if (!$data) {
		if (\Pimcore\Tool::classExists("\\Pimcore\\Model\\DataObject\\OnlineShopOrderItem\\Customized")) {
			$data = new \Pimcore\Model\DataObject\OnlineShopOrderItem\Customized($this, "customized");
			$this->customized = $data;
		} else {
			return null;
		}
	}
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customized");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	return $data;
}

/**
* Set customized - Customized
* @param \Pimcore\Model\DataObject\Objectbrick|null $customized
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem
*/
public function setCustomized(?\Pimcore\Model\DataObject\Objectbrick $customized)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks $fd */
	$fd = $this->getClass()->getFieldDefinition("customized");
	$this->customized = $fd->preSetData($this, $customized);

	return $this;
}

}

