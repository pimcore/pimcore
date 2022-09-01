<?php

/**
* Inheritance: no
* Variants: no


Fields Summary:
- ordernumber [input]
- orderState [select]
- orderdate [datetime]
- items [manyToManyObjectRelation]
- comment [textarea]
- customerOrderData [input]
- voucherTokens [manyToManyObjectRelation]
- giftItems [manyToManyObjectRelation]
- priceModifications [fieldcollections]
- subTotalNetPrice [numeric]
- subTotalPrice [numeric]
- totalNetPrice [numeric]
- totalPrice [numeric]
- taxInfo [table]
- currency [input]
- cartId [input]
- successorOrder [manyToOneRelation]
- cartHash [numeric]
- customer [manyToOneRelation]
- customerFirstname [input]
- customerLastname [input]
- customerCompany [input]
- customerStreet [input]
- customerZip [input]
- customerCity [input]
- customerCountry [country]
- customerEmail [input]
- deliveryFirstname [input]
- deliveryLastname [input]
- deliveryCompany [input]
- deliveryStreet [input]
- deliveryZip [input]
- deliveryCity [input]
- deliveryCountry [country]
- paymentProvider [objectbricks]
- paymentInfo [fieldcollections]
- paymentReference [input]
- customized [objectbricks]
*/

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

/**
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing getList()
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByOrdernumber($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByOrderState($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByOrderdate($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByItems($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByComment($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerOrderData($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByVoucherTokens($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByGiftItems($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getBySubTotalNetPrice($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getBySubTotalPrice($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByTotalNetPrice($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByTotalPrice($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCurrency($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCartId($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getBySuccessorOrder($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCartHash($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomer($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerFirstname($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerLastname($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerCompany($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerStreet($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerZip($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerCity($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerCountry($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerEmail($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryFirstname($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryLastname($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryCompany($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryStreet($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryZip($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryCity($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryCountry($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByPaymentReference($value, $limit = 0, $offset = 0)
*/

class OnlineShopOrder extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder
{
protected $o_classId = "EF_OSO";
protected $o_className = "OnlineShopOrder";
protected $ordernumber;
protected $orderState;
protected $orderdate;
protected $items;
protected $comment;
protected $customerOrderData;
protected $voucherTokens;
protected $giftItems;
protected $priceModifications;
protected $subTotalNetPrice;
protected $subTotalPrice;
protected $totalNetPrice;
protected $totalPrice;
protected $taxInfo;
protected $currency;
protected $cartId;
protected $successorOrder;
protected $cartHash;
protected $customer;
protected $customerFirstname;
protected $customerLastname;
protected $customerCompany;
protected $customerStreet;
protected $customerZip;
protected $customerCity;
protected $customerCountry;
protected $customerEmail;
protected $deliveryFirstname;
protected $deliveryLastname;
protected $deliveryCompany;
protected $deliveryStreet;
protected $deliveryZip;
protected $deliveryCity;
protected $deliveryCountry;
protected $paymentProvider;
protected $paymentInfo;
protected $paymentReference;
protected $customized;


/**
* @param array $values
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get ordernumber - Ordernumber
* @return string|null
*/
public function getOrdernumber(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("ordernumber");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->ordernumber;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set ordernumber - Ordernumber
* @param string|null $ordernumber
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setOrdernumber(?string $ordernumber)
{
	$this->ordernumber = $ordernumber;

	return $this;
}

/**
* Get orderState - OrderState
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
* Set orderState - OrderState
* @param string|null $orderState
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setOrderState(?string $orderState)
{
	$this->orderState = $orderState;

	return $this;
}

/**
* Get orderdate - Orderdate
* @return \Carbon\Carbon|null
*/
public function getOrderdate(): ?\Carbon\Carbon
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("orderdate");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->orderdate;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set orderdate - Orderdate
* @param \Carbon\Carbon|null $orderdate
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setOrderdate(?\Carbon\Carbon $orderdate)
{
	$this->orderdate = $orderdate;

	return $this;
}

/**
* Get items - Items
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem[]
*/
public function getItems(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("items");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("items");
	$data = $fd->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set items - Items
* @param \Pimcore\Model\DataObject\OnlineShopOrderItem[] $items
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setItems(?array $items)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("items");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getItems();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $items);
	if (!$isEqual) {
		$this->markFieldDirty("items", true);
	}
	$this->items = $fd->preSetData($this, $items);

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
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setComment(?string $comment)
{
	$this->comment = $comment;

	return $this;
}

/**
* Get customerOrderData - Customer Order Data
* @return string|null
*/
public function getCustomerOrderData(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customerOrderData");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->customerOrderData;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customerOrderData - Customer Order Data
* @param string|null $customerOrderData
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomerOrderData(?string $customerOrderData)
{
	$this->customerOrderData = $customerOrderData;

	return $this;
}

/**
* Get voucherTokens - Voucher Tokens
* @return \Pimcore\Model\DataObject\OnlineShopVoucherToken[]
*/
public function getVoucherTokens(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("voucherTokens");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("voucherTokens");
	$data = $fd->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set voucherTokens - Voucher Tokens
* @param \Pimcore\Model\DataObject\OnlineShopVoucherToken[] $voucherTokens
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setVoucherTokens(?array $voucherTokens)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("voucherTokens");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getVoucherTokens();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $voucherTokens);
	if (!$isEqual) {
		$this->markFieldDirty("voucherTokens", true);
	}
	$this->voucherTokens = $fd->preSetData($this, $voucherTokens);

	return $this;
}

/**
* Get giftItems - Gift Items
* @return \Pimcore\Model\DataObject\OnlineShopOrderItem[]
*/
public function getGiftItems(): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("giftItems");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("giftItems");
	$data = $fd->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set giftItems - Gift Items
* @param \Pimcore\Model\DataObject\OnlineShopOrderItem[] $giftItems
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setGiftItems(?array $giftItems)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("giftItems");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getGiftItems();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $giftItems);
	if (!$isEqual) {
		$this->markFieldDirty("giftItems", true);
	}
	$this->giftItems = $fd->preSetData($this, $giftItems);

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getPriceModifications()
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("priceModifications");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("priceModifications");
	$data = $fd->preGetData($this);

	return $data;
}

/**
* Set priceModifications - PriceModifications
* @param \Pimcore\Model\DataObject\Fieldcollection|null $priceModifications
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setPriceModifications(?\Pimcore\Model\DataObject\Fieldcollection $priceModifications)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("priceModifications");
	$this->priceModifications = $fd->preSetData($this, $priceModifications);

	return $this;
}

/**
* Get subTotalNetPrice - SubTotalNetPrice
* @return string|null
*/
public function getSubTotalNetPrice(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("subTotalNetPrice");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->subTotalNetPrice;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set subTotalNetPrice - SubTotalNetPrice
* @param string|null $subTotalNetPrice
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setSubTotalNetPrice(?string $subTotalNetPrice)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("subTotalNetPrice");
	$this->subTotalNetPrice = $fd->preSetData($this, $subTotalNetPrice);

	return $this;
}

/**
* Get subTotalPrice - SubTotalPrice
* @return string|null
*/
public function getSubTotalPrice(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("subTotalPrice");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->subTotalPrice;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set subTotalPrice - SubTotalPrice
* @param string|null $subTotalPrice
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setSubTotalPrice(?string $subTotalPrice)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("subTotalPrice");
	$this->subTotalPrice = $fd->preSetData($this, $subTotalPrice);

	return $this;
}

/**
* Get totalNetPrice - TotalNetPrice
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
* Set totalNetPrice - TotalNetPrice
* @param string|null $totalNetPrice
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setTotalNetPrice(?string $totalNetPrice)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("totalNetPrice");
	$this->totalNetPrice = $fd->preSetData($this, $totalNetPrice);

	return $this;
}

/**
* Get totalPrice - TotalPrice
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
* Set totalPrice - TotalPrice
* @param string|null $totalPrice
* @return \Pimcore\Model\DataObject\OnlineShopOrder
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
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setTaxInfo(?array $taxInfo)
{
	$this->taxInfo = $taxInfo;

	return $this;
}

/**
* Get currency - Currency
* @return string|null
*/
public function getCurrency(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("currency");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->currency;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set currency - Currency
* @param string|null $currency
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCurrency(?string $currency)
{
	$this->currency = $currency;

	return $this;
}

/**
* Get cartId - Cart ID
* @return string|null
*/
public function getCartId(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("cartId");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->cartId;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set cartId - Cart ID
* @param string|null $cartId
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCartId(?string $cartId)
{
	$this->cartId = $cartId;

	return $this;
}

/**
* Get successorOrder - Successor Order
* @return \Pimcore\Model\DataObject\OnlineShopOrder|null
*/
public function getSuccessorOrder(): ?\Pimcore\Model\Element\AbstractElement
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("successorOrder");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("successorOrder");
	$data = $fd->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set successorOrder - Successor Order
* @param \Pimcore\Model\DataObject\OnlineShopOrder $successorOrder
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setSuccessorOrder(?\Pimcore\Model\Element\AbstractElement $successorOrder)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("successorOrder");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getSuccessorOrder();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $successorOrder);
	if (!$isEqual) {
		$this->markFieldDirty("successorOrder", true);
	}
	$this->successorOrder = $fd->preSetData($this, $successorOrder);

	return $this;
}

/**
* Get cartHash - Cart Hash
* @return int|null
*/
public function getCartHash(): ?int
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("cartHash");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->cartHash;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set cartHash - Cart Hash
* @param int|null $cartHash
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCartHash(?int $cartHash)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("cartHash");
	$this->cartHash = $fd->preSetData($this, $cartHash);

	return $this;
}

/**
* Get customer - Customer
* @return \Pimcore\Model\DataObject\Customer|null
*/
public function getCustomer(): ?\Pimcore\Model\Element\AbstractElement
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customer");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("customer");
	$data = $fd->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customer - Customer
* @param \Pimcore\Model\DataObject\Customer $customer
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomer(?\Pimcore\Model\Element\AbstractElement $customer)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("customer");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getCustomer();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $customer);
	if (!$isEqual) {
		$this->markFieldDirty("customer", true);
	}
	$this->customer = $fd->preSetData($this, $customer);

	return $this;
}

/**
* Get customerFirstname - Firstname
* @return string|null
*/
public function getCustomerFirstname(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customerFirstname");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->customerFirstname;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customerFirstname - Firstname
* @param string|null $customerFirstname
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomerFirstname(?string $customerFirstname)
{
	$this->customerFirstname = $customerFirstname;

	return $this;
}

/**
* Get customerLastname - Lastname
* @return string|null
*/
public function getCustomerLastname(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customerLastname");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->customerLastname;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customerLastname - Lastname
* @param string|null $customerLastname
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomerLastname(?string $customerLastname)
{
	$this->customerLastname = $customerLastname;

	return $this;
}

/**
* Get customerCompany - Company
* @return string|null
*/
public function getCustomerCompany(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customerCompany");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->customerCompany;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customerCompany - Company
* @param string|null $customerCompany
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomerCompany(?string $customerCompany)
{
	$this->customerCompany = $customerCompany;

	return $this;
}

/**
* Get customerStreet - Street
* @return string|null
*/
public function getCustomerStreet(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customerStreet");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->customerStreet;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customerStreet - Street
* @param string|null $customerStreet
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomerStreet(?string $customerStreet)
{
	$this->customerStreet = $customerStreet;

	return $this;
}

/**
* Get customerZip - Zip
* @return string|null
*/
public function getCustomerZip(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customerZip");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->customerZip;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customerZip - Zip
* @param string|null $customerZip
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomerZip(?string $customerZip)
{
	$this->customerZip = $customerZip;

	return $this;
}

/**
* Get customerCity - City
* @return string|null
*/
public function getCustomerCity(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customerCity");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->customerCity;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customerCity - City
* @param string|null $customerCity
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomerCity(?string $customerCity)
{
	$this->customerCity = $customerCity;

	return $this;
}

/**
* Get customerCountry - Country
* @return string|null
*/
public function getCustomerCountry(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customerCountry");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->customerCountry;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customerCountry - Country
* @param string|null $customerCountry
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomerCountry(?string $customerCountry)
{
	$this->customerCountry = $customerCountry;

	return $this;
}

/**
* Get customerEmail - Email
* @return string|null
*/
public function getCustomerEmail(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customerEmail");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->customerEmail;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customerEmail - Email
* @param string|null $customerEmail
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomerEmail(?string $customerEmail)
{
	$this->customerEmail = $customerEmail;

	return $this;
}

/**
* Get deliveryFirstname - Firstname
* @return string|null
*/
public function getDeliveryFirstname(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("deliveryFirstname");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->deliveryFirstname;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set deliveryFirstname - Firstname
* @param string|null $deliveryFirstname
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setDeliveryFirstname(?string $deliveryFirstname)
{
	$this->deliveryFirstname = $deliveryFirstname;

	return $this;
}

/**
* Get deliveryLastname - Lastname
* @return string|null
*/
public function getDeliveryLastname(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("deliveryLastname");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->deliveryLastname;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set deliveryLastname - Lastname
* @param string|null $deliveryLastname
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setDeliveryLastname(?string $deliveryLastname)
{
	$this->deliveryLastname = $deliveryLastname;

	return $this;
}

/**
* Get deliveryCompany - Company
* @return string|null
*/
public function getDeliveryCompany(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("deliveryCompany");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->deliveryCompany;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set deliveryCompany - Company
* @param string|null $deliveryCompany
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setDeliveryCompany(?string $deliveryCompany)
{
	$this->deliveryCompany = $deliveryCompany;

	return $this;
}

/**
* Get deliveryStreet - Street
* @return string|null
*/
public function getDeliveryStreet(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("deliveryStreet");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->deliveryStreet;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set deliveryStreet - Street
* @param string|null $deliveryStreet
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setDeliveryStreet(?string $deliveryStreet)
{
	$this->deliveryStreet = $deliveryStreet;

	return $this;
}

/**
* Get deliveryZip - Zip
* @return string|null
*/
public function getDeliveryZip(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("deliveryZip");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->deliveryZip;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set deliveryZip - Zip
* @param string|null $deliveryZip
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setDeliveryZip(?string $deliveryZip)
{
	$this->deliveryZip = $deliveryZip;

	return $this;
}

/**
* Get deliveryCity - City
* @return string|null
*/
public function getDeliveryCity(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("deliveryCity");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->deliveryCity;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set deliveryCity - City
* @param string|null $deliveryCity
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setDeliveryCity(?string $deliveryCity)
{
	$this->deliveryCity = $deliveryCity;

	return $this;
}

/**
* Get deliveryCountry - Country
* @return string|null
*/
public function getDeliveryCountry(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("deliveryCountry");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->deliveryCountry;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set deliveryCountry - Country
* @param string|null $deliveryCountry
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setDeliveryCountry(?string $deliveryCountry)
{
	$this->deliveryCountry = $deliveryCountry;

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\OnlineShopOrder\PaymentProvider
*/
public function getPaymentProvider(): ?\Pimcore\Model\DataObject\Objectbrick
{
	$data = $this->paymentProvider;
	if (!$data) {
		if (\Pimcore\Tool::classExists("\\Pimcore\\Model\\DataObject\\OnlineShopOrder\\PaymentProvider")) {
			$data = new \Pimcore\Model\DataObject\OnlineShopOrder\PaymentProvider($this, "paymentProvider");
			$this->paymentProvider = $data;
		} else {
			return null;
		}
	}
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("paymentProvider");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	return $data;
}

/**
* Set paymentProvider - Payment Provider
* @param \Pimcore\Model\DataObject\Objectbrick|null $paymentProvider
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setPaymentProvider(?\Pimcore\Model\DataObject\Objectbrick $paymentProvider)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks $fd */
	$fd = $this->getClass()->getFieldDefinition("paymentProvider");
	$this->paymentProvider = $fd->preSetData($this, $paymentProvider);

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getPaymentInfo()
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("paymentInfo");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("paymentInfo");
	$data = $fd->preGetData($this);

	return $data;
}

/**
* Set paymentInfo - Payment Informations
* @param \Pimcore\Model\DataObject\Fieldcollection|null $paymentInfo
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setPaymentInfo(?\Pimcore\Model\DataObject\Fieldcollection $paymentInfo)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("paymentInfo");
	$this->paymentInfo = $fd->preSetData($this, $paymentInfo);

	return $this;
}

/**
* Get paymentReference - Payment Ref.
* @return string|null
*/
public function getPaymentReference(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("paymentReference");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->paymentReference;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set paymentReference - Payment Ref.
* @param string|null $paymentReference
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setPaymentReference(?string $paymentReference)
{
	$this->paymentReference = $paymentReference;

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\OnlineShopOrder\Customized
*/
public function getCustomized(): ?\Pimcore\Model\DataObject\Objectbrick
{
	$data = $this->customized;
	if (!$data) {
		if (\Pimcore\Tool::classExists("\\Pimcore\\Model\\DataObject\\OnlineShopOrder\\Customized")) {
			$data = new \Pimcore\Model\DataObject\OnlineShopOrder\Customized($this, "customized");
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
* @return \Pimcore\Model\DataObject\OnlineShopOrder
*/
public function setCustomized(?\Pimcore\Model\DataObject\Objectbrick $customized)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks $fd */
	$fd = $this->getClass()->getFieldDefinition("customized");
	$this->customized = $fd->preSetData($this, $customized);

	return $this;
}

}

