# Deployment Tools

Following tools are provided by Pimcore to support deployment processes. 

## Pimcore Configurations

All Pimcore configurations are saved as PHP files on the file system. As a result they can be included into 
[version control systems](./01_Version_Control_Systems.md) and by utilizing the 
[multi environment feature](./03_Multi_Environment.md) different configuration files for different deployment stages 
an be defined. 

* <https://github.com/pimcore/pimcore/tree/master/website_demo/var/config> 
* <https://github.com/pimcore/pimcore/tree/master/website_demo/config>


## Pimcore Class Definitions

As with Pimcore configurations also Pimcore class definitions are saved as PHP configuration files and therefore can 
be added to version control systems and be deployed to different deployment stages. 

> **Note**: Changes on Pimcore class definitions not only have influence to configuration files but also on the database. 
> If deploying changes between different deployment stages also database changes need to be deployed. This can be done
> with the `deployment:classes-rebuild` command. 


After every code update you should use the `deployment:classes-rebuild` command to push changes to the database.
 
```bash
php pimcore/cli/console.php deployment:classes-rebuild
```


As an alternative also class export to json-files and the class import commands can be used. 

```bash
php pimcore/cli/console.php definition:import:objectbrick /brick_jsonfile_path.json

php pimcore/cli/console.php definition:import:fieldcollection /collection_jsonfile_path.json

php pimcore/cli/console.php definition:import:class /class_jsonfile_path.json
```


## Pimcore Console

The [Pimcore Console](../09_Development_Tools_and_Details/11_Console_CLI.md) provides several useful tasks for deployment. 
 These tasks can be integrated into custom deployment workflows and tools. One example for them would be the Pimcore
 class definitions as described above. 

To get a list of all available commands use ```php pimcore/cli/console.php list````. 

#### Potentially useful commands:

| Command                                              | Description                                                                                     |
|------------------------------------------------------|-------------------------------------------------------------------------------------------------|
| classmap-generator                                   | Generate class maps to improve performance                                                      |
| mysql-tools                                          | Optimize and warmup mysql database                                                              |
| search-backend-reindex                               | Re-indexes the backend search of pimcore                                                        |
| update                                               | Update pimcore to the desired version/build                                                     |
| cache:clear                                          | Clear caches                                                                                    |
| cache:warming                                        | Warm up caches                                                                                  |
| classificationstore:delete-store                     | Delete Classification Store                                                                     |
| definition:import:class                              | Import Class definition from a JSON export                                                      |
| definition:import:fieldcollection                    | Import FieldCollection definition from a JSON export                                            |
| definition:import:objectbrick                        | Import ObjectBrick definition from a JSON export                                                |
| deployment:classes-rebuild                           | rebuilds classes and db structure based on updated `website/var/classes/definition_*.php` files |
| thumbnails:image                                     | Generate image thumbnails, useful to pre-generate thumbnails in the background                  |
| thumbnails:optimize-images                           | Optimize filesize of all images in `/vagrant/www/pimcore/website/var/tmp`                       |
| thumbnails:video                                     | Generate video thumbnails, useful to pre-generate thumbnails in the background                  |

Find more about the Pimcore Console on the [dedicated page](../09_Development_Tools_and_Details/11_Console_CLI.md).


## Content migration

The content migration between environments is not provided by Pimcore and also it's not recommended.
 
The content should be created by editors in the production environment and visibility on the frontend can be managed 
by built-in features like publishing / unpublishing / [versioning](../08_Tools_and_Features/01_Versioning.md) / 
[scheduling](../08_Tools_and_Features/03_Scheduling.md) / preview the effect in editmode.

Therefore, editors shouldn't work on different stages. 

Of course, the content migration is possible but this is always a very individual task depending on data model, environments 
and use cases. 
 
If you need some kind of content migration utilize the PHP API for [assets](../04_Assets/01_Working_with_PHP_API.md), 
[objects](../05_Objects/03_Working_with_PHP_API.md) and [documents](../03_Documents/09_Working_with_PHP_API.md) for doing so. 