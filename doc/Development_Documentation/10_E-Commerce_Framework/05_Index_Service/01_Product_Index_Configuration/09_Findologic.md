# Special Aspects for Findologic Exporter
Basically findologic worker works as described in the [optimized architecture](README.md). But there is an additional 
speciality with the export: 

Executing `php bin/console ecommerce:indexservice:process-queue update-index` does not write the data directly to 
Findologic, but into an extra table 
`\Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultFindologic::EXPORT_TABLE_NAME` 
(default is `ecommerceframework_productindex_export_findologic`). 

Findologic then can use the endpoint `/ecommerceframework/findologic-export`, which delivers all data directly based on 
the export table. Valid parameters for this endpoint are:
- `start`: Pagination start.
- `count`: Count of delivered entries.
- `shopKey`: Shop key to identify the shop. 
- `id`: Filter for Product-ID
- `type`: Filter for type
