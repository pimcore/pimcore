<?php
declare(strict_types=1);

/**
 * Inheritance: no
 * Variants: no
 *
 * Fields Summary:
 * - orderState [select]
 * - product [manyToOneRelation]
 * - productNumber [input]
 * - productName [input]
 * - amount [numeric]
 * - totalNetPrice [numeric]
 * - totalPrice [numeric]
 * - taxInfo [table]
 * - pricingRules [fieldcollections]
 * - comment [textarea]
 * - subItems [manyToManyObjectRelation]
 * - customized [objectbricks]
 */

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;
use Pimcore\Model\Element\AbstractElement;

/**
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing getList(array $config = [])
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByOrderState(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByProduct(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByProductNumber(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByProductName(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByAmount(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByTotalNetPrice(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByTotalPrice(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getByComment(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
* @method static \Pimcore\Model\DataObject\OnlineShopOrderItem\Listing|\Pimcore\Model\DataObject\OnlineShopOrderItem|null getBySubItems(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
*/

class OnlineShopOrderItem extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem
{
protected $classId = "EF_OSOI";
protected $className = "OnlineShopOrderItem";
protected ?string $orderState = null;
protected \Pimcore\Model\Element\AbstractElement|AbstractObject|null $product = null;
protected ?string $productNumber = null;
protected ?string $productName = null;
protected ?float $amount = null;
protected ?string $totalNetPrice = null;
protected ?string $totalPrice = null;
protected array $taxInfo;
protected ?Fieldcollection $pricingRules = null;
protected ?string $comment = null;
protected array $subItems;
protected Objectbrick|null|OnlineShopOrderItem\Customized $customized = null;


public static function create(array $values = []): static
{
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
* @return $this
*/
public function setOrderState(?string $orderState): static
{
	$this->orderState = $orderState;

	return $this;
}

/**
* Get product - Produkt
* @return AbstractObject|\Pimcore\Model\Element\AbstractElement|\Pimcore\Model\Element\ElementInterface|null
*/
public function getProduct(): AbstractObject|\Pimcore\Model\Element\ElementInterface|\Pimcore\Model\Element\AbstractElement|null
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("product");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("product")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set product - Produkt
* @param \Pimcore\Model\DataObject\AbstractObject|null $product
* @return $this
*/
public function setProduct(?\Pimcore\Model\Element\AbstractElement $product): static
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
* @return $this
*/
public function setProductNumber(?string $productNumber): static
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
* @return $this
*/
public function setProductName(?string $productName): static
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
* @return $this
*/
public function setAmount(?float $amount): static
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

    public function getPricingRules(): ?Fieldcollection
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("pricingRules");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getClass()->getFieldDefinition("pricingRules")->preGetData($this);
	return $data;
}

/**
* Set pricingRules - Pricing Rules
* @param \Pimcore\Model\DataObject\Fieldcollection|null $pricingRules
* @return $this
*/
public function setPricingRules(?\Pimcore\Model\DataObject\Fieldcollection $pricingRules): static
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
* @return $this
*/
public function setComment(?string $comment): static
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

	$data = $this->getClass()->getFieldDefinition("subItems")->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set subItems - Subitems
* @param \Pimcore\Model\DataObject\OnlineShopOrderItem[] $subItems
* @return $this
*/
public function setSubItems(?array $subItems): static
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

