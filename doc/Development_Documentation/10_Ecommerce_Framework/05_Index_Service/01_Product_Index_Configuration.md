# Product Index Configuration

The configuration of the *Product Index* defines the content of the *Product Index* (which attributes are extracted how
and stored where) and takes place in the `EcommerceFrameworkConfig.php` within the section `productindex`:

```php
"productindex" => [
    /* to disable default tenant, add parameter  "disableDefaultTenant"=>true  to productindex element  */
    
    /* add columns for general fulltext search index of productlist - they must be part of the column configuration below  */
    "generalSearchColumns" => [
        /* column definition for product index */
        [
            "name" => "name"
        ],
        [
            "name" => "seoname"
        ]
    ],
    /* column definition for product index 
     *
     * Included config files will be merged with given columns.
     *
     * Placeholder values in this file ("locale" => "%locale%") will be replaced by
     * the given placeholder value (eg. "de_AT").
     */
    "columns" => [
        [
            "file" => "/website/var/plugins/EcommerceFramework/additional-index.php",
            "placeholders" => ["%locale%" => "de_AT"]
        ],
        [
            "name" => "name",
            "type" => "varchar(255)",
            "locale" => "en_GB",
            "filtergroup" => "string"
        ],
        [
            "name" => "seoname",
            "type" => "varchar(255)",
            "filtergroup" => "string"
        ]
    ]
]
```


### `generalSearchColumns`
Defines attributes which should be considered in fulltext-search. All attributes must be defined within the 
`<columns>`-section. 

### `columns`
Defines for all attributes if and how they should be indexed. Following configuration options are available: 
- `name` (mandatory): Name of attribute in product index, also used as `fieldname`, if `fieldname` is not provided. 
- `type` (mandatory for mysql): Datatype for column in Product Index. 
- `fieldname` (optional): Field name in product object, needed if it is different than `name` in index.
- `locale` (optional): Used locale for data retrieving if field is in a localized field. 
- `getter` (optional): Special getter implementation for getting attribute value. Per default, the method `get<NAME>()`
   of the product object is called. If this is not suitable, an alternative getter class can be defined which is 
   responsible for getting the value. This can be used for calculations, getting complex values 
   (field collections, object bricks), etc. Getter implementations need to implement 
   `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\IGetter` interface.  
- `interpreter` (optional): By default all data is stored without any transformation in the Product Index. With an 
interpreter class, this data can be transformed and manipulated before storing. This can be used for only saving IDs 
of assets, normalization of data, special treatment of relations, etc. Interpreter implementations need to implement
`Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Interpreter` interface. 
 
- `hideInFieldlistDatatype` (optional): Hides column in FieldList drop down (see [FilterService](../07_Filter_Service.md) 
   for more information).
- `filtergroup` (optional): Defines filter group for the FieldList drop down (see [FilterService](../07_Filter_Service.md) 
   for more information).

Each attribute can have an additional `<config>`-element for further configuration which can be used in getter or 
 interpreter implementations.


#### Relations in *Product Index*
Relations are stored in a special way in *Product Index* and also need to be filtered in a different way in the 
[Product List](./07_Product_List.md).
 
In order to store relations correctly in the Product Index, relation attributes must have an interpreter defined which 
implements the interface `Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IRelationInterpreter`. 


#### Selection of available Getters:
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\DefaultBrickGetter`: Gets data out of an Object Brick. 
Needed configuration: 
  - `brickfield`: Field name of Object Brick, e.g. `bricks`. 
  - `bricktype`: Type of Object Brick, e.g. `TentBrick`. 
  - `fieldname`: Field name of attribute in Object Brick, e.g. `height`. 
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\DefaultBrickGetterSequence`: Same as `DefaultBrickGetter`, 
   but can use more than one source definition and stores first found value in *Product Index*. 
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\DefaultBrickGetterSequenceToMultiselect`: Like 
  `DefaultBrickGetterSequence`, but stores all found values as a multi select in the *Product Index*. 
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter\TagsGetter`: Gets [Tags](../../18_Tools_and_Features/09_Tags.md) 
  of product object and returns them as array. 


#### Selection of available Interpreters:
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\AssetId`: Stores only asset id into *Product Index*.
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultObjects`: Default interpreter to store object 
  relations as relations into the *Product Index*.
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultRelations`: Same as `DefaultObjects` but also 
  for other relations to Assets and Documents. Also can deal with `ObjectMetadata`. 
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultStructuredTable`: Interpreter for structured
  table data type. Expects following configuration options: 
     - `row`
     - `column`
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IdList`: Returns id list of given values as CSV list. 
 If configuration option `multiSelectEncoded` is set, it returns id list encoded as multi select (relevant for filtering 
 in Product List). 
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Numeric`: Returns `floatval` of given value. 
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\ObjectId`: Returns id of given object. 
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\ObjectIdSum`: Calculates sum if ids of given objects. 
Could be used for similarity calculation. 
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\ObjectValue`: Get value from an related object. 
Expects following configuration options: 
     - `['target']['fieldname']`: Field name of value to get. Is used for getter generation which is called on given object. 
     - `['target']['locale']`: Locale is optionally passed as first parameter to getter. 
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Round`: Rounds given value to integer.
- `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\Soundex`: Returns soundex of given value. Could be used 
for similarity calculation.


> Depending on the *Product Index* implementation, the *Product Index* configuration can be slightly different. 
> See sample configurations for specific *Product Iindex* implementations.
 
