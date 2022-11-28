<?php
declare(strict_types=1);

/**
 * Fields Summary:
 * - token [input]
 * - usages [numeric]
 * - onlyTokenPerCart [checkbox]
 */

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class VoucherTokenTypeSingle extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherTokenType
{
protected string $type = "VoucherTokenTypeSingle";
protected ?string $token;
protected ?int $usages;
protected ?bool $onlyTokenPerCart;


/**
* Get token - Token
* @return string|null
*/
public function getToken(): ?string
{
	$data = $this->token;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set token - Token
* @param string|null $token
* @return $this
*/
public function setToken(?string $token): static
{
	$this->token = $token;

	return $this;
}

/**
* Get usages - Usage count
* @return int|null
*/
public function getUsages(): ?int
{
	$data = $this->usages;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set usages - Usage count
* @param int|null $usages
* @return $this
*/
public function setUsages(?int $usages): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("usages");
	$this->usages = $fd->preSetData($this, $usages);
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
* @return $this
*/
public function setOnlyTokenPerCart(?bool $onlyTokenPerCart): static
{
	$this->onlyTokenPerCart = $onlyTokenPerCart;

	return $this;
}

}

