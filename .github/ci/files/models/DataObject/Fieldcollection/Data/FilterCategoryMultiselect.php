<?php 

/** 
Fields Summary: 
- label [input]
- preSelect [manyToManyObjectRelation]
- useAndCondition [checkbox]
- includeParentCategories [checkbox]
- scriptPath [input]
- availableCategories [manyToManyObjectRelation]
*/ 

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class FilterCategoryMultiselect extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\CategoryFilterDefinitionType {

protected $type = "FilterCategoryMultiselect";
protected $label;
protected $preSelect;
protected $useAndCondition;
protected $includeParentCategories;
protected $scriptPath;
protected $availableCategories;


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
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategoryMultiselect
*/
public function setLabel (?string $label) {
	$fd = $this->getDefinition()->getFieldDefinition("label");
	$this->label = $label;
	return $this;
}

/**
* Get preSelect - Pre Select
* @return \Pimcore\Model\DataObject\ProductCategory[]
*/
public function getPreSelect (): array {
	$container = $this;
	$fd = $this->getDefinition()->getFieldDefinition("preSelect");
	$data = $fd->preGetData($container);
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set preSelect - Pre Select
* @param \Pimcore\Model\DataObject\ProductCategory[] $preSelect
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategoryMultiselect
*/
public function setPreSelect (?array $preSelect) {
	$fd = $this->getDefinition()->getFieldDefinition("preSelect");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getPreSelect();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $preSelect);
	if (!$isEqual) {
		$this->markFieldDirty("preSelect", true);
	}
	$this->preSelect = $fd->preSetData($this, $preSelect);
	return $this;
}

/**
* Get useAndCondition - Use And Condition
* @return bool|null
*/
public function getUseAndCondition (): ?bool {
	$data = $this->useAndCondition;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set useAndCondition - Use And Condition
* @param bool|null $useAndCondition
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategoryMultiselect
*/
public function setUseAndCondition (?bool $useAndCondition) {
	$fd = $this->getDefinition()->getFieldDefinition("useAndCondition");
	$this->useAndCondition = $useAndCondition;
	return $this;
}

/**
* Get includeParentCategories - Include SubCategories
* @return bool|null
*/
public function getIncludeParentCategories (): ?bool {
	$data = $this->includeParentCategories;
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set includeParentCategories - Include SubCategories
* @param bool|null $includeParentCategories
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategoryMultiselect
*/
public function setIncludeParentCategories (?bool $includeParentCategories) {
	$fd = $this->getDefinition()->getFieldDefinition("includeParentCategories");
	$this->includeParentCategories = $includeParentCategories;
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
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategoryMultiselect
*/
public function setScriptPath (?string $scriptPath) {
	$fd = $this->getDefinition()->getFieldDefinition("scriptPath");
	$this->scriptPath = $scriptPath;
	return $this;
}

/**
* Get availableCategories - Available Categories
* @return \Pimcore\Model\DataObject\ProductCategory[]
*/
public function getAvailableCategories (): array {
	$container = $this;
	$fd = $this->getDefinition()->getFieldDefinition("availableCategories");
	$data = $fd->preGetData($container);
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set availableCategories - Available Categories
* @param \Pimcore\Model\DataObject\ProductCategory[] $availableCategories
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategoryMultiselect
*/
public function setAvailableCategories (?array $availableCategories) {
	$fd = $this->getDefinition()->getFieldDefinition("availableCategories");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getAvailableCategories();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $availableCategories);
	if (!$isEqual) {
		$this->markFieldDirty("availableCategories", true);
	}
	$this->availableCategories = $fd->preSetData($this, $availableCategories);
	return $this;
}

}

