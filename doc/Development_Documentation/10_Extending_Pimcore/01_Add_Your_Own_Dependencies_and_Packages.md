# Add your own Dependencies and Packages

Pimcore manages itself all dependencies using composer and therefore you can add your own dependencies by using 
 standard composer functionalities. 

## Basic Example
Use composer in your project root directory, eg. 
```bash
composer require mtdowling/cron-expression
```

## Pimcore Bundles

Pimcore bundles 


## Version Checking
To avoid compatibility problems with plugins or custom components, that are compatible with a special Pimcore version only, Pimcore
has following requirement `pimcore/core-version` that defines its current version: 

```jsonmus
{
    ...
    "require": {
        ...
        "pimcore/core-version": "4.3.1",
        ...
    }
    ...
}
```

If your components have the same requirement to the versions they can work with, composer prevents you from installing your components
to an unsupported version of Pimcore due to version conflicts to the requirement `pimcore/core-version`. 
