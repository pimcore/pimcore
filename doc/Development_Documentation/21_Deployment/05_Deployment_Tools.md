# Deployment Tools

Following tools are provided by Pimcore to support deployment processes. 

## Pimcore Configurations

All Pimcore configurations are saved as YAML or PHP files on the file system. As a result they can be included into 
[version control systems](./01_Version_Control_Systems.md) and by utilizing the 
[multi environment feature](./03_Multi_Environment.md) different configuration files for different deployment stages 
can be defined. 

* <https://github.com/pimcore/pimcore/tree/master/app/config> 
* <https://github.com/pimcore/pimcore/tree/master/app/config/pimcore>
* <https://github.com/pimcore/pimcore/tree/master/var/config>


## Pimcore Class Definitions

As with Pimcore configurations also Pimcore class definitions are saved as PHP configuration files and therefore can 
be added to version control systems and be deployed to different deployment stages. 

> **Note**: Changes on Pimcore class definitions not only have influence to configuration files but also on the database. 
> If deploying changes between different deployment stages also database changes need to be deployed. This can be done
> with the `pimcore:deployment:classes-rebuild` command. 


After every code update you should use the `pimcore:deployment:classes-rebuild` command to push changes to the database.
 
```bash
./bin/console pimcore:deployment:classes-rebuild
```


As an alternative also class export to json-files and the class import commands can be used. 

```bash
./bin/console pimcore:definition:import:objectbrick /brick_jsonfile_path.json

./bin/console pimcore:definition:import:fieldcollection /collection_jsonfile_path.json

./bin/console pimcore:definition:import:class /class_jsonfile_path.json
```


## Pimcore Console

The [Pimcore Console](../19_Development_Tools_and_Details/11_Console_CLI.md) provides several useful tasks for deployment. 
 These tasks can be integrated into custom deployment workflows and tools. One example for them would be the Pimcore
 class definitions as described above. 

To get a list of all available commands use `./bin/console list`. 

#### Potentially useful commands:

| Command                                              | Description                                                                                     |
|------------------------------------------------------|-------------------------------------------------------------------------------------------------|
| pimcore:mysql-tools                                  | Optimize and warm up mysql database                                                              |
| pimcore:search-backend-reindex                       | Re-indexes the backend search of Pimcore                                                        |
| pimcore:cache:clear                                  | Clear Pimcore core caches                                                                                    |
| cache:clear                                          | Clear Symfony caches                                                                                    |
| pimcore:cache:warming                                | Warm up caches                                                                                  |
| pimcore:classificationstore:delete-store                     | Delete Classification Store                                                                     |
| pimcore:definition:import:class                      | Import Class definition from a JSON export                                                      |
| pimcore:definition:import:customlayout               | Import Customlayout definition from a JSON export                                               |
| pimcore:definition:import:fieldcollection            | Import FieldCollection definition from a JSON export                                            |
| pimcore:definition:import:objectbrick                | Import ObjectBrick definition from a JSON export                                                |
| pimcore:deployment:classes-rebuild                   | Rebuilds classes and db structure based on updated `var/classes/definition_*.php` files |
| pimcore:thumbnails:image                             | Generate image thumbnails, useful to pre-generate thumbnails in the background. Use `--processes` option for parallel processing.                   |
| pimcore:thumbnails:optimize-images                   | Optimize file size of all images in `web/var/tmp`                       |
| pimcore:thumbnails:video                             | Generate video thumbnails, useful to pre-generate thumbnails in the background. Use `--processes` option for parallel processing.                   |

Find more about the Pimcore Console on the [dedicated page](../19_Development_Tools_and_Details/11_Console_CLI.md).


## Content migration

The content migration between environments is not provided by Pimcore and it's not recommended at all.
 
The content should be created by editors in the production environment and visibility on the frontend can be managed 
by built-in features like publishing / unpublishing / [versioning](../18_Tools_and_Features/01_Versioning.md) / 
[scheduling](../18_Tools_and_Features/03_Scheduling.md) / preview the effect in editmode.

Therefore, editors shouldn't work on different stages. 

Of course, the content migration is possible but this is always a very individual task depending on data model, environments 
and use cases. 
 
If you need some kind of content migration utilize the PHP API for [assets](../04_Assets/01_Working_with_PHP_API.md), 
[objects](../05_Objects/03_Working_with_PHP_API.md) and [documents](../03_Documents/09_Working_with_PHP_API.md) for doing so. 
