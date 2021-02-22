<?php 

/** 
Fields Summary: 
- label [input]
- field [indexFieldSelection]
- ranges [structuredTable]
- preSelectFrom [numeric]
- preSelectTo [numeric]
- scriptPath [input]
- unit [input]
*/ 

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class FilterNumberRangeSelection extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType {

protected $type = "FilterNumberRangeSelection";
protected $label;
protected $field;
protected $ranges;
protected $preSelectFrom;
protected $preSelectTo;
protected $scriptPath;
protected $unit;


/**
* Get label - Label
* @return string|null
*/
public function getLabel (): ?string {
	$data = $this->label;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set label - Label
* @param string|null $label
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRangeSelection
*/
public function setLabel (?string $label) {
	$fd = $this->getDefinition()->getFieldDefinition("label");
	$this->label = $label;
	return $this;
}

/**
* Get field - Field
* @return \Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection|null
*/
public function getField (): ?\Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection {
	$data = $this->field;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set field - Field
* @param \Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection|null $field
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRangeSelection
*/
public function setField (?\Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection $field) {
	$fd = $this->getDefinition()->getFieldDefinition("field");
	$this->field = $field;
	return $this;
}

/**
* Get ranges - Ranges
* @return \Pimcore\Model\DataObject\Data\StructuredTable|null
*/
public function getRanges (): ?\Pimcore\Model\DataObject\Data\StructuredTable {
	$data = $this->ranges;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set ranges - Ranges
* @param \Pimcore\Model\DataObject\Data\StructuredTable|null $ranges
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRangeSelection
*/
public function setRanges (?\Pimcore\Model\DataObject\Data\StructuredTable $ranges) {
	$fd = $this->getDefinition()->getFieldDefinition("ranges");
	$this->ranges = $ranges;
	return $this;
}

/**
* Get preSelectFrom - Pre Select From
* @return float|null
*/
public function getPreSelectFrom (): ?float {
	$data = $this->preSelectFrom;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set preSelectFrom - Pre Select From
* @param float|null $preSelectFrom
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRangeSelection
*/
public function setPreSelectFrom (?float $preSelectFrom) {
	$fd = $this->getDefinition()->getFieldDefinition("preSelectFrom");
	$this->preSelectFrom = $fd->preSetData($this, $preSelectFrom);
	return $this;
}

/**
* Get preSelectTo - Pre Select To
* @return float|null
*/
public function getPreSelectTo (): ?float {
	$data = $this->preSelectTo;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set preSelectTo - Pre Select To
* @param float|null $preSelectTo
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRangeSelection
*/
public function setPreSelectTo (?float $preSelectTo) {
	$fd = $this->getDefinition()->getFieldDefinition("preSelectTo");
	$this->preSelectTo = $fd->preSetData($this, $preSelectTo);
	return $this;
}

/**
* Get scriptPath - Script Path
* @return string|null
*/
public function getScriptPath (): ?string {
	$data = $this->scriptPath;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set scriptPath - Script Path
* @param string|null $scriptPath
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRangeSelection
*/
public function setScriptPath (?string $scriptPath) {
	$fd = $this->getDefinition()->getFieldDefinition("scriptPath");
	$this->scriptPath = $scriptPath;
	return $this;
}

/**
* Get unit - Unit
* @return string|null
*/
public function getUnit (): ?string {
	$data = $this->unit;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set unit - Unit
* @param string|null $unit
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRangeSelection
*/
public function setUnit (?string $unit) {
	$fd = $this->getDefinition()->getFieldDefinition("unit");
	$this->unit = $unit;
	return $this;
}

}

