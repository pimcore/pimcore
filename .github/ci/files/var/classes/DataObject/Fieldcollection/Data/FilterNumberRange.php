<?php
declare(strict_types=1);

/**
 * Fields Summary:
 * - label [input]
 * - field [indexFieldSelection]
 * - rangeFrom [numeric]
 * - rangeTo [numeric]
 * - preSelectFrom [numeric]
 * - preSelectTo [numeric]
 * - scriptPath [input]
 */

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class FilterNumberRange extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType
{
protected string $type = "FilterNumberRange";
protected ?string $label;
protected ?\Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection $field;
protected ?float $rangeFrom;
protected ?float $rangeTo;
protected ?float $preSelectFrom;
protected ?float $preSelectTo;
protected ?string $scriptPath;


/**
* Get label - Label
* @return string|null
*/
public function getLabel(): ?string
{
	$data = $this->label;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set label - Label
* @param string|null $label
* @return $this
*/
public function setLabel(?string $label): static
{
	$this->label = $label;

	return $this;
}

/**
* Get field - Field
* @return \Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection|null
*/
public function getField(): ?\Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection
{
	$data = $this->field;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set field - Field
* @param \Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection|null $field
* @return $this
*/
public function setField(?\Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection $field): static
{
	$this->field = $field;

	return $this;
}

/**
* Get rangeFrom - Range From
* @return float|null
*/
public function getRangeFrom(): ?float
{
	$data = $this->rangeFrom;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set rangeFrom - Range From
* @param float|null $rangeFrom
* @return $this
*/
public function setRangeFrom(?float $rangeFrom): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("rangeFrom");
	$this->rangeFrom = $fd->preSetData($this, $rangeFrom);
	return $this;
}

/**
* Get rangeTo - Range To
* @return float|null
*/
public function getRangeTo(): ?float
{
	$data = $this->rangeTo;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set rangeTo - Range To
* @param float|null $rangeTo
* @return $this
*/
public function setRangeTo(?float $rangeTo): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("rangeTo");
	$this->rangeTo = $fd->preSetData($this, $rangeTo);
	return $this;
}

/**
* Get preSelectFrom - Pre Select From
* @return float|null
*/
public function getPreSelectFrom(): ?float
{
	$data = $this->preSelectFrom;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set preSelectFrom - Pre Select From
* @param float|null $preSelectFrom
* @return $this
*/
public function setPreSelectFrom(?float $preSelectFrom): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("preSelectFrom");
	$this->preSelectFrom = $fd->preSetData($this, $preSelectFrom);
	return $this;
}

/**
* Get preSelectTo - Pre Select To
* @return float|null
*/
public function getPreSelectTo(): ?float
{
	$data = $this->preSelectTo;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set preSelectTo - Pre Select To
* @param float|null $preSelectTo
* @return $this
*/
public function setPreSelectTo(?float $preSelectTo): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getDefinition()->getFieldDefinition("preSelectTo");
	$this->preSelectTo = $fd->preSetData($this, $preSelectTo);
	return $this;
}

/**
* Get scriptPath - Script Path
* @return string|null
*/
public function getScriptPath(): ?string
{
	$data = $this->scriptPath;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set scriptPath - Script Path
* @param string|null $scriptPath
* @return $this
*/
public function setScriptPath(?string $scriptPath): static
{
	$this->scriptPath = $scriptPath;

	return $this;
}

}

