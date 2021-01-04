# GDPR Data Extractor 

The GDPR Data Extractor is a tool that helps the user to full fill the *right of access by the data subject* and helps to
export data that is stored for a specific person in different data sources like `data objects`, `sent mails`, `Pimcore backend 
user`, etc. For details on the usage see our user docs chapter 
[Data Protection and GDPR](../../User_Documentation/10_Administration_of_Pimcore/01_Data_Protection_and_GDPR.md).

![GDPR Data Extractor](../img/gdpr-data-extractor.jpg)

## Configuration 
Via the configuration, following options can be set to modify the behaviour of the Data Extractor: 
* What data object classes should be included (e.g. exclude data object classes without personal information like products)
* What relation attributes should included recursively into the data export (e.g. include order items into export of orders)
* Allow deletion of data object directly in result view

For Details see configuration reference as follows: 

```yml
# Default configuration for "PimcoreAdminBundle"
pimcore_admin:
    gdpr_data_extractor:

        # Settings for DataObjects DataProvider
        dataObjects:

            # Configure which classes should be considered, array key is class name
            classes:

                # Prototype: 
                #     MY_CLASS_NAME: 
                #               include: true
                #               allowDelete: false
                #               includedRelations:
                #                       - manualSegemens
                #                       - calculatedSegments
                #                         
                -

                    # Set if class should be considered in export.
                    include:              true

                    # Allow delete of objects directly in preview grid.
                    allowDelete:          false

                    # List relation attributes that should be included recursively into export.
                    includedRelations:    []

```


Pimcore ships with a reasonable default configuration. By using it, all data object classes are considered in the search, 
export concludes all attributes directly attached to the data object (no relations) and allows deletion of the data objects 
directly in the result list. 
 
 
## Extending GDPR Data Extractor with Custom Data Sources
It is possible to attach additional data sources to the GDPR Data Extractor with Pimcore Bundles. Thereby specific data 
exports can be attached or external data sources can be included. 

To do so, following steps are necessary: 

1) Create a custom implementation of 
[`Pimcore\Bundle\AdminBundle\GDPR\DataProvider\DataProviderInterface`](https://github.com/pimcore/pimcore/blob/master/bundles/AdminBundle/GDPR/DataProvider/DataProviderInterface.php#L20). 
The following functions need to be implemented:

    * `getSortPriority()` - Returns sort priority for the tabs - higher is sorted first.
    * `getName()` - Returns the name of the data provider.
    * `getJsClassName()` - Returns the name of the JavaScript class implementation for frontend presentation.

2) Implement the specified JavaScript class with all the user interface with following restrictions:

    * The constructor gets the current `searchParams` as parameter.
    * It needs to have a function `getPanel()` that returns a `Ext.Panel`.

3) Register your custom implementation as service. The service needs to be tagged with the tag `pimcore.gdpr.data-provider`.
   If you're using autoconfiguration this will be automatically done for you, otherwise you need to specify the tag in
   your service definition:

    ```yml
    # either enable autoconfigure as _defaults (or only for your service)
    services:
        _defaults:
            autoconfigure: true
            public: false

        AppBundle\GDPR\DataProvider\MyCustomDataProvider: ~

    # or specify the tag manually if not using autoconfiguration
    services:
        _defaults:
            public: false

        AppBundle\GDPR\DataProvider\MyCustomDataProvider:
            tags:
                - { name: pimcore.gdpr.data-provider }
    ```

For an example see the implementation for the [customers data provider](https://github.com/pimcore/customer-data-framework/blob/master/src/GDPR/DataProvider/Customers.php) 
in our customer management framework. 
