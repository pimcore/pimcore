## General

Find basic information about directories tree in Pimcore project, below. 

| Directory                                          | Description                                                                        |
|-- ------------------------------------------------ | -----------------------------------------------------------------------------------|
| ![Pimcore directory](/img/Directories_pimcore.png) | Here is Core of Pimcore, do not change anything here.                              |
| ![Plugins directory](/img/Directories_plugins.png) | Directory for [extensions](!Getting_Started/Create_Extension).                               |
| ![Vendor directory](/img/Directories_vendor.png)   | All third part libraries are here. For example Zend libraries.                     |
| ![Website directory](/img/Directories_website.png) | Everything concerning the website (templates, controllers, settings, objects, ...) |

## Parts of the website directory

| Directory           | Description                                                                                                        |
|---------------------|--------------------------------------------------------------------------------------------------------------------|
| website/controllers | Here you put controllers of your application.                                                                      |
| website/config      | Configuration files for cache, workflow modules, DI configuration, extensions additional configuration, ...        |
| website/lib         | Objects and custom libraries.                                                                                      |
| website/models      | Your custom models (if needed).                                                                                    |
| website/var         | This directory contains files created by Pimcore during runtime like assets, classes, thumbnails, ...              |
| website/views       | Your templates.                                                                                                    |

[Next part: Documents, Assets, Objects](!Getting_Started/Tutorial)