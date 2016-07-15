## General

Find basic information about directories tree in Pimcore project, below. 

| Directory                                          | Description                                                                        |
|-- ------------------------------------------------ | -----------------------------------------------------------------------------------|
| ![Pimcore directory](/img/Directories_pimcore.png) | Here is Core of Pimcore, it's not a right place to change anything.                |
| ![Plugins directory](/img/Directories_plugins.png) | Directory for [extensions](!Start/Create_Extension).                               |
| ![Vendor directory](/img/Directories_vendor.png)   | All third part libraries are here. For example Zend libraries.                     |
| ![Website directory](/img/Directories_website.png) | Everything concerning the website (templates, controllers, settings, objects, ...) |
| ![Tests directory](/img/Directories_tests.png)     | Unit tests                                                                         |

## Parts of the website directory

| Directory           | Description                                                                                                        |
|---------------------|--------------------------------------------------------------------------------------------------------------------|
| website/controllers | Here you put controllers to your application.                                                                      |
| website/config      | Configuration files for cache, workflow modules, class map configuration, extensions additional configuration, ... |
| website/lib         | Objects and custom libraries.                                                                                      |
| website/models      | Your custom models (if needed).                                                                                    |
| website/var         | This directory contains Pimcore files like assets, classes, cache, ...                                             |
| website/views       | Your templates.                                                                                                    |

[Next part: Documents, Assets, Objects](!Start/Pimcore_Elements)