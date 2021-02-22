<?php 

/** 
Fields Summary: 
- label [input]
- field [indexFieldSelection]
- useAndCondition [checkbox]
- scriptPath [input]
- availableRelations [manyToManyObjectRelation]
*/ 

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class FilterMultiRelation extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType {

protected $type = "FilterMultiRelation";
protected $label;
protected $field;
protected $useAndCondition;
protected $scriptPath;
protected $availableRelations;


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
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterMultiRelation
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
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterMultiRelation
*/
public function setField ($field) {
	$fd = $this->getDefinition()->getFieldDefinition("field");
	$this->field = $field;
	return $this;
}

/**
* Get useAndCondition - Use And Condition
* @return bool|null
*/
public function getUseAndCondition () {
	$data = $this->useAndCondition;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set useAndCondition - Use And Condition
* @param bool|null $useAndCondition
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterMultiRelation
*/
public function setUseAndCondition ($useAndCondition) {
	$fd = $this->getDefinition()->getFieldDefinition("useAndCondition");
	$this->useAndCondition = $useAndCondition;
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
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterMultiRelation
*/
public function setScriptPath ($scriptPath) {
	$fd = $this->getDefinition()->getFieldDefinition("scriptPath");
	$this->scriptPath = $scriptPath;
	return $this;
}

/**
* Get availableRelations - Available Relations
* @return \Pimcore\Model\DataObject\AbstractObject[]
*/
public function getAvailableRelations () {
	$container = $this;
	$fd = $this->getDefinition()->getFieldDefinition("availableRelations");
	$data = $fd->preGetData($container);
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set availableRelations - Available Relations
* @param \Pimcore\Model\DataObject\AbstractObject[] $availableRelations
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterMultiRelation
*/
public function setAvailableRelations ($availableRelations) {
	$fd = $this->getDefinition()->getFieldDefinition("availableRelations");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getAvailableRelations();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $availableRelations);
	if (!$isEqual) {
		$this->markFieldDirty("availableRelations", true);
	}
	$this->availableRelations = $fd->preSetData($this, $availableRelations);
	return $this;
}

}

