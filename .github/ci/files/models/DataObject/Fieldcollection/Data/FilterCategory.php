<?php 

/** 
Fields Summary: 
- label [input]
- preSelect [manyToOneRelation]
- rootCategory [manyToOneRelation]
- includeParentCategories [checkbox]
- scriptPath [input]
- availableCategories [manyToManyObjectRelation]
*/ 

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class FilterCategory extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\CategoryFilterDefinitionType {

protected $type = "FilterCategory";
protected $label;
protected $preSelect;
protected $rootCategory;
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
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategory
*/
public function setLabel (?string $label) {
	$fd = $this->getDefinition()->getFieldDefinition("label");
	$this->label = $label;
	return $this;
}

/**
* Get preSelect - Pre Select
* @return \\Pimcore\Model\DataObject\Category|null
*/
public function getPreSelect (): ?\Pimcore\Model\Element\AbstractElement {
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
* @param \Pimcore\Model\DataObject\Category $preSelect
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategory
*/
public function setPreSelect (?\Pimcore\Model\Element\AbstractElement $preSelect) {
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
* Get rootCategory - Root Category
* @return \\Pimcore\Model\DataObject\Category|null
*/
public function getRootCategory (): ?\Pimcore\Model\Element\AbstractElement {
	$container = $this;
	$fd = $this->getDefinition()->getFieldDefinition("rootCategory");
	$data = $fd->preGetData($container);
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		    return $data->getPlain();
	}
	 return $data;
}

/**
* Set rootCategory - Root Category
* @param \Pimcore\Model\DataObject\Category $rootCategory
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategory
*/
public function setRootCategory (?\Pimcore\Model\Element\AbstractElement $rootCategory) {
	$fd = $this->getDefinition()->getFieldDefinition("rootCategory");
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getRootCategory();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	$isEqual = $fd->isEqual($currentData, $rootCategory);
	if (!$isEqual) {
		$this->markFieldDirty("rootCategory", true);
	}
	$this->rootCategory = $fd->preSetData($this, $rootCategory);
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
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategory
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
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategory
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
* @return \Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategory
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

