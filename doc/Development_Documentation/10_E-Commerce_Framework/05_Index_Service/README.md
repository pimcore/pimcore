# Index Service

The Index Service (in combination with the Filter Service) provides functionality for indexing, listing, filtering and 
searching products.
 
In the heart of this component is the *Product Index* - an optimized storage of product data for all kinds of queries. Depending 
on the implementation, the *Product Index* is stored in a special mysql table, in elastic search or any other search 
provider (currently implemented are [fact finder](http://www.fact-finder.de/) and [findologic](https://www.findologic.com/)). 
These implementations can be configured in [Assortment Tenants](./01_Product_Index_Configuration/03_Assortment_Tenant_Configuration.md). 
The default tenant always uses `DefaultMysql` as implementation.  

The separate *Product Index* has several advantages:  
- It is completely independent from the Pimcore object structure, only contains needed information and can pre-calculate 
  complex data.
- It can be optimized without any side effects on Pimcore for requirements considering filtering, listing and 
  searching products. 
- It supports assortment tenants and therefore allows optimized indices for multiple assortments within one system. 


Based on the *Product Index* the [*Product List*](./07_Product_List.md) provides a one stop API for accessing data and 
listing products. 


See the following topics for details: 
- [Configuration of the *product index*](./01_Product_Index_Configuration/README.md)
- [Assortment Tenant configuration](./01_Product_Index_Configuration/03_Assortment_Tenant_Configuration.md)
- [Data architecture and indexing process](./01_Product_Index_Configuration/05_Data_Architecture_and_Indexing_Process.md)
- [Working with Product Lists](07_Product_List.md)
- [Mockup Objects in Product List Results](09_Mockup_Objects.md)
