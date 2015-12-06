## 1 - Basic Idea of the FilterService
The FilterService supports the developer of setting up e-commerce product listings with filters and layered navigation known from classic shop systems. Therefore it provides functionality to ...
- ... configure available filters and so set up the product listings in the frontend.
- ... generating the necessary filter conditions for the product index based on the filter type and user input. 
- ... printing out the configured filter with possible filter values etc. to the frontend. 

To provide these functionalities, a few components are necessary. The FilterService links all these components together and provides the developer a clean API for creating product listings in the frontend. 


## 2 - Setting up FilterTypes
Each product listing has different filters like dropdowns, multi selects, input fields, number ranges, etc. Each of these filter types require
- special configuration
- special presentation in the view
- special filter conditions for the product index

The FilterTypes are responsible for these three tasks. By adding filter type field collections to the filter definition objects (see next chapter) simple configuration of filters is possible for the user. The backend implementation of FilterTypes is done in php classes which implement the abstract class ```OnlineShop_Framework_FilterService_AbstractFilterType``` and responsible for creating the correct filter conditions based on the product index implementation and rendering the filter output for the frontend. Therefore ```OnlineShop_Framework_FilterService_AbstractFilterType``` expects the two methods ```getFilterFrontend()``` and ```addCondition()``` to be implemented. 


The configuration of the FilterTypes takes place in the OnlineShopConfig.xml
```xml
<!--
	assign backend implementations and views to filter type field collections

	helper = tool for pimcore backend controller to get possible group by values for a certain field
			 (used by object data type IndexFieldSelection, e.g. in filter definitions)
-->
<filtertypes helper="OnlineShop_Framework_FilterService_FilterGroupHelper">
	<FilterNumberRange class="OnlineShop_Framework_FilterService_NumberRange" script="/shop/filters/range.php"/>
	<FilterNumberRangeSelection class="OnlineShop_Framework_FilterService_NumberRangeSelection" script="/shop/filters/numberrange.php"/>
	<FilterSelect class="OnlineShop_Framework_FilterService_Select" script="/shop/filters/select.php"/>
	<FilterSelectFromMultiSelect class="OnlineShop_Framework_FilterService_SelectFromMultiSelect" script="/shop/filters/select.php"/>
	<FilterMultiSelect class="OnlineShop_Framework_FilterService_MultiSelect" script="/shop/filters/multiselect.php"/>
	<FilterMultiSelectFromMultiSelect class="OnlineShop_Framework_FilterService_MultiSelectFromMultiSelect" script="/shop/filters/multiselect.php"/>
	<FilterMultiRelation class="OnlineShop_Framework_FilterService_MultiSelectRelation" script="/shop/filters/multiselect-relation.php"/>
	<FilterCategory class="OnlineShop_Framework_FilterService_SelectCategory" script="/shop/filters/select_category.php"/>
	<FilterRelation class="OnlineShop_Framework_FilterService_SelectRelation" script="/shop/filters/object_relation.php"/>
</filtertypes>
```


**Configuration elements are:**
- FilterCategory: represents the field collection type for configuration in filter definitions
- class: backend implementation of the filter type
- script: default view script of the filter type, can be overwritten in den filter definitions

- Helper: is a helper implementation that gets available values for pre select settings in the filter definition objects based on the filtergroup setting in the index attributes definition. 


## 3 - Setting up FilterDefinitions
The configuration of available filters and the set up of product listings in the frontend takes place in FilterDefinition pimcore Objects. Configuration options are beside others: 
- General settings like page size etc. 
- PreConditions for pre filtering of products, e.g. only products of a certain category. These preconditions cannot be changed by the user in the frontend. 
- Filters that are visible in the frontend. 

![filterdefinition](images/filterdefinitions.png)


The configuration of preconditions and filters is done by field collection entries, whereby the field collection types are mapped to FilterTypes and their backend implementations in the OnlineShopConfig.xml (see previous chapter). The FilterDefinition class can be extended and modified to the needs of the system. 

FilterDefinition objects can be assigned to category objects to build up automatic category pages or to area bricks in pimcore documents to set up manual landing pages etc. 


## 4 - Putting it all together
Once FilterTypes and FilterDefinitions are set up, it is quite easy to put it all together and use the FilterService in controller actions. 
 
### Controller
For setting up the FilterService (including product-list with Zend_Paginator) within the controller use following snippet: 
```php 
<?php 

$params = $this->getAllParams();

$this->view->filterDefinitionObject = $filterDefinition;

// create product list
$products = \OnlineShop\Framework\Factory::getInstance()->getIndexService()->getProductListForCurrentTenant();

// create and init filter service
$filterService = \OnlineShop\Framework\Factory::getInstance()->getFilterService($this->view);

OnlineShop_Framework_FilterService_Helper::setupProductList($filterDefinition, $products, $params, $this->view, $filterService, true);
$this->view->filterService = $filterService;


// init pagination
$paginator = Zend_Paginator::factory( $products );
$paginator->setCurrentPageNumber( $this->getParam('page') );
$paginator->setItemCountPerPage( $filterDefinition->getPageLimit() );
$paginator->setPageRange(10);
$this->view->paginator = $paginator;

```

### View
For putting all filters to the frontend use following snippet. It is important, that this snippet is inside a form in order to get the parameter of changed filters delivered back to the controller. 

```php
<?php if($this->filterDefinitionObject->getFilters()): ?>
	<div class="widget">
	<?php foreach ($this->filterDefinitionObject->getFilters() as $filter): ?>
		<?= $this->filterService->getFilterFrontend($filter, $this->products, $this->currentFilter);?>
	<?php endforeach; ?><!-- end widget -->
	</div>
<?php endif; ?>
```
