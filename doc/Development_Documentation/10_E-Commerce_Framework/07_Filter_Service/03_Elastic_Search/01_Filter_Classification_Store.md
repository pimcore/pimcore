# Filter Classification Store

With Elastic Search it is possible to index all attributes of [Classification Store](../../../05_Objects/01_Object_Classes/01_Data_Types/15_Classification_Store.md) 
data without defining an attribute for each single classification store key.   

To do so, follow these steps

### 1) Index Definition

Pimcore ships with a special interpreter to load all classification store attributes to the index. To use this interpreter
add following attribute configuration into your index definition:  

```yml
some_field_name:
    fieldname: 'my_classification_store_field_name'
    interpreter: Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\DefaultClassificationStore
    filtergroup: classificationstore
    options:
        mapping:
            type: 'object'
            properties:
                keys:
                    type: 'long'
                values:
                    type: 'object'

```

This results in a data structure in your index similar to this:

```json
"some_field_name" : {
  "values" : {
    "1" : [
      "one value"
    ],
    "2" : [
      "another value"
    ],
    "3" : [
      "123",
      "99"
    ],
    "5" : [
      "AT",
      "DZ"
    ],
    "4" : [
      ""
    ]
  },
  "keys" : [
    1,
    2,
    3,
    5,
    4
  ]
```

This contains all the classification store IDs as well as all the data. 


### 2) Filter Type

To interpret the indexed data correctly, a special filter type is needed. As this filter type works only in combination 
with elastic search, it is not activated by default. Following steps are necessary to activate it: 

- make sure the field collection `FilteSelectClsStoreAttributes` is installed and is allowed in the filter list of
  the filter definition class.  
- add following filter type mapping (see [here](../README.md) for details):

```yml
FilteSelectClsStoreAttributes:
    filter_type_id: Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch\SelectClassificationStoreAttributes
    template: 'product/filters/nested_attributes.html.twig'
```

### 3) Template

The template for the filter has to render not only one filter, but the whole list of possible filters for the classification
store attributes. For a sample template see our [demo](https://github.com/pimcore/demo/blob/master/app/Resources/views/product/filters/nested_attributes.html.twig). 
