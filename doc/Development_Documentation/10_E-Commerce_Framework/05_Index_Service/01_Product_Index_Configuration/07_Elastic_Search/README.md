# Special Aspects for Elastic Search
Basically elastic search worker works as described in the [optimized architecture](../README.md). 
Currently Elastic Search 5 and Elastic Search 6 are supported. 

## Installation
To work properly Pimcore requires the Elasticsearch bindings, install them with: `composer require elasticsearch/elasticsearch`.

## Index Configuration
Elastic search provides a couple of additional configuration options for the index to utilize elastic search features. 
See [Configuration Details](01_Configuration_Details.md) for more information. 

## Reindexing Mode
It is possible that Elastic Search cannot update the mapping, e.g. if data types of attributes change on the fly. 
For this case, a reindex is necessary. If it is necessary, the E-Commerce Framework automatically switches into a 
reindex mode. When in reindex mode, all queries go to the current index but in parallel a new index is created based 
on the data in the store table. The current index is read only and all data changes that take place go directly into 
the new index. As a result, during reindex the results delivered by Product Lists can contain old data. 
 
As soon the reindex is finished, the current index is switched to the newly created index and the old index is deleted.  


## Indexing of Classification Store Attributes

With Elastic Search it is possible to index all attributes of [Classification Store](../../../../05_Objects/01_Object_Classes/01_Data_Types/13_Classification_Store.md) 
data without defining an attribute for each single classification store key.   

For details see [Filter Classification Store](../../../07_Filter_Service/03_Elastic_Search/01_Filter_Classification_Store.md) 
in Filter Service documentation. 