<?php

/**
* Inheritance: no
* Variants: no


Fields Summary:
- name [input]
- tokenSettings [fieldcollections]
*/

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

/**
* @method static \Pimcore\Model\DataObject\OnlineShopVoucherSeries\Listing getList()
* @method static \Pimcore\Model\DataObject\OnlineShopVoucherSeries\Listing|\Pimcore\Model\DataObject\OnlineShopVoucherSeries|null getByName($value, $limit = 0, $offset = 0)
*/

class OnlineShopVoucherSeries extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherSeries
{
protected $o_classId = "EF_OSVS";
protected $o_className = "OnlineShopVoucherSeries";
protected $name;
protected $tokenSettings;


/**
* @param array $values
* @return \Pimcore\Model\DataObject\OnlineShopVoucherSeries
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get name - Name
* @return string|null
*/
public function getName(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("name");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->name;

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set name - Name
* @param string|null $name
* @return \Pimcore\Model\DataObject\OnlineShopVoucherSeries
*/
public function setName(?string $name)
{
	$this->name = $name;

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getTokenSettings()
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("tokenSettings");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("tokenSettings");
	$data = $fd->preGetData($this);

	return $data;
}

/**
* Set tokenSettings - Token Settings
* @param \Pimcore\Model\DataObject\Fieldcollection|null $tokenSettings
* @return \Pimcore\Model\DataObject\OnlineShopVoucherSeries
*/
public function setTokenSettings(?\Pimcore\Model\DataObject\Fieldcollection $tokenSettings)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("tokenSettings");
	$this->tokenSettings = $fd->preSetData($this, $tokenSettings);

	return $this;
}

}

