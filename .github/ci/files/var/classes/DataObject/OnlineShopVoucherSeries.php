<?php

/**
 * Inheritance: no
 * Variants: no
 *
 * Fields Summary:
 * - name [input]
 * - tokenSettings [fieldcollections]
 */

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

/**
* @method static \Pimcore\Model\DataObject\OnlineShopVoucherSeries\Listing getList(array $config = [])
* @method static \Pimcore\Model\DataObject\OnlineShopVoucherSeries\Listing|\Pimcore\Model\DataObject\OnlineShopVoucherSeries|null getByName(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
*/

class OnlineShopVoucherSeries extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherSeries
{
protected $o_classId = "EF_OSVS";
protected $o_className = "OnlineShopVoucherSeries";
protected $name;
protected $tokenSettings;


/**
* @param array $values
* @return static
*/
public static function create(array $values = []): static
{
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
* @return $this
*/
public function setName(?string $name): static
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

	$data = $this->getClass()->getFieldDefinition("tokenSettings")->preGetData($this);
	return $data;
}

/**
* Set tokenSettings - Token Settings
* @param \Pimcore\Model\DataObject\Fieldcollection|null $tokenSettings
* @return $this
*/
public function setTokenSettings(?\Pimcore\Model\DataObject\Fieldcollection $tokenSettings): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("tokenSettings");
	$this->tokenSettings = $fd->preSetData($this, $tokenSettings);
	return $this;
}

}

