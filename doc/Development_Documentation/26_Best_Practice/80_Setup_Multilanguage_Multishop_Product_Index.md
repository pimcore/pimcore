# Setup Multi Language & Multi Shop Product Index

A common requirement in e-commerce applications are *multi language* and *multi shop* environments. 

As *multi language* we understand that the whole store front should offer the same products in multiple languages. 
Therefore also the product search and product filtering has to be language specific. 

As *multi shop* we understand multiple shops in one shop offering different products and maybe also having different 
processes (which is not part of this article), etc.

These requirements are fully met by the e-commerce framework but have effects on how to setup the product index.
 For a general description of how to setup the product index see our [docs](../10_E-Commerce_Framework/05_Index_Service/01_Product_Index_Configuration/README.md). 


#### Multi Language 

There are two ways of setting up a multi language product index. 


##### 1) One Product Index with Multiple Fields per Language

This is suitable when only having a few multi language fields, e.g. just name and description, and all the other fields 
are the same for each language. 

```yml
pimcore_ecommerce_framework:
    index_service:
        tenants:
            mytenant: 
                attributes:                          
                    name_de:
                        type: 'varchar(100)'
                        locale: 'de'
                        filter_group: string
                    name_en:
                        type: 'varchar(100)'
                        locale: 'en'
                        filter_group: string
                    name_fr:
                        type: 'varchar(100)'
                        locale: 'fr'
                        filter_group: string        
```

As a consequence you need to take care using the correct attributes for filtering and searching by your self in the 
 application (filter definition, fulltext search) which might be quite complex and hard to archive.

Beneficial is that only one product index is needed which results in less needed storage and reduced indexing time. 
  


##### 2) One Product Index per Language

This is suitable when having multiple language fields or complex applications and takes advantage of e-commerce frameworks
[assortment tenants](../10_E-Commerce_Framework/05_Index_Service/01_Product_Index_Configuration/03_Assortment_Tenant_Configuration.md)
 by configuring one assortment tenant per language. 
 
In order to reduce configuration effort (like copying all attributes for each assortment tenant), you can take advantage of
the `_defaults` configuration feature and `placeholders`.
 
```yml
pimcore_ecommerce_framework:
    index_service:
        tenants:
            _defaults: 
                attributes: 
                    name:
                        type: varchar(255)
                        locale: '%%locale%%'
                        filter_group: string
                    description:
                        type: varchar(255)
                        locale: '%%locale%%'
                        filter_group: string
                    weight:
                        type: double
                        filter_group: number    
                            
            mytenant_de: 
                placeholders: 
                    '%%locale%%': de
            mytenant_en: 
                placeholders: 
                    '%%locale%%': en
            mytenant_fr: 
                placeholders: 
                    '%%locale%%': fr                    
```

As a consequence you don't need to take care of using the correct attributes for filtering and searching. You just need to
make sure to activate the correct assortment tenant based on the current language. Everything else is done by the framework. 

Downside is that there are multiple product indices which results in more needed storage and more indexing time. 



#### Multi Shop 

Multi shop setups that just differ in the assortment, but not in products and attributes of products, can be setup with 
the frameworks [Assortment Subtenant](../10_E-Commerce_Framework/05_Index_Service/01_Product_Index_Configuration/03_Assortment_Tenant_Configuration.md) 
feature. 
  
Assortment subtenants are basically a mapping that defines which of the products should be available in a certain shop.  

For setting them up, you need to configure an assortment tenant with a configuration, that sets up sub tenants. 

```yml

pimcore_ecommerce_framework:
    index_service:
        tenants:
            default:
                enabled: false
                
                # service ID of the desired configuration
                config_id: Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysqlSubTenantConfig
```

The config has to be set up as container service. A sample implementation for a configuration with sub tenants is
[DefaultMysqlSubTenantConfig](https://github.com/pimcore/pimcore/blob/master/bundles/EcommerceFrameworkBundle/IndexService/Config/DefaultMysqlSubTenantConfig.php). 



## Summary
To summarize things following rule of thumb can be applied:

> Use Assortment tenants for multi language und multi shop setups by using assortment tenants for each language 
> and assortment sub tenants for configuring multiple shops. 
