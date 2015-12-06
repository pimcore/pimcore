## 1 - Basic Idea of the IndexService
The IndexService (in combination with the FilterService) provides functionality concerning indexing, listing, filtering and searching product. 
Heart of this component is the product index - a optimized storage of product data for all queries. Depending on the implementation, this product index is stored in a special mysql table, in elastic search or any other search provider (currently implemented are fact finder and findologic). 
These implementations can be configured in product index tenants (see later in this chapter), the default tenant always uses DefaultMysql as implementation.  

Advantages of product index: 
- it is completely independent of the pimcore object structure, only contains needed information and can pre-calculate complex data
- it can be optimized without any side effect for requirements considering filtering, listing and searching products


## 2 - Configuration of the Product Index
The configuration of the product index defines the content of the product index and takes place in the OnlineShopConfig.xml within the section ```<productindex>```:

```xml
<productindex>
	<!-- add columns for general fulltext search index of productlist - they must be part of the column configuration below -->
	<generalSearchColumns>
		<column name="name"/>
		<column name="seoname"/>
	</generalSearchColumns>

	<!-- column definition for product index -->
	<columns>
		<column name="name" type="varchar(255)" locale="en_GB" filtergroup="string"/>
		<column name="seoname" type="varchar(255)" filtergroup="string"/>

	 	<column name="features" interpreter="OnlineShop_Framework_IndexService_Interpreter_DefaultObjects" filtergroup="relation" />
		<column name="tentTentPegs" type="varchar(50)"
				getter="OnlineShop_Framework_IndexService_Getter_DefaultBrickGetter" filtergroup="string">
			<config brickfield="specificAttributes" bricktype="tentSpecifications" fieldname="tentPegs"/>
		</column>
	</columns>
</productindex>
```
### ```<generalSearchColumns>```
Defines attributes which should be considered in fulltext-search. All attributes must be defined within the <columns>-section

### ```<columns>```
Defines for all attributes if and how they should be indexed. Following information can be/ needs to be provided: 
- name (mandatory): name of attribute in product index, also used as fieldname if fieldname is not set. 
- type (mandatory for mysql): datatype for column in product index
- fieldname (optional): field name in product object, if different of name in index.
- locale (optional): used locale if field is a localized field. 
- getter (optional): special getter implementation for getting attribute value. Per default, the method getNAME() of the product is called. If this is not suitable, an alternative getter class can be defined which is responible for getting the value. This can be used for calculations, getting complex values (field collections, object bricks), etc. 
- interpreter (optional): by default all data is stored without any transformation in the product index. With an interpreter class, this data can be transformed and manipulated before storing. This can be used for only saving IDs of assets, normalization of data, special treatment of relations, etc. 
- hideInFieldlistDatatype (optional): hides column in fieldlist-datatyp dropdown (see FilterService for more information).
- filtergroup (optional): defines filtergroup for the fieldlist-datatype dropdown (see FilterService for more information).

Each attribute can have an additional ```<config>```-Element for additional configuration. 

#### Relations in product index
Relations are treated in a special way within the product index and also need to be filtered in a different way in the product list. 
In order to store relations correctly in the product index, relation attributes must have a interpreter defined, which implements the interface ```OnlineShop_Framework_IndexService_RelationInterpreter```. 


#### Selection of available getters:
- OnlineShop_Framework_IndexService_Getter_DefaultBrickGetter: Gets data of an object brick
needed configuration: brickfield (fieldname of object brick), bricktype (type of object brick), fieldname (fieldname of attribute in object brick)
- OnlineShop_Framework_IndexService_Getter_DefaultBrickGetterSequence: same as DefaultBrickGetter, but can use more than one source definition. Stores first found value in the product index. 
- OnlineShop_Framework_IndexService_Getter_DefaultBrickGetterSequenceToMultiselect: like DefaultBrickGetterSequence, but stores all found values as a multi select into the product index. 


#### Selection of available interpreters
- OnlineShop_Framework_IndexService_Interpreter_AssetId: stores only asset-id into product index. 
OnlineShop_Framework_IndexService_Interpreter_DefaultObjects: default interpreter to store object relations as relations to the product index.


> Depending on the product index implementation, the product index configuration can be different. see sample configurations for specific product index implemenations. 


## 3 - Tenant configuration
The ecommerce framework provides a two level tenant system for the product index: 

   1. Tenant: The first level of tenants are heavy-weight tenants. They allow multiple shop instances within one system. The shop instances are completely independent from each other and can contain complete different products, index attributes and even use different product index implementations.

   2. SubTenant: The second level of tenants are light-weight tenants, which exist within a shop instance. Light-weight tenants use the same product index with the same attributes as their parent shop, but can contain a subset of the products. So they are meant to be used for implementing different product assortments within one shop. 

One system can have multiple tenants (heavy- and light-weight). But too many tenants can have bad effects on the performance of saving products, since the product indices need to up updated on each save. 

By default the system always one heavy-weight tenant (= DefaultMysql), but the default tenant can be disabled. 

### Configuration of tenants
For setting up a tenant, following things are necessary: 
- **Implementation of a tenant config**
The tenant config class is the central configuration of the tenant, defines which products are available for the tenant and provides the connection to the used product index implementation. It needs to implement ``` OnlineShop_Framework_IndexService_Tenant_IConfig ```. For detailed information see insource documentation of the interface. Following implementations are provided by the framework and may be extended: 
  - ```OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysql```: provides a simple mysql implementation of the product index.
  - ```OnlineShop_Framework_IndexService_Tenant_Config_OptimizedMysql```: provides an optimized mysql implementation of the product index.
  - ```OnlineShop_Framework_IndexService_Tenant_Config_ElasticSearch```: provides a default elastic search implementation of the product index.
  - ```OnlineShop_Framework_IndexService_Tenant_Config_DefaultFactFinder```: provides a default fact finder implementation of the product index.


- **Configuring tenants within OnlineShopConfig.xml:** 
Each tenant has to be configured within OnlineShopConfig.xml by defining the tenant config class and index attributes. Depending on the product index implementation, additional configuration may be necessary. The configuration also can be outsourced in to an additional configuration file. For more information and samples see the OnlineShopConfig_sample.xml (TODO add link). 


### Setting current tenant for frontend
The Environment provides following methods to set the current tenant: 
```php
<?php
    /**
     * sets current assortment tenant which is used for indexing and product lists
     *
     * @param $tenant string
     * @return mixed
     */
    public function setCurrentAssortmentTenant($tenant);

    /**
     * gets current assortment tenant which is used for indexing and product lists
     *
     * @return string
     */
    public function getCurrentAssortmentTenant();

    /**
     * sets current assortment sub tenant which is used for indexing and product lists
     *
     * @param $subTenant string
     * @return mixed
     */
    public function setCurrentAssortmentSubTenant($subTenant);

    /**
     * gets current assortment sub tenant which is used for indexing and product lists
     *
     * @return string
     */
    public function getCurrentAssortmentSubTenant();
```

The current tenants have to be set in the application controllers, e.g. after the login of a specific customer. The index service provides the corresponding product list implementation based on the current tenant. 


## 4 - Data architecture and indexing
Depending on the product index implementation, there are two different product index data architectures and ways for indexing. For indexing itself the helper class ```OnlineShop_Framework_IndexService_Tool_IndexUpdater``` can be used. 

### Simple Mysql Architecture
- In the simple architecture, object data is transferred directly to the product index. 
- After every update of a pimcore object, the changes are directly written into the product index. Updates of dependent objects (like child objects) are not transferred into the index automatically. 

- For manually updating the whole index use following command: 

```php
<?php
OnlineShop_Framework_IndexService_Tool_IndexUpdater::updateIndex("Object_Product_List");
```

- Only used for OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysql


![productindex-simple](images/productindex-simple.png)




### Optimized Architecture
- In the optimized architecture, object data is transferred **not** directly to the product index. 
- In this case a so called store table is between the pimcore objects and the product index. This store table enables to ...
   - ... update the product index only if index relevant data has changed. Therefore the load on the index itself is reduced and unnecessary write operations are prevented. 
   - ... update the product index asynchronously and therefore update also dependent elements of an updated pimcore objects without impact on save performance. 
   - ... rebuild the whole product index out of the store table much faster since no direct interaction with pimcore objects is needed. 

- After every update of a pimcore object, the changes are written into the index store table and all child objects of the updated object are added to the preparation queue. 
- For manually update all pimcore objects to the index store use following command: 

```php
<?php
OnlineShop_Framework_IndexService_Tool_IndexUpdater::updateIndex("Object_Product_List");
```

- For process the preparation queue and update pimcore objects to the index store use following command. This command should be executed periodically (e.g. all 10 mins) 

```php
<?php
OnlineShop_Framework_IndexService_Tool_IndexUpdater::processPreparationQueue();
```

- To update the product index based on changes stored in the store table use the following command. This command should be executed periodically (e.g. all 10 mins)  

```php
<?php
OnlineShop_Framework_IndexService_Tool_IndexUpdater::processUpdateIndexQueue();
```

- Used for optimized mysql, elastic search, ...


![productindex-optimized](images/productindex-optimized.png)


## 5 - Usage of product list
The API for getting (and filtering, ...) products out of the product index are so called ProductLists. The all implement the interface ```OnlineShop_Framework_IProductList``` and need to be product index implementation specific. Detailed method documentation is available in in-source documentation. 
For getting a ProdutList instance suitable for the product index implementation and filter for products see following code: 
```php 
<?php 
$list = \OnlineShop\Framework\Factory::getInstance()->getIndexService()->getProductListForCurrentTenant();
$list->addCondition("name = 'testproduct'", 'name');
$list->addRelationCondition('category', "dest IN (1024,1025,1026)");
$list->setOrder("ASC");
$list->setOrderKey('name');
$list->load();
```

All used attributes need to be in the product index. The product list implements all iterator and paginator interfaces and can be used in foreach loops and in combination with Zend_Paginators. 


If pimcore log level is set at least to INFO, all created index queries are logged into online-shop-sql.log in the website/var/log folder. 