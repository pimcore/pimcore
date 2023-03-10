<?php

/**
 * Inheritance: yes
 * Variants: no
 *
 * Fields Summary:
 * - pageLimit [numeric]
 * - defaultOrderByInheritance [select]
 * - defaultOrderBy [fieldcollections]
 * - orderByAsc [textarea]
 * - orderByDesc [textarea]
 * - ajaxReload [checkbox]
 * - infiniteScroll [checkbox]
 * - limitOnFirstLoad [numeric]
 * - conditionsInheritance [select]
 * - conditions [fieldcollections]
 * - filtersInheritance [select]
 * - filters [fieldcollections]
 * - crossSellingCategory [manyToOneRelation]
 * - similarityFieldsInheritance [select]
 * - similarityFields [fieldcollections]
 */

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

/**
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing getList(array $config = [])
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByPageLimit(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByDefaultOrderByInheritance(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByOrderByAsc(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByOrderByDesc(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByAjaxReload(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByInfiniteScroll(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByLimitOnFirstLoad(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByConditionsInheritance(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByFiltersInheritance(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getByCrossSellingCategory(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 * @method static \Pimcore\Model\DataObject\FilterDefinition\Listing|\Pimcore\Model\DataObject\FilterDefinition|null getBySimilarityFieldsInheritance(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)
 */

class FilterDefinition extends \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinition
{
    public const FIELD_PAGE_LIMIT = 'pageLimit';
    public const FIELD_DEFAULT_ORDER_BY_INHERITANCE = 'defaultOrderByInheritance';
    public const FIELD_DEFAULT_ORDER_BY = 'defaultOrderBy';
    public const FIELD_ORDER_BY_ASC = 'orderByAsc';
    public const FIELD_ORDER_BY_DESC = 'orderByDesc';
    public const FIELD_AJAX_RELOAD = 'ajaxReload';
    public const FIELD_INFINITE_SCROLL = 'infiniteScroll';
    public const FIELD_LIMIT_ON_FIRST_LOAD = 'limitOnFirstLoad';
    public const FIELD_CONDITIONS_INHERITANCE = 'conditionsInheritance';
    public const FIELD_CONDITIONS = 'conditions';
    public const FIELD_FILTERS_INHERITANCE = 'filtersInheritance';
    public const FIELD_FILTERS = 'filters';
    public const FIELD_CROSS_SELLING_CATEGORY = 'crossSellingCategory';
    public const FIELD_SIMILARITY_FIELDS_INHERITANCE = 'similarityFieldsInheritance';
    public const FIELD_SIMILARITY_FIELDS = 'similarityFields';

    protected $classId = "EF_FD";
    protected $className = "FilterDefinition";
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
     * @return static
     */
    public static function create(array $values = []): static
    {
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
     * @return $this
     */
    public function setPageLimit(?float $pageLimit): static
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
     * @return $this
     */
    public function setDefaultOrderByInheritance(?string $defaultOrderByInheritance): static
    {
        $this->defaultOrderByInheritance = $defaultOrderByInheritance;

        return $this;
    }

    /**
     * @return \Pimcore\Model\DataObject\Fieldcollection|null
     */
    public function getDefaultOrderBy(): ?\Pimcore\Model\DataObject\Fieldcollection
    {
        if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
            $preValue = $this->preGetValue("defaultOrderBy");
            if ($preValue !== null) {
                return $preValue;
            }
        }

        $data = $this->getClass()->getFieldDefinition("defaultOrderBy")->preGetData($this);
        return $data;
    }

    /**
     * Set defaultOrderBy - Default OrderBy
     * @param \Pimcore\Model\DataObject\Fieldcollection|null $defaultOrderBy
     * @return $this
     */
    public function setDefaultOrderBy(?\Pimcore\Model\DataObject\Fieldcollection $defaultOrderBy): static
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
     * @return $this
     */
    public function setOrderByAsc(?string $orderByAsc): static
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
     * @return $this
     */
    public function setOrderByDesc(?string $orderByDesc): static
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
     * @return $this
     */
    public function setAjaxReload(?bool $ajaxReload): static
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
     * @return $this
     */
    public function setInfiniteScroll(?bool $infiniteScroll): static
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
     * @return $this
     */
    public function setLimitOnFirstLoad(?float $limitOnFirstLoad): static
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
     * @return $this
     */
    public function setConditionsInheritance(?string $conditionsInheritance): static
    {
        $this->conditionsInheritance = $conditionsInheritance;

        return $this;
    }

    /**
     * @return \Pimcore\Model\DataObject\Fieldcollection|null
     */
    public function getConditions(): ?\Pimcore\Model\DataObject\Fieldcollection
    {
        if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
            $preValue = $this->preGetValue("conditions");
            if ($preValue !== null) {
                return $preValue;
            }
        }

        $data = $this->getClass()->getFieldDefinition("conditions")->preGetData($this);
        return $data;
    }

    /**
     * Set conditions - Conditions
     * @param \Pimcore\Model\DataObject\Fieldcollection|null $conditions
     * @return $this
     */
    public function setConditions(?\Pimcore\Model\DataObject\Fieldcollection $conditions): static
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
     * @return $this
     */
    public function setFiltersInheritance(?string $filtersInheritance): static
    {
        $this->filtersInheritance = $filtersInheritance;

        return $this;
    }

    /**
     * @return \Pimcore\Model\DataObject\Fieldcollection|null
     */
    public function getFilters(): ?\Pimcore\Model\DataObject\Fieldcollection
    {
        if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
            $preValue = $this->preGetValue("filters");
            if ($preValue !== null) {
                return $preValue;
            }
        }

        $data = $this->getClass()->getFieldDefinition("filters")->preGetData($this);
        return $data;
    }

    /**
     * Set filters - Filters
     * @param \Pimcore\Model\DataObject\Fieldcollection|null $filters
     * @return $this
     */
    public function setFilters(?\Pimcore\Model\DataObject\Fieldcollection $filters): static
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

        $data = $this->getClass()->getFieldDefinition("crossSellingCategory")->preGetData($this);

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
     * @param \Pimcore\Model\DataObject\ProductCategory|null $crossSellingCategory
     * @return $this
     */
    public function setCrossSellingCategory(?\Pimcore\Model\Element\AbstractElement $crossSellingCategory): static
    {
        /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $fd */
        $fd = $this->getClass()->getFieldDefinition("crossSellingCategory");
        $hideUnpublished = \Pimcore\Model\DataObject\Concrete::getHideUnpublished();
        \Pimcore\Model\DataObject\Concrete::setHideUnpublished(false);
        $currentData = \Pimcore\Model\DataObject\Service::useInheritedValues(false, function() {
            return $this->getCrossSellingCategory();
        });
        \Pimcore\Model\DataObject\Concrete::setHideUnpublished($hideUnpublished);
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
     * @return $this
     */
    public function setSimilarityFieldsInheritance(?string $similarityFieldsInheritance): static
    {
        $this->similarityFieldsInheritance = $similarityFieldsInheritance;

        return $this;
    }

    /**
     * @return \Pimcore\Model\DataObject\Fieldcollection|null
     */
    public function getSimilarityFields(): ?\Pimcore\Model\DataObject\Fieldcollection
    {
        if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
            $preValue = $this->preGetValue("similarityFields");
            if ($preValue !== null) {
                return $preValue;
            }
        }

        $data = $this->getClass()->getFieldDefinition("similarityFields")->preGetData($this);
        return $data;
    }

    /**
     * Set similarityFields - SimilarityFields
     * @param \Pimcore\Model\DataObject\Fieldcollection|null $similarityFields
     * @return $this
     */
    public function setSimilarityFields(?\Pimcore\Model\DataObject\Fieldcollection $similarityFields): static
    {
        /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
        $fd = $this->getClass()->getFieldDefinition("similarityFields");
        $this->similarityFields = $fd->preSetData($this, $similarityFields);
        return $this;
    }

}

