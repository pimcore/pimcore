## General

Find basic information about directories tree in Pimcore project, below. 

| Directory                                                                    | Description                                                                                               |
|-- ------------------------------------------------                           | ---------------------------------------------------------------------------------                       --|
| ![Pimcore directory](/Development_Documentation/img/Directories_pimcore.png) | Here is Core of Pimcore, do not change anything here.                                                     |
| ![Plugins directory](/Development_Documentation/img/Directories_plugins.png) | Directory for [extensions](!Development_Documentation/Extending_Pimcore/Plugin_Developers_Guide/Example). |
| ![Vendor directory](/Development_Documentation/img/Directories_vendor.png)   | All third part libraries are here. For example Zend libraries.                                            |
| ![Website directory](/Development_Documentation/img/Directories_website.png) | Everything concerning the website (templates, controllers, settings, objects, ...)                        |

## Parts of the website directory

| Directory           | Description                                                                                                        |
|---------------------|--------------------------------------------------------------------------------------------------------------------|
| website/controllers | Here you put controllers of your application.                                                                      |
| website/config      | Configuration files for cache, workflow modules, DI configuration, extensions additional configuration, ...        |
| website/lib         | Objects and custom libraries.                                                                                      |
| website/models      | Your custom models (if needed).                                                                                    |
| website/var         | This directory contains files created by Pimcore during runtime like assets, classes, thumbnails, ...              |
| website/views       | Your templates.                                                                                                    |

[Next part: Documents, Assets, Objects](./06_Create_A_First_Project.md)