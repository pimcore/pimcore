# Data Providers

A data provider is a class implementing the [`DataProviderInterface`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Targeting/DataProvider/DataProviderInterface.php).
It is expected to set its data onto the `VisitorInfo` under a given key. As best practice, the core data providers expose
their storage key as constant. This constant is used to store data and to read it from conditions.

A data provider is simply registered as service. This service ID needs to be added to the config in order to be mapped
properly to other components:

```yaml
pimcore:
    targeting:
        data_providers:
            custom: MyVendor\MyBundle\Targeting\DataProvider\Custom
```

Basically the data provider can do anything, however the core data providers do the following:

* They store their information on a storage key which is exposed as constant
* They always set their content key. If no data can be resolved (e.g. GeoIP is unable to resolve a location), `null` is set.
* Before loading data, core providers check if there already is an entry for the own storage key and abort loading if that
 is the case.
