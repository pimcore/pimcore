<?php
declare(strict_types=1);

/**
* Inheritance: no
* Variants: no


Fields Summary:
- taxEntryCombinationType [select]
- taxEntries [fieldcollections]
*/

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

/**
* @method static \Pimcore\Model\DataObject\OnlineShopTaxClass\Listing getList()
* @method static \Pimcore\Model\DataObject\OnlineShopTaxClass\Listing|\Pimcore\Model\DataObject\OnlineShopTaxClass|null getByTaxEntryCombinationType($value, $limit = 0, $offset = 0)
*/

class OnlineShopTaxClass extends Concrete
{
protected $o_classId = "EF_OSTC";
protected $o_className = "OnlineShopTaxClass";
protected ?string $taxEntryCombinationType;
protected ?Fieldcollection $taxEntries;


    public static function create(array $values = array()): OnlineShopTaxClass
{
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get taxEntryCombinationType - Tax Entry Combination Type
* @return string|null
*/
public function getTaxEntryCombinationType(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("taxEntryCombinationType");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->taxEntryCombinationType;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set taxEntryCombinationType - Tax Entry Combination Type
* @param string|null $taxEntryCombinationType
* @return \Pimcore\Model\DataObject\OnlineShopTaxClass
*/
public function setTaxEntryCombinationType(?string $taxEntryCombinationType): static
{
	$this->taxEntryCombinationType = $taxEntryCombinationType;

	return $this;
}

    public function getTaxEntries(): ?Fieldcollection
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("taxEntries");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("taxEntries");
	$data = $fd->preGetData($this);

	return $data;
}

/**
* Set taxEntries - Tax Entries
* @param \Pimcore\Model\DataObject\Fieldcollection|null $taxEntries
* @return \Pimcore\Model\DataObject\OnlineShopTaxClass
*/
public function setTaxEntries(?\Pimcore\Model\DataObject\Fieldcollection $taxEntries): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("taxEntries");
	$this->taxEntries = $fd->preSetData($this, $taxEntries);

	return $this;
}

}

