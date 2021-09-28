<?php

/**
* Inheritance: yes
* Variants: no


Fields Summary:
- pageLimit [numeric]
- defaultOrderByInheritance [select]
- defaultOrderBy [fieldcollections]
- orderByAsc [indexFieldSelectionField]
- orderByDesc [indexFieldSelectionField]
- ajaxReload [checkbox]
- infiniteScroll [checkbox]
- limitOnFirstLoad [numeric]
- conditionsInheritance [select]
- conditions [fieldcollections]
- filtersInheritance [select]
- filters [fieldcollections]
- crossSellingCategory [manyToOneRelation]
- similarityFieldsInheritance [select]
- similarityFields [fieldcollections]
*/

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

/**
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing getList()
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByPageLimit($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByDefaultOrderByInheritance($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByOrderByAsc($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByOrderByDesc($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByAjaxReload($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByInfiniteScroll($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByLimitOnFirstLoad($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByConditionsInheritance($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByFiltersInheritance($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByCrossSellingCategory($value, $limit = 0, $offset = 0)
* @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getBySimilarityFieldsInheritance($value, $limit = 0, $offset = 0)
*/

class FilterDefinition extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinition
{
protected $o_classId = "EF_FD";
protected $o_className = "FilterDefinition";
protected $pageLimit;
protected $defaultOrderByInheritance;
protected $defaultOrderBy;
protected $orderByAsc;
protected $orderByDesc;
protected $ajaxReload;
protected $infiniteScroll;
protected $limitOnFirstLoad;
protected $conditionsInheritance;
protected $conditions;
protected $filtersInheritance;
protected $filters;
protected $crossSellingCategory;
protected $similarityFieldsInheritance;
protected $similarityFields;


/**
* @param array $values
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get pageLimit - Results per Page
* @return float|null
*/
public function getPageLimit(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("pageLimit");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->pageLimit;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("pageLimit")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("pageLimit");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set pageLimit - Results per Page
* @param float|null $pageLimit
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setPageLimit(?float $pageLimit)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("pageLimit");
	$this->pageLimit = $fd->preSetData($this, $pageLimit);

	return $this;
}

/**
* Get defaultOrderByInheritance - inherit Default OrderBy
* @return string|null
*/
public function getDefaultOrderByInheritance(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("defaultOrderByInheritance");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->defaultOrderByInheritance;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("defaultOrderByInheritance")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("defaultOrderByInheritance");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set defaultOrderByInheritance - inherit Default OrderBy
* @param string|null $defaultOrderByInheritance
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setDefaultOrderByInheritance(?string $defaultOrderByInheritance)
{
	$this->defaultOrderByInheritance = $defaultOrderByInheritance;

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getDefaultOrderBy()
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("defaultOrderBy");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("defaultOrderBy");
	$data = $fd->preGetData($this);

	return $data;
}

/**
* Set defaultOrderBy - Default OrderBy
* @param \Pimcore\Model\DataObject\Fieldcollection|null $defaultOrderBy
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setDefaultOrderBy(?\Pimcore\Model\DataObject\Fieldcollection $defaultOrderBy)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("defaultOrderBy");
	$this->defaultOrderBy = $fd->preSetData($this, $defaultOrderBy);

	return $this;
}

/**
* Get orderByAsc - OrderBy
* @return string|null
*/
public function getOrderByAsc(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("orderByAsc");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->orderByAsc;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("orderByAsc")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("orderByAsc");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set orderByAsc - OrderBy
* @param string|null $orderByAsc
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setOrderByAsc(?string $orderByAsc)
{
	$this->orderByAsc = $orderByAsc;

	return $this;
}

/**
* Get orderByDesc - OrderBy Descending
* @return string|null
*/
public function getOrderByDesc(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("orderByDesc");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->orderByDesc;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("orderByDesc")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("orderByDesc");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set orderByDesc - OrderBy Descending
* @param string|null $orderByDesc
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setOrderByDesc(?string $orderByDesc)
{
	$this->orderByDesc = $orderByDesc;

	return $this;
}

/**
* Get ajaxReload - ajaxReload
* @return bool|null
*/
public function getAjaxReload(): ?bool
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("ajaxReload");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->ajaxReload;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("ajaxReload")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("ajaxReload");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set ajaxReload - ajaxReload
* @param bool|null $ajaxReload
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setAjaxReload(?bool $ajaxReload)
{
	$this->ajaxReload = $ajaxReload;

	return $this;
}

/**
* Get infiniteScroll - Infinite Scroll
* @return bool|null
*/
public function getInfiniteScroll(): ?bool
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("infiniteScroll");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->infiniteScroll;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("infiniteScroll")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("infiniteScroll");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set infiniteScroll - Infinite Scroll
* @param bool|null $infiniteScroll
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setInfiniteScroll(?bool $infiniteScroll)
{
	$this->infiniteScroll = $infiniteScroll;

	return $this;
}

/**
* Get limitOnFirstLoad - Limit on First Load
* @return float|null
*/
public function getLimitOnFirstLoad(): ?float
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("limitOnFirstLoad");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->limitOnFirstLoad;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("limitOnFirstLoad")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("limitOnFirstLoad");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set limitOnFirstLoad - Limit on First Load
* @param float|null $limitOnFirstLoad
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setLimitOnFirstLoad(?float $limitOnFirstLoad)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric $fd */
	$fd = $this->getClass()->getFieldDefinition("limitOnFirstLoad");
	$this->limitOnFirstLoad = $fd->preSetData($this, $limitOnFirstLoad);

	return $this;
}

/**
* Get conditionsInheritance - inherit Conditions
* @return string|null
*/
public function getConditionsInheritance(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("conditionsInheritance");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->conditionsInheritance;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("conditionsInheritance")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("conditionsInheritance");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set conditionsInheritance - inherit Conditions
* @param string|null $conditionsInheritance
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setConditionsInheritance(?string $conditionsInheritance)
{
	$this->conditionsInheritance = $conditionsInheritance;

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getConditions()
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("conditions");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("conditions");
	$data = $fd->preGetData($this);

	return $data;
}

/**
* Set conditions - Conditions
* @param \Pimcore\Model\DataObject\Fieldcollection|null $conditions
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setConditions(?\Pimcore\Model\DataObject\Fieldcollection $conditions)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("conditions");
	$this->conditions = $fd->preSetData($this, $conditions);

	return $this;
}

/**
* Get filtersInheritance - inherit Filters
* @return string|null
*/
public function getFiltersInheritance(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("filtersInheritance");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->filtersInheritance;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("filtersInheritance")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("filtersInheritance");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set filtersInheritance - inherit Filters
* @param string|null $filtersInheritance
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setFiltersInheritance(?string $filtersInheritance)
{
	$this->filtersInheritance = $filtersInheritance;

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getFilters()
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("filters");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("filters");
	$data = $fd->preGetData($this);

	return $data;
}

/**
* Set filters - Filters
* @param \Pimcore\Model\DataObject\Fieldcollection|null $filters
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setFilters(?\Pimcore\Model\DataObject\Fieldcollection $filters)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("filters");
	$this->filters = $fd->preSetData($this, $filters);

	return $this;
}

/**
* Get crossSellingCategory - Base category for recommendations
* @return \Pimcore\Model\DataObject\ProductCategory|null
*/
public function getCrossSellingCategory(): ?\Pimcore\Model\Element\AbstractElement
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("crossSellingCategory");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("crossSellingCategory");
	$data = $fd->preGetData($this);

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("crossSellingCategory")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("crossSellingCategory");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set crossSellingCategory - Base category for recommendations
* @param \Pimcore\Model\DataObject\ProductCategory $crossSellingCategory
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setCrossSellingCategory(?\Pimcore\Model\Element\AbstractElement $crossSellingCategory)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
	$fd = $this->getClass()->getFieldDefinition("crossSellingCategory");
	$inheritValues = self::getGetInheritedValues();
	self::setGetInheritedValues(false);
	$hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
	$currentData = $this->getCrossSellingCategory();
	\Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
	self::setGetInheritedValues($inheritValues);
	$isEqual = $fd->isEqual($currentData, $crossSellingCategory);
	if (!$isEqual) {
		$this->markFieldDirty("crossSellingCategory", true);
	}
	$this->crossSellingCategory = $fd->preSetData($this, $crossSellingCategory);

	return $this;
}

/**
* Get similarityFieldsInheritance - inherit SimilarityFields
* @return string|null
*/
public function getSimilarityFieldsInheritance(): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("similarityFieldsInheritance");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->similarityFieldsInheritance;

	if (\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("similarityFieldsInheritance")->isEmpty($data)) {
		try {
			return $this->getValueFromParent("similarityFieldsInheritance");
		} catch (InheritanceParentNotFoundException $e) {
			// no data from parent available, continue ...
		}
	}

	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

/**
* Set similarityFieldsInheritance - inherit SimilarityFields
* @param string|null $similarityFieldsInheritance
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setSimilarityFieldsInheritance(?string $similarityFieldsInheritance)
{
	$this->similarityFieldsInheritance = $similarityFieldsInheritance;

	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection|null
*/
public function getSimilarityFields()
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("similarityFields");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("similarityFields");
	$data = $fd->preGetData($this);

	return $data;
}

/**
* Set similarityFields - SimilarityFields
* @param \Pimcore\Model\DataObject\Fieldcollection|null $similarityFields
* @return \Pimcore\Model\DataObject\FilterDefinition
*/
public function setSimilarityFields(?\Pimcore\Model\DataObject\Fieldcollection $similarityFields)
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("similarityFields");
	$this->similarityFields = $fd->preSetData($this, $similarityFields);

	return $this;
}

}

