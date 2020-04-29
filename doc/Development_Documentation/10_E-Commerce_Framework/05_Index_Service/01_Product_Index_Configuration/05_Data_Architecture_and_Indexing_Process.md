# Data Architecture and Indexing Process
Depending on the *Product Index* implementation, there are two different *Product Index* data architectures and ways for 
indexing: Simple Mysql Architecture and Optimized Architecture. 

For indexing itself the helper class `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Tool\IndexUpdater` 
or the provided Pimcore console commands can be used. 


## Simple Mysql Architecture
- Pimcore object data is transferred directly to the Product Index. 
- After every update of a Pimcore object, the changes are directly written into the Product Index. 
- Only used for `\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysql`

> Updates of dependent objects (like child objects, variants that inherit data, related objects) are not transferred 
> into the index automatically. 


![productindex-simple](../../../img/productindex-simple.png)

### Console Commands for Simple Mysql Architecture

- For manually updating the whole index use following command: 
```bash
php bin/console ecommerce:indexservice:bootstrap --update-index
```

- If you need to create or update the index structures you can use:
```bash
php bin/console ecommerce:indexservice:bootstrap --create-or-update-index-structure
```

- By default, Pimcore assumes that the product class is `Pimcore\Model\DataObject\Product`. If you have a 
different product class name, please use the `--object-list-class` param and provide the listing class name
that should be used. 

> For further details (e.g. only updating certain product types, apply list conditions) see `--help` section of the 
>`ecommerce:indexservice:bootstrap` command. 




## Optimized Architecture
- In the optimized architecture, object data is transferred **not** directly to the *Product Index*. 
- In this case a so called store table is between the Pimcore objects and the *Product Index*. This store table enables to ...
   - ... update the *Product Index* only if index relevant data has changed. Therefore the load on the index itself is reduced 
         and unnecessary write operations are prevented. 
   - ... update the *Product Index* asynchronously and therefore update also dependent elements (childs, variants, ...) 
         of an updated Pimcore object without impact on save performance. 
   - ... rebuilding the whole *Product Index* out of the store table much faster since no direct interaction with 
         Pimcore objects is needed. 

- After every update of a Pimcore object, the changes are written into the store table and all child objects of the 
updated object are added to the so called preparation queue (see later). As a consequence a periodic full update 
should not be necessary any more.
- Used for optimized mysql, elastic search, ...

![productindex-optimized](../../../img/productindex-optimized.png)


### Console Commands for Optimized Architecture

For updating data in index following commands are available.
- For process the preparation queue and update Pimcore objects to the index store table, use following command. 
**This command should be executed periodically (e.g. all 10 minutes).**

```bash
php bin/console ecommerce:indexservice:process-queue preparation
```

- To update the Product Index based on changes stored in the store table use the following command. 
**This command should be executed periodically (e.g. all 10 minutes).**

```bash
php bin/console ecommerce:indexservice:process-queue update-index
```

- For manually update all Pimcore objects in the index store use following command. As stated before, this should only be
  necessary for an initial fill-up of the index. After that, at least Product Index Store and Pimcore objects should always 
  be in sync. It is important to execute `ecommerce:indexservice:process-queue preparation` and 
  `ecommerce:indexservice:process-queue update-index` periodically though.
```bash
php bin/console ecommerce:indexservice:bootstrap --update-index
```
> By default, Pimcore assumes that the product class is `Pimcore\Model\DataObject\Product`. If you have a 
> different product class name, please use the `--object-list-class` param and provide the listing class name
> that should be used. 

- Invalidate either the preparation queue or the index-update queue. This is usually **only needed during development** when 
  the store table is out of sync. Reset the preparation queue for instance when your product model 
  returns updated data for a field.
```bash
php bin/console ecommerce:indexservice:reset-queue preparation
php bin/console ecommerce:indexservice:reset-queue update-index
```

- If you need to create or update the index structures you can still use:
```bash
php bin/console ecommerce:indexservice:bootstrap --create-or-update-index-structure
```

### Console Commands for Elastic Search
In case that you are using Elastic Search (e.g., ``Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ElasticSearch\DefaultELasticSearch6``),
 there are two additional helper commands. Those 
  1)  simplify the synchronisation of index mapping updates and eases the integration
  2)  support the integration of a synonym search out-of-the-box.

#### Case 1: Synchronization of Mapping Updates
The following command performs a native index updated based on the [Reindex API](https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-reindex.html)
of Elastic Search (ES).

```bash
php bin/console ecommerce:indexservice:elasticsearch-sync reindex
```

The native reindexing is faster than running the ecommerce process updating queue, and only one command needs to be executed.
This is especially useful when a new search feature is under development. In the later case, changes in the ES index mapping
often require a complete reindexing so that the new settings will be applied to the index
(cf. [Product Index Configuration](../README.md)).

#### Case 2: Synonym Support
Pimcore provides a simple solution for ES synonym search integration out of the box.

Basic index configuration setup for synonyms (cf. [Product Index Configuration](../README.md)):
```yml
pimcore_ecommerce_framework:
    index_service:
       example_tenant:
            #...            
            config_options: 
                #...
                index_settings:
                    #...
                    analysis:
                        #...                        
                        analyzer:
                            app_synonyms_lowercase:
                                tokenizer: keyword # @see https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-tokenizers.html
                                filter:
                                    - app_synonyms_filter
                                    - lowercase
                        filter:
                            app_synonyms_filter:
                                type: synonym
                                synonyms_path: "pimcore_config/synonyms_example_tenant.txt" # must reside in /etc/elasticsearch/
            attributes:
                articleName:
                    filtergroup: string
                    type: 'text'
                    locale: 'de'
                    options:
                        mapping:
                            type: keyword
                            store: true
                            index: true
                            fields:                                 
                                analyzed_synonym:
                                    type: text
                                    analyzer: app_synonyms_lowercase             
```

In the example, the synonym file must be finally located in ``/etc/elasticsearch/pimcore_config/synonyms_example_tenant.txt`` at indexing time.
You can create a text file based on ES's / Lucene's [synonym format](https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-synonym-tokenfilter.html))
within Pimcore's Asset Management system, e.g. in ``/System/Ecommerce/Search/Synonyms/synonyms_example_tenant``.

Example synonym content: 
```txt
football, soccer
volleyball, beachball
pimcore, pim-core, pim
```

By executing

```bash
php bin/console ecommerce:indexservice:elasticsearch-sync refresh-synonyms --synonymAssetSourceFolder=/System/Ecommerce/Search/Synonyms
```
the txt file will be extracted from Pimcore's asset management system and copied to the Elastic Search target location. 
Ensure that the directory ``/etc/elasticsearch/pimcore_config/`` exists.
Afterwards the search index will be closed and re-openeds so that the updates in the synonym file are applied.

> For further details see `--help` section of the commands. 


