<?php

/**
Fields Summary:
- localizedfields [localizedfields]
-- name [input]
- percent [numeric]
*/

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class TaxEntry extends DataObject\Fieldcollection\Data\AbstractData
{
protected $type = "TaxEntry";
protected $localizedfields;
protected $percent;


/**
* Get localizedfields - 
* @return \Pimcore\Model\DataObject\Localizedfield|null
*/
public function getLocalizedfields(): ?\Pimcore\Model\DataObject\Localizedfield
{
	$container = $this;
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields $fd */
	$fd = $this->getDefinition()->getFieldDefinition("localizedfields");
	$data = $fd->preGetData($container);
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Get name - Name
* @return string|null
*/
public function getName($language = null): ?string
{
	$data = $this->getLocalizedfields()->getLocalizedValue("name", $language);
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set localizedfields - 
* @param \Pimcore\Model\DataObject\Localizedfield|null $localizedfields
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\TaxEntry
*/
public function setLocalizedfields(?\Pimcore\Model\DataObject\Localizedfield $localizedfields)
{
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getLocalizedfields();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$this->markFieldDirty("localizedfields", true);
	$this->localizedfields = $localizedfields;

	return $this;
}

/**
* Set name - Name
* @param string|null $name
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\TaxEntry
*/
public function setName (?string $name, $language = null)
{
	$isEqual = false;
	$this->getLocalizedfields()->setLocalizedValue("name", $name, $language, !$isEqual);

	return $this;
}

/**
* Get percent - Tax Rate in Percent
* @return float|null
*/
public function getPercent(): ?float
{
	$data = $this->percent;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set percent - Tax Rate in Percent
* @param float|null $percent
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\TaxEntry
*/
public function setPercent(?float $percent)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("percent");
	$this->percent = $fd->preSetData($this, $percent);

	return $this;
}

}

