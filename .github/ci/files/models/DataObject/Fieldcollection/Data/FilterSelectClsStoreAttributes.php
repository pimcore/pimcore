<?php 

/** 
Fields Summary: 
- label [input]
- field [indexFieldSelection]
- scriptPath [input]
- excludedKeyIds [textarea]
- keyIdPriorityOrder [textarea]
*/ 

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class FilterSelectClsStoreAttributes extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\CategoryFilterDefinitionType {

protected $type = "FilterSelectClsStoreAttributes";
protected $label;
protected $field;
protected $scriptPath;
protected $excludedKeyIds;
protected $keyIdPriorityOrder;


/**
* Get label - Label
* @return string
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
* @param string $label
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterSelectClsStoreAttributes
*/
public function setLabel ($label) {
	$fd = $this->getDefinition()->getFieldDefinition("label");
	$this->label = $label;
	return $this;
}

/**
* Get field - Field
* @return \Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection
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
* @param \Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection $field
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterSelectClsStoreAttributes
*/
public function setField ($field) {
	$fd = $this->getDefinition()->getFieldDefinition("field");
	$this->field = $field;
	return $this;
}

/**
* Get scriptPath - Script Path
* @return string
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
* @param string $scriptPath
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterSelectClsStoreAttributes
*/
public function setScriptPath ($scriptPath) {
	$fd = $this->getDefinition()->getFieldDefinition("scriptPath");
	$this->scriptPath = $scriptPath;
	return $this;
}

/**
* Get excludedKeyIds - Excluded KeyIDs (CSV)
* @return string
*/
public function getExcludedKeyIds () {
	$data = $this->excludedKeyIds;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set excludedKeyIds - Excluded KeyIDs (CSV)
* @param string $excludedKeyIds
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterSelectClsStoreAttributes
*/
public function setExcludedKeyIds ($excludedKeyIds) {
	$fd = $this->getDefinition()->getFieldDefinition("excludedKeyIds");
	$this->excludedKeyIds = $excludedKeyIds;
	return $this;
}

/**
* Get keyIdPriorityOrder - KeyID Priority Order (CSV)
* @return string
*/
public function getKeyIdPriorityOrder () {
	$data = $this->keyIdPriorityOrder;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set keyIdPriorityOrder - KeyID Priority Order (CSV)
* @param string $keyIdPriorityOrder
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterSelectClsStoreAttributes
*/
public function setKeyIdPriorityOrder ($keyIdPriorityOrder) {
	$fd = $this->getDefinition()->getFieldDefinition("keyIdPriorityOrder");
	$this->keyIdPriorityOrder = $keyIdPriorityOrder;
	return $this;
}

}

