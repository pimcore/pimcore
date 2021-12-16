<?php

/**
* Inheritance: no
* Variants: no


Fields Summary:
- tokenId [numeric]
- token [input]
- voucherSeries [manyToOneRelation]
*/

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

/**
* @method static \Pimcore\Model\DataObject\OnlineShopVoucherToken\Listing getList()
* @method static \Pimcore\Model\DataObject\OnlineShopVoucherToken\Listing|\Pimcore\Model\DataObject\OnlineShopVoucherToken|null getByTokenId($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopVoucherToken\Listing|\Pimcore\Model\DataObject\OnlineShopVoucherToken|null getByToken($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\OnlineShopVoucherToken\Listing|\Pimcore\Model\DataObject\OnlineShopVoucherToken|null getByVoucherSeries($value, $limit = 0, $offset = 0)
*/

class OnlineShopVoucherToken extends Concrete
{
protected $o_classId = "EF_OSVT";
protected $o_className = "OnlineShopVoucherToken";
protected $tokenId;
protected $token;
protected $voucherSeries;


/**
* @param array $values
* @return \Pimcore\Model\DataObject\OnlineShopVoucherToken
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get tokenId - Token ID
* @return float|null
*/
public function getTokenId(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("tokenId");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->tokenId;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set tokenId - Token ID
* @param float|null $tokenId
* @return \Pimcore\Model\DataObject\OnlineShopVoucherToken
*/
public function setTokenId(?float $tokenId)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("tokenId");
	$this->tokenId = $fd->preSetData($this, $tokenId);

	return $this;
}

/**
* Get token - Token
* @return string|null
*/
public function getToken(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("token");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->token;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set token - Token
* @param string|null $token
* @return \Pimcore\Model\DataObject\OnlineShopVoucherToken
*/
public function setToken(?string $token)
{
	$this->token = $token;

	return $this;
}

/**
* Get voucherSeries - Voucher Series
* @return \Pimcore\Model\DataObject\OnlineShopVoucherSeries|null
*/
public function getVoucherSeries(): ?\Pimcore\Model\Element\AbstractElement
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("voucherSeries");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("voucherSeries");
	$data = $fd->preGetData($this);

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set voucherSeries - Voucher Series
* @param \Pimcore\Model\DataObject\OnlineShopVoucherSeries $voucherSeries
* @return \Pimcore\Model\DataObject\OnlineShopVoucherToken
*/
public function setVoucherSeries(?\Pimcore\Model\Element\AbstractElement $voucherSeries)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("voucherSeries");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getVoucherSeries();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $voucherSeries);
	if (!$isEqual) {
		$this->markFieldDirty("voucherSeries", true);
	}
	$this->voucherSeries = $fd->preSetData($this, $voucherSeries);

	return $this;
}

}

