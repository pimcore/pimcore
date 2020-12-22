# Configuration Configuration

Following aspects need to be considered in index configuration:  

## General Configuration Options
In the `config_options` area general Elastic Search settings can be made - like hosts, index settings, etc. 

##### `client_config`
- `logging`: `true`/`false` to activate logging of elastic search client
- `indexName`: index name to be used, if not provided tenant name is used as index name 

##### `index_settings`
Index settings that are used when creating a new index. They are passed 1:1 as 
settings param to the body of the create index command. Details see 
also [Elastic Search Docs](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_index_management_operations.html). 


##### `es_client_params`
- `hosts`: Array of hosts of the Elastic Search cluster to use. 
- `indexType`: Necessary for Elastic Search 6 - defines the type name of products in index. 

##### `synonym_providers`
Specify synonym providers for synonym filters defined in filter section of index settings. 
For details see [Synonyms](./02_Synonyms.md).

#### Sample Config
```yml
pimcore_ecommerce_framework:
    index_service:
        tenants:
            MyEsTenant:
                config_options:
                    client_config:
                        logging: false
                        indexName: 'ecommerce-demo-elasticsearch'

                    es_client_params:
                        hosts:
                            - '%elasticsearch.host%'
                        indexType: 'Product'

                    index_settings:
                        number_of_shards: 5
                        number_of_replicas: 0
                        analysis:
                            analyzer:
                                my_ngram_analyzer:
                                    tokenizer: my_ngram_tokenizer
                                whitelist_analyzer:
                                    tokenizer: standard
                                    filter:
                                      - white_list_filter
                            tokenizer:
                                my_ngram_tokenizer:
                                    type: nGram
                                    min_gram: 2
                                    max_gram: 15
                                    token_chars: [letter, digit]
                            filter:
                                white_list_filter:
                                    type: keep
                                    keep_words:
                                      - was
                                      - WAS
```


## Data Types for attributes
The type of the data attributes needs to be set to elastic search data types. Be careful, some types changed between
Elastic Search 5 and 6 (like string vs. keyword/text). 

```yml
pimcore_ecommerce_framework:
    index_service:
        tenants:
            MyEsTenant:
                attributes:
                    name:
                        locale: '%%locale%%'
                        type: keyword
```

In addition to the `type` configuration, you also can provide custom mappings for a field. If provided, these mapping 
configurations are used for creating the mapping of the elastic search index.

You can also skip the `type` and `mapping`, then ES will try to create dynamic mapping. 

```yml

pimcore_ecommerce_framework:
    index_service:
        tenants:
            MyEsTenant:
                attributes:
                    name:
                        locale: '%%locale%%'
                        type: text
                        options:
                            mapping:
                                type: text
                                store: true
                                index: not_analyzed
                                fields:
                                    analyzed:
                                        type: text
                                        analyzer: german
                                    analyzed_ngram:
                                        type: text
                                        analyzer: my_ngram_analyzer
``` 
