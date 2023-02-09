<?php
declare(strict_types=1);

/**
 * Inheritance: no
 * Variants: no
 *
 * Fields Summary:
 * - ordernumber [input]
 * - orderState [select]
 * - orderdate [datetime]
 * - items [manyToManyObjectRelation]
 * - comment [textarea]
 * - customerOrderData [input]
 * - voucherTokens [manyToManyObjectRelation]
 * - giftItems [manyToManyObjectRelation]
 * - priceModifications [fieldcollections]
 * - subTotalNetPrice [numeric]
 * - subTotalPrice [numeric]
 * - totalNetPrice [numeric]
 * - totalPrice [numeric]
 * - taxInfo [table]
 * - currency [input]
 * - cartId [input]
 * - successorOrder [manyToOneRelation]
 * - cartHash [numeric]
 * - customer [manyToOneRelation]
 * - customerFirstname [input]
 * - customerLastname [input]
 * - customerCompany [input]
 * - customerStreet [input]
 * - customerZip [input]
 * - customerCity [input]
 * - customerCountry [country]
 * - customerEmail [input]
 * - deliveryFirstname [input]
 * - deliveryLastname [input]
 * - deliveryCompany [input]
 * - deliveryStreet [input]
 * - deliveryZip [input]
 * - deliveryCity [input]
 * - deliveryCountry [country]
 * - paymentProvider [objectbricks]
 * - paymentInfo [fieldcollections]
 * - paymentReference [input]
 * - customized [objectbricks]
 */

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;
use Pimcore\Model\Element\AbstractElement;

/**
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing getList(array $config = [])
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByOrdernumber(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByOrderState(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByOrderdate(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByItems(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByComment(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerOrderData(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByVoucherTokens(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByGiftItems(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getBySubTotalNetPrice(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getBySubTotalPrice(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByTotalNetPrice(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByTotalPrice(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCurrency(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCartId(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getBySuccessorOrder(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCartHash(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomer(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerFirstname(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerLastname(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerCompany(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerStreet(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerZip(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerCity(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerCountry(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByCustomerEmail(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryFirstname(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryLastname(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryCompany(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryStreet(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryZip(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryCity(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByDeliveryCountry(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrder\Listing|\Pimcore\Model\DataObject\OnlineShopOrder|null getByPaymentReference(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
*/

class OnlineShopOrder extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder
{
protected $classId = "EF_OSO";
protected $className = "OnlineShopOrder";
protected ?string $ordernumber;
protected ?string $orderState;
protected ?\Carbon\Carbon $orderdate;
protected array $items;
protected ?string $comment;
protected ?string $customerOrderData;
protected array $voucherTokens;
protected array $giftItems;
protected ?Fieldcollection $priceModifications;
protected ?string $subTotalNetPrice;
protected ?string $subTotalPrice;
protected ?string $totalNetPrice;
protected ?string $totalPrice;
protected array $taxInfo;
protected ?string $currency;
protected ?string $cartId;
protected \Pimcore\Model\Element\AbstractElement|null|OnlineShopOrder $successorOrder;
protected ?int $cartHash;
protected \Pimcore\Model\Element\AbstractElement|Customer|null $customer;
protected ?string $customerFirstname;
protected ?string $customerLastname;
protected ?string $customerCompany;
protected ?string $customerStreet;
protected ?string $customerZip;
protected ?string $customerCity;
protected ?string $customerCountry;
protected ?string $customerEmail;
protected ?string $deliveryFirstname;
protected ?string $deliveryLastname;
protected ?string $deliveryCompany;
protected ?string $deliveryStreet;
protected ?string $deliveryZip;
protected ?string $deliveryCity;
protected ?string $deliveryCountry;
protected Objectbrick|null|OnlineShopOrder\PaymentProvider $paymentProvider;
protected ?Fieldcollection $paymentInfo;
protected ?string $paymentReference;
protected Objectbrick|OnlineShopOrder\Customized|null $customized;


public static function create(array $values = []): static
{
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
* @return $this
*/
public function setOrdernumber(?string $ordernumber): static
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
* @return $this
*/
public function setOrderState(?string $orderState): static
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
* @return $this
*/
public function setOrderdate(?\Carbon\Carbon $orderdate): static
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

	$data = $this->getClass()->getFieldDefinition("items")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set items - Items
* @param \Pimcore\Model\DataObject\OnlineShopOrderItem[] $items
* @return $this
*/
public function setItems(?array $items): static
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
* @return $this
*/
public function setComment(?string $comment): static
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
* @return $this
*/
public function setCustomerOrderData(?string $customerOrderData): static
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

	$data = $this->getClass()->getFieldDefinition("voucherTokens")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set voucherTokens - Voucher Tokens
* @param \Pimcore\Model\DataObject\OnlineShopVoucherToken[] $voucherTokens
* @return $this
*/
public function setVoucherTokens(?array $voucherTokens): static
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

	$data = $this->getClass()->getFieldDefinition("giftItems")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set giftItems - Gift Items
* @param \Pimcore\Model\DataObject\OnlineShopOrderItem[] $giftItems
* @return $this
*/
public function setGiftItems(?array $giftItems): static
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

    public function getPriceModifications(): ?Fieldcollection
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("priceModifications");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("priceModifications")->preGetData($this);
	return $data;
}

/**
* Set priceModifications - PriceModifications
* @param \Pimcore\Model\DataObject\Fieldcollection|null $priceModifications
* @return $this
*/
public function setPriceModifications(?\Pimcore\Model\DataObject\Fieldcollection $priceModifications): static
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
* @return $this
*/
public function setSubTotalNetPrice(?string $subTotalNetPrice): static
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
* @return $this
*/
public function setSubTotalPrice(?string $subTotalPrice): static
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
* @return $this
*/
public function setTotalNetPrice(?string $totalNetPrice): static
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
* @return $this
*/
public function setTotalPrice(?string $totalPrice): static
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
* @return $this
*/
public function setTaxInfo(?array $taxInfo): static
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
* @return $this
*/
public function setCurrency(?string $currency): static
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
* @return $this
*/
public function setCartId(?string $cartId): static
{
	$this->cartId = $cartId;

	return $this;
}

/**
* Get successorOrder - Successor Order
* @return OnlineShopOrder|\Pimcore\Model\Element\AbstractElement|\Pimcore\Model\Element\ElementInterface|null
*/
public function getSuccessorOrder(): OnlineShopOrder|\Pimcore\Model\Element\ElementInterface|\Pimcore\Model\Element\AbstractElement|null
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("successorOrder");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("successorOrder")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set successorOrder - Successor Order
* @param \Pimcore\Model\DataObject\OnlineShopOrder|null $successorOrder
* @return $this
*/
public function setSuccessorOrder(?\Pimcore\Model\Element\AbstractElement $successorOrder): static
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
* @return $this
*/
public function setCartHash(?int $cartHash): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("cartHash");
	$this->cartHash = $fd->preSetData($this, $cartHash);
	return $this;
}

/**
* Get customer - Customer
* @return Customer|\Pimcore\Model\Element\AbstractElement|\Pimcore\Model\Element\ElementInterface|null
*/
public function getCustomer(): Customer|\Pimcore\Model\Element\ElementInterface|\Pimcore\Model\Element\AbstractElement|null
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("customer");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("customer")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set customer - Customer
* @param \Pimcore\Model\DataObject\Customer|null $customer
* @return $this
*/
public function setCustomer(?\Pimcore\Model\Element\AbstractElement $customer): static
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
* @return $this
*/
public function setCustomerFirstname(?string $customerFirstname): static
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
* @return $this
*/
public function setCustomerLastname(?string $customerLastname): static
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
* @return $this
*/
public function setCustomerCompany(?string $customerCompany): static
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
* @return $this
*/
public function setCustomerStreet(?string $customerStreet): static
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
* @return $this
*/
public function setCustomerZip(?string $customerZip): static
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
* @return $this
*/
public function setCustomerCity(?string $customerCity): static
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
* @return $this
*/
public function setCustomerCountry(?string $customerCountry): static
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
* @return $this
*/
public function setCustomerEmail(?string $customerEmail): static
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
* @return $this
*/
public function setDeliveryFirstname(?string $deliveryFirstname): static
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
* @return $this
*/
public function setDeliveryLastname(?string $deliveryLastname): static
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
* @return $this
*/
public function setDeliveryCompany(?string $deliveryCompany): static
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
* @return $this
*/
public function setDeliveryStreet(?string $deliveryStreet): static
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
* @return $this
*/
public function setDeliveryZip(?string $deliveryZip): static
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
* @return $this
*/
public function setDeliveryCity(?string $deliveryCity): static
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
* @return $this
*/
public function setDeliveryCountry(?string $deliveryCountry): static
{
	$this->deliveryCountry = $deliveryCountry;

	return $this;
}

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
* @return $this
*/
public function setPaymentProvider(?\Pimcore\Model\DataObject\Objectbrick $paymentProvider): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks $fd */
	$fd = $this->getClass()->getFieldDefinition("paymentProvider");
	$this->paymentProvider = $fd->preSetData($this, $paymentProvider);
	return $this;
}

    public function getPaymentInfo(): ?Fieldcollection
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("paymentInfo");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("paymentInfo")->preGetData($this);
	return $data;
}

/**
* Set paymentInfo - Payment Informations
* @param \Pimcore\Model\DataObject\Fieldcollection|null $paymentInfo
* @return $this
*/
public function setPaymentInfo(?\Pimcore\Model\DataObject\Fieldcollection $paymentInfo): static
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
* @return $this
*/
public function setPaymentReference(?string $paymentReference): static
{
	$this->paymentReference = $paymentReference;

	return $this;
}

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
* @return $this
*/
public function setCustomized(?\Pimcore\Model\DataObject\Objectbrick $customized): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks $fd */
	$fd = $this->getClass()->getFieldDefinition("customized");
	$this->customized = $fd->preSetData($this, $customized);
	return $this;
}

}

