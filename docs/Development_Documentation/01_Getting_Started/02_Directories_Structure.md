## General

Find basic information about directories tree in Pimcore project, below. 

| Directory                                            | Description                                                                          |
|------------------------------------------------------|--------------------------------------------------------------------------------------|
| `/pimcore/` | Here are the core files of Pimcore, do not change anything here.                                |
| `/plugins/` | Directory for [plugins / extensions](../Extending_Pimcore/Plugin_Developers_Guide/Example.md). |
| `/vendor/`  | All third part libraries are there. It's the default location for packages installed using [Composer](https://getcomposer.org/) / [Packagist](https://packagist.org/)                     |
| `/website/` | Everything regarding your individual project/application (templates, controllers, settings, objects, ...). All your code goes there.   |

## Parts of the website directory

| Directory             | Description                                                                                                        |
|-----------------------|--------------------------------------------------------------------------------------------------------------------|
| `/website/controllers` | Here you put controllers of your application.                                                                      |
| `/website/config`      | Configuration files for cache, workflow modules, DI configuration, extensions additional configuration, ...        |
| `/website/lib`         | Objects and custom libraries.                                                                                      |
| `/website/models`      | Your custom models (if needed).                                                                                    |
| `/website/var`         | This directory contains files created by Pimcore during runtime like assets, classes, thumbnails, ...              |
| `/website/views`       | Your templates.                                                                                                    |

[Next up: Overval architecture of Pimcore](./04_Architecture_Overview.md)