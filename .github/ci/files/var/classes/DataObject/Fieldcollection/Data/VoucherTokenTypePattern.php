<?php

/**
Fields Summary:
- count [numeric]
- prefix [input]
- length [numeric]
- characterType [select]
- separator [input]
- separatorCount [numeric]
- allowOncePerCart [checkbox]
- onlyTokenPerCart [checkbox]
*/

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class VoucherTokenTypePattern extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherTokenType
{
protected $type = "VoucherTokenTypePattern";
protected $count;
protected $prefix;
protected $length;
protected $characterType;
protected $separator;
protected $separatorCount;
protected $allowOncePerCart;
protected $onlyTokenPerCart;


/**
* Get count - Token Count
* @return int|null
*/
public function getCount(): ?int
{
	$data = $this->count;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set count - Token Count
* @param int|null $count
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\VoucherTokenTypePattern
*/
public function setCount(?int $count)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("count");
	$this->count = $fd->preSetData($this, $count);

	return $this;
}

/**
* Get prefix - Prefix
* @return string|null
*/
public function getPrefix(): ?string
{
	$data = $this->prefix;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set prefix - Prefix
* @param string|null $prefix
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\VoucherTokenTypePattern
*/
public function setPrefix(?string $prefix)
{
	$this->prefix = $prefix;

	return $this;
}

/**
* Get length - Length
* @return int|null
*/
public function getLength(): ?int
{
	$data = $this->length;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set length - Length
* @param int|null $length
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\VoucherTokenTypePattern
*/
public function setLength(?int $length)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("length");
	$this->length = $fd->preSetData($this, $length);

	return $this;
}

/**
* Get characterType - Character Type
* @return string|null
*/
public function getCharacterType(): ?string
{
	$data = $this->characterType;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set characterType - Character Type
* @param string|null $characterType
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\VoucherTokenTypePattern
*/
public function setCharacterType(?string $characterType)
{
	$this->characterType = $characterType;

	return $this;
}

/**
* Get separator - Separator
* @return string|null
*/
public function getSeparator(): ?string
{
	$data = $this->separator;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set separator - Separator
* @param string|null $separator
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\VoucherTokenTypePattern
*/
public function setSeparator(?string $separator)
{
	$this->separator = $separator;

	return $this;
}

/**
* Get separatorCount - Every x character 
* @return string|null
*/
public function getSeparatorCount(): ?string
{
	$data = $this->separatorCount;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set separatorCount - Every x character 
* @param string|null $separatorCount
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\VoucherTokenTypePattern
*/
public function setSeparatorCount(?string $separatorCount)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("separatorCount");
	$this->separatorCount = $fd->preSetData($this, $separatorCount);

	return $this;
}

/**
* Get allowOncePerCart - Only allow one token of this type per cart
* @return bool|null
*/
public function getAllowOncePerCart(): ?bool
{
	$data = $this->allowOncePerCart;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set allowOncePerCart - Only allow one token of this type per cart
* @param bool|null $allowOncePerCart
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\VoucherTokenTypePattern
*/
public function setAllowOncePerCart(?bool $allowOncePerCart)
{
	$this->allowOncePerCart = $allowOncePerCart;

	return $this;
}

/**
* Get onlyTokenPerCart - Only token of a cart
* @return bool|null
*/
public function getOnlyTokenPerCart(): ?bool
{
	$data = $this->onlyTokenPerCart;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set onlyTokenPerCart - Only token of a cart
* @param bool|null $onlyTokenPerCart
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\VoucherTokenTypePattern
*/
public function setOnlyTokenPerCart(?bool $onlyTokenPerCart)
{
	$this->onlyTokenPerCart = $onlyTokenPerCart;

	return $this;
}

}

