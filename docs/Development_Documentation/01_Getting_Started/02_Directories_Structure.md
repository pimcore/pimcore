# Directories Structure

| Directory                                            | Description                                                                          |
|------------------------------------------------------|--------------------------------------------------------------------------------------|
| `/pimcore/` | Core files of Pimcore, do not change anything here.                                |
| `/plugins/` | Directory for [plugins / extensions](../Extending_Pimcore/Plugin_Developers_Guide/Example.md). |
| `/vendor/`  | All third-party libraries are there. It's the default location for packages installed by [Composer](https://getcomposer.org/) / [Packagist](https://packagist.org/).                     |
| `/website/` | Everything regarding your individual project/application (templates, controllers, settings, objects, ...). All your code goes there (see below).   |

  
  
### Contents of `/website` 

| Directory             | Description                                                                                                        |
|-----------------------|--------------------------------------------------------------------------------------------------------------------|
| `/website/controllers` | Controllers of your application.                                                                      |
| `/website/config`      | Configuration files for cache, workflow modules, DI configuration, extensions additional configuration, ... [Examples](https://github.com/pimcore/pimcore/tree/master/website_demo/config)        |
| `/website/lib`         | Custom libraries (if needed, use Composer to install dependencies whenever possible)                                                                                      |
| `/website/models`      | Your custom models (if needed).                                                                                    |
| `/website/var`         | This directory contains files created by Pimcore during runtime like assets, classes, thumbnails, ... (Pimcore needs [write access](../13_Installation_and_Upgrade/03_System_Setup_and_Hosting/03_File_Permissions.md) on this directory)             |
| `/website/views`       | Your templates/views.                                                                                                    |
