## Basic Idea of the *Filter Service*
The *Filter Service* supports the developers in setting up E-Commerce product listings with filters and layered navigation 
known from classic shop systems. Therefore it provides functionality to ...
- ... configure available filters and to set up the product listings in the frontend.
- ... generating the necessary filter conditions for the Product Index based on the filter type and user input. 
- ... printing out the configured filter with possible filter values etc. to the frontend. 

To provide this functionality, a few components are necessary. The *Filter Service* links all these components together 
and provides the developer a clean API for creating product listings in the frontend. 


## 1 - Setting up Filter Types
Each product listing has different filters like drop downs, multi selects, input fields, number ranges, etc. Each of 
these Filter Types require
- special configuration
- special presentation in the view
- special filter conditions for the Product Index

The Filter Types are responsible for these three tasks and can be used for composing filter definition objects (see next chapter).

The backend implementation of Filter Types takes place in php classes which extend the abstract class 
`\Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType` and are responsible for creating 
the correct filter conditions based on the Product Index implementation and rendering the filter output to the frontend. 

Therefore `\Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType` expects the two methods 
`getFilterFrontend()` and `addCondition()` to be implemented. 

Each Filter Type needs to be defined as service and registered on the `pimcore_ecommerce_framework.filter_service` configuration.
The framework already defines a number of core filter types in [filter_service_filter_types.yml](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/Resources/config/filter_service_filter_types.yml).

> FilterTypes are dependent of the used index backend. You need to use different FilterTypes when using MySQL or ElasticSearch etc. 
> Pimcore ships with FilterTypes implementations for all supported index backends. For details see for example 
> [Elastic Search Config](03_Elastic_Search/README.md).  
 
```yaml
pimcore_ecommerce_framework:
    filter_service:
        tenants:
            default:
                # assign backend implementations and views to filter type field collections
                filter_types:
                
                    # filter type for the FilterNumberRange field collection
                    FilterNumberRange:
                        # service id for filter type implementation
                        filter_type_id: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\NumberRange
                        # default template for filter, can be overwritten in filter definition
                        template: ':Shop/filters:range.html.php'

                    FilterNumberRangeSelection:
                        filter_type_id: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\NumberRangeSelection
                        template: ':Shop/filters:numberrange.html.php'

                    FilterSelect:
                        filter_type_id: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Select
                        template: ':Shop/filters:select.html.php'

                    FilterSelectFromMultiSelect:
                        filter_type_id: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\SelectFromMultiSelect
                        template: ':Shop/filters:select.html.php'

                    FilterMultiSelect:
                        filter_type_id: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelect
                        template: ':Shop/filters:multiselect.html.php'

                    FilterMultiSelectFromMultiSelect:
                        filter_type_id: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelectFromMultiSelect
                        template: ':Shop/filters:multiselect.html.php'

                    FilterMultiRelation:
                        filter_type_id: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelectRelation
                        template: ':Shop/filters:multiselect-relation.html.php'

                    FilterCategory:
                        filter_type_id: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\SelectCategory
                        template: ':Shop/filters:select_category.html.php'

                    FilterRelation:
                        filter_type_id: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\SelectRelation
                        template: ':Shop/filters:object_relation.html.php'
```

Optionally, you can configure a custom filter service which relies on a custom helper implementation. The helper is a tool
for the Pimcore backend controller to get possible group by values for a certain field (used by object data type IndexFieldSelection,
e.g. in filter definitions). First, create your filter service definition:

```yaml
services:
    app.custom_filter_service:
        class: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterService
        arguments:
            - '@Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterGroupHelper'
```

You can now use the service definition in the `filter_service` config:

```yaml
pimcore_ecommerce_framework:
    filter_service:
        tenants:
            default:
                service_id: app.custom_filter_service
                filter_types:
                    # ...
```

**Configuration elements are:**
- `FilterCategory`: The key of the array represents the field collection type (= name of field collection) for configuration 
  in filter definition objects (see next chapter). 
- `class`: Backend implementation of the filter type. 
- `script`: Default view script of the filter type, can be overwritten in the filter definition objects. 
  You can find some script filter examples in the Demo [here](https://github.com/pimcore/demo/tree/master/app/Resources/views/product/filters). 


- `Helper`: Is a helper implementation that gets available values for pre select settings in the filter definition objects 
  based on the [filter group](../05_Index_Service/01_Product_Index_Configuration/README.md) setting in the index attributes 
  definition. 



## 2 - Setting up FilterDefinition Objects
The configuration of available filters and the set up of product listings in the frontend takes place in FilterDefinition 
Pimcore objects. Configuration options are beside others: 
- General settings like `page size` etc. 
- `PreConditions` for pre filtering of products, e.g. only products of a certain category. These preconditions cannot be 
changed by the user in the frontend. 
- `Filters` that are visible in the frontend. 


![FilterDefinition](../../img/filter-definitions.jpg)


The configuration of preconditions and filters is done by field collection entries, whereby the field collection types 
are mapped to Filter Types and their backend implementations in the `pimcore_ecommerce_framework.filter_service` config 
section (see previous chapter). 
The Filter Definition class can be extended and modified to custom needs of the system. 

Filter Definition objects can be assigned to category objects to build up automatic category pages or to area bricks in 
Pimcore documents to set up manual landing pages etc. 
Both is demonstrated at our [Demo](https://demo.pimcore.fun) and also available as 
[source code](https://github.com/pimcore/demo). 

In case that a filter contains relational objects (`FilterMultiRelation`, `FilterRelation`, etc.), 
the `getName()` method of the object is used to render the text in pre-select lists and filters. 
Implement the `getNameForFilterDefinition()` method in your data objects to show customized (HTML) texts, including icons. 

## 3 - Putting it all together
Once Filter Types and Filter Definitions are set up, it is quite easy to put it all together and use the *Filter Service* 
in controller actions. 
 
### Controller
For setting up the *Filter Service* (including Product List with `Zend\Paginator`) within the controller use following 
sample: 

```php
<?php 
$ecommerceFactory = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance();

$viewModel = new ViewModel();
$params = array_merge($request->query->all(), $request->attributes->all());

$indexService = $ecommerceFactory->getIndexService();
$productListing = $indexService->getProductListForCurrentTenant();
$viewModel->productListing = $productListing;

//get filter definition from document, category or global settings
$filterDefinition = //TODO ...get from somewhere;

// create and init filter service
$filterService = $ecommerceFactory->getFilterService();
\Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\Helper::setupProductList($filterDefinition, $productListing, $params, $viewModel, $filterService, true);
$viewModel->filterService = $filterService;
$viewModel->filterDefinition = $filterDefinition;

// init pagination
$paginator = new Paginator($productListing);
$paginator->setCurrentPageNumber($request->get('page'));
$paginator->setItemCountPerPage(18);
$paginator->setPageRange(5);
$viewModel->results = $paginator;
$viewModel->paginationVariables = $paginator->getPages('Sliding');

return $viewModel->getAllParameters();
```

For a sample of a controller see our demo [here](https://github.com/pimcore/demo/blob/master/src/AppBundle/Controller/ProductController.php#L118). 

### View
For putting all filters to the frontend use following sample. It is important that this sample is inside a form in order 
to get the parameter of changed filters delivered back to the controller. 

<div class="code-section">

```php
<?php if($this->filterDefinitionObject->getFilters()): ?>
	<div class="widget">
	<?php foreach ($this->filterDefinitionObject->getFilters() as $filter): ?>
		<?= $this->filterService->getFilterFrontend($filter, $this->products, $this->currentFilter);?>
	<?php endforeach; ?><!-- end widget -->
	</div>
<?php endif; ?>
```

```twig
{% if(filterDefinition.filters|length > 0) %}
    {% for filter in filterDefinition.filters %}
        {% set filterMarkup = filterService.filterFrontend(filter, productListing, currentFilter) %}
        {{ filterMarkup | raw  }}
    {% endfor %}
{% endif %}
```

</div>
