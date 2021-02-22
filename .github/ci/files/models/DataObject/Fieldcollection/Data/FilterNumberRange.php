<?php 

/** 
Fields Summary: 
- label [input]
- field [indexFieldSelection]
- rangeFrom [numeric]
- rangeTo [numeric]
- preSelectFrom [numeric]
- preSelectTo [numeric]
- scriptPath [input]
*/ 

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class FilterNumberRange extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType {

protected $type = "FilterNumberRange";
protected $label;
protected $field;
protected $rangeFrom;
protected $rangeTo;
protected $preSelectFrom;
protected $preSelectTo;
protected $scriptPath;


/**
* Get label - Label
* @return string|null
*/
public function getLabel () {
	$data = $this->label;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set label - Label
* @param string|null $label
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRange
*/
public function setLabel ($label) {
	$fd = $this->getDefinition()->getFieldDefinition("label");
	$this->label = $label;
	return $this;
}

/**
* Get field - Field
* @return \Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ClassDefinition\IndexFieldSelection|null
*/
public function getField () {
	$data = $this->field;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set field - Field
* @param \Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ClassDefinition\IndexFieldSelection|null $field
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRange
*/
public function setField ($field) {
	$fd = $this->getDefinition()->getFieldDefinition("field");
	$this->field = $field;
	return $this;
}

/**
* Get rangeFrom - Range From
* @return float|null
*/
public function getRangeFrom () {
	$data = $this->rangeFrom;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set rangeFrom - Range From
* @param float|null $rangeFrom
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRange
*/
public function setRangeFrom ($rangeFrom) {
	$fd = $this->getDefinition()->getFieldDefinition("rangeFrom");
	$this->rangeFrom = $fd->preSetData($this, $rangeFrom);
	return $this;
}

/**
* Get rangeTo - Range To
* @return float|null
*/
public function getRangeTo () {
	$data = $this->rangeTo;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set rangeTo - Range To
* @param float|null $rangeTo
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRange
*/
public function setRangeTo ($rangeTo) {
	$fd = $this->getDefinition()->getFieldDefinition("rangeTo");
	$this->rangeTo = $fd->preSetData($this, $rangeTo);
	return $this;
}

/**
* Get preSelectFrom - Pre Select From
* @return float|null
*/
public function getPreSelectFrom () {
	$data = $this->preSelectFrom;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set preSelectFrom - Pre Select From
* @param float|null $preSelectFrom
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRange
*/
public function setPreSelectFrom ($preSelectFrom) {
	$fd = $this->getDefinition()->getFieldDefinition("preSelectFrom");
	$this->preSelectFrom = $fd->preSetData($this, $preSelectFrom);
	return $this;
}

/**
* Get preSelectTo - Pre Select To
* @return float|null
*/
public function getPreSelectTo () {
	$data = $this->preSelectTo;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set preSelectTo - Pre Select To
* @param float|null $preSelectTo
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRange
*/
public function setPreSelectTo ($preSelectTo) {
	$fd = $this->getDefinition()->getFieldDefinition("preSelectTo");
	$this->preSelectTo = $fd->preSetData($this, $preSelectTo);
	return $this;
}

/**
* Get scriptPath - Script Path
* @return string|null
*/
public function getScriptPath () {
	$data = $this->scriptPath;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set scriptPath - Script Path
* @param string|null $scriptPath
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRange
*/
public function setScriptPath ($scriptPath) {
	$fd = $this->getDefinition()->getFieldDefinition("scriptPath");
	$this->scriptPath = $scriptPath;
	return $this;
}

}

