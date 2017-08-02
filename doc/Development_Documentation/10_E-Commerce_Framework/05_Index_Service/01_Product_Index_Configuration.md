# Product Index Configuration

The configuration of the *Product Index* defines the content of the *Product Index* (which attributes are extracted how
and stored where) and takes place in the `pimcore_ecommerce_framework` configuration within the section `product_index`.
The product index can define multiple tenants (see [Assortment Tenant Configuration](./03_Assortment_Tenant_Configuration.md))
which can be configured individually. Each tenant defines a set of attributes to index/search and a configuration and worker
class responsible for storing and managing the index. 


```yaml
pimcore_ecommerce_framework:
    product_index:
        # product index defines multiple tenants which can be disabled individually
        # a tenant can define placeholders which will be replaced 
        tenants:
            _defaults:
                attributes:
                    name:
                        type: varchar(255)
                        filter_group: string
                        locale: '%%locale%%'
        
            default:
                enabled: false
                
                # service ID of the desired configuration
                config_id: Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysql
                
                # optional set of options passed to the config service - available options vary by config
                # implementation 
                config_options:
                    foo: bar
                
                # optional - id of the worker handling the index. worker and config must be matching as a worker
                # is only able to accept a certain config implementation (e.g. an elasticsearch worker can only operate
                # on an elasticsearch config). as long as you don't implement a custom worker you can omit the wotker_id
                # as the system is able to automatically resolve the worker for a given config 
                worker_id: Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultMysql
                
                # placeholders will be replaced with the value defined here
                # you can use placeholders to define multiple tenant configuration
                # via _defaults which set the locale based on a placeholder
                # 
                # in this example, the "default" tenant will have the attributes name
                # and seoname and use "de_AT" as locale for the "name" attribute
                #
                # please make sure to use double %% or another char as {} to denote placeholders
                # as "%locale%" would be replaced by the "%locale%" container parameter instead
                # of the placeholder                 
                placeholders:
                    '%%locale%%': de_AT
                
                # add columns for general fulltext search index of productlist
                # they must be part of the attributes configuration below
                search_attributes:
                    - name
                    - seoname
                    
                # defines search index attributes and how they're gathered
                # as the _defaults entry already defines a name attribute, we'll end
                # up with name and seoname here
                attributes:
                    seoname:
                        type: varchar(255)
                        filter_group: string
                        
            example_tenant:
                attributes:                          
                    rucksacksLoad:
                        type: 'double'              
                        filter_group: double
                                  
                        # a getter is a specific service responsible for getting the value from an object
                        # this must be defined as service - as we use the class name as service id you can
                        # just use the fully qualified class name. you can use your own getters if you define
                        # them as service first
                        getter_id: Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\DefaultBrickGetter
                        
                        # options passed to the getter. available options vary by getter implementation - see
                        # getter for available options
                        getter_options:
                            brickfield: specificAttributes
                            bricktype: rucksackSpecs
                            fieldname: load
                        
                        # an interpreter can further transform the value retrieved by the getter. same logic applies as
                        # for getters - interpreter must be defined as service    
                        interpreter_id: Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Numeric
                        
                        # interpreter can define options which are passed to the interpreter when interpreting the value
                        interpreter_options: {}
```

### `search_attributes`

Defines attributes which should be considered in fulltext-search. All attributes must be defined within the 
`attributes`-section. 

### `attributes`

Defines for all attributes if and how they should be indexed. Each attribute is referenced with an unique name (e.g. `seoname`
or `rucksacksLoad` in the example above) which is the name of the attribute in the product index. The following configuration
options are available: 
 
- `type` (mandatory for mysql): Datatype for column in Product Index. 
- `field_name` (optional): Field name in product object, needed if it is different than `name` in index. Defaults to `name`
- `locale` (optional): Used locale for data retrieving if field is in a localized field. 
- `getter_id` (optional): Service ID of a special getter implementation for getting attribute value. Per default, the
   method `get<NAME>()` of the product object is called. If this is not suitable, an alternative getter class can be defined
   which is responsible for getting the value. This can be used for calculations, getting complex values (field collections,
   object bricks), etc. Getter implementations need to implement `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\IGetter`
   interface and be defined as service. Best practice is to use the fully qualified class name as service ID and to reference
   the class name in the configuration.
- `getter_options` (optional): options passed to the getter when resolving a value. Available options vary by getter implementation.
- `interpreter_id` (optional): By default all data is stored without any transformation in the Product Index. With an 
   interpreter, this data can be transformed and manipulated before storing. This can be used for only saving IDs 
   of assets, normalization of data, special treatment of relations, etc. Interpreter implementations need to implement
   `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Interpreter` interface. The same service ID best practices
   as for getters apply to interpreters.
- `interpreter_options` (optional): options passed to the interpreter. Available options vary by interpreter implementation.
- `hide_in_fieldlist_datatype` (optional): Hides column in FieldList drop down (see [FilterService](../07_Filter_Service.md) 
   for more information).
- `filter_group` (optional): Defines filter group for the FieldList drop down (see [FilterService](../07_Filter_Service.md) 
   for more information).

#### Relations in *Product Index*
Relations are stored in a special way in *Product Index* and also need to be filtered in a different way in the 
[Product List](./07_Product_List.md).
 
In order to store relations correctly in the Product Index, relation attributes must have an interpreter defined which 
implements the interface `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IRelationInterpreter`. 


#### Selection of available Getters:
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\DefaultBrickGetter`: Gets data from an Object Brick. 
  Needed configuration: 
  - `brickfield`: Field name of Object Brick, e.g. `bricks`. 
  - `bricktype`: Type of Object Brick, e.g. `TentBrick`. 
  - `fieldname`: Field name of attribute in Object Brick, e.g. `height`. 
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\DefaultBrickGetterSequence`: Same as `DefaultBrickGetter`, 
   but can use more than one source definition and stores first found value in *Product Index*. 
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\DefaultBrickGetterSequenceToMultiselect`: Like 
  `DefaultBrickGetterSequence`, but stores all found values as a multi select in the *Product Index*. 
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\TagsGetter`: Gets [Tags](../../18_Tools_and_Features/09_Tags.md) 
  of product object and returns them as array. 


#### Selection of available Interpreters:
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\AssetId`: Stores only asset id into *Product Index*.
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultObjects`: Default interpreter to store object 
  relations as relations into the *Product Index*.
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultRelations`: Same as `DefaultObjects` but also 
  for other relations to Assets and Documents. Also can deal with `ObjectMetadata`. 
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultStructuredTable`: Interpreter for structured
  table data type. Expects following configuration options: 
     - `row`
     - `column`
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IdList`: Returns id list of given values as CSV list. 
 If configuration option `multiSelectEncoded` is set, it returns id list encoded as multi select (relevant for filtering 
 in Product List). 
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Numeric`: Returns `floatval` of given value. 
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\ObjectId`: Returns id of given object. 
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\ObjectIdSum`: Calculates sum if ids of given objects. 
Could be used for similarity calculation. 
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\ObjectValue`: Get value from an related object. 
Expects following configuration options: 
     - `['target']['fieldname']`: Field name of value to get. Is used for getter generation which is called on given object. 
     - `['target']['locale']`: Locale is optionally passed as first parameter to getter. 
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Round`: Rounds given value to integer.
- `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Soundex`: Returns soundex of given value. Could be used 
for similarity calculation.

> Depending on the *Product Index* implementation, the *Product Index* configuration can be slightly different. 
> See sample configurations for specific *Product Iindex* implementations.
 
