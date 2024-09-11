---
title: Core Framework
---

# Pimcore Core Framework Documentation

> This documentation section provides all information you need to use the Core Framework of Pimcore. 
> 
> We've aimed this part of the documentation at a developer's audience.

Pimcore provides a fully flexible and extendable platform for managing and exploiting data of any type. The Core Framework is the foundation of the whole platform and provides a lot of basic functionalities.

It gathers four major modules to answer a lot of use cases:
- Product Information (PIM) and Master Data Management (MDM)
- Digital Asset Management (DAM)
- Enterprise Content Management (CMS/UX)
- B2C and B2B E-commerce Framework

The system is written in PHP, follows the Model-View-Controller (MVC) pattern and relies on the Symfony Framework.

Pimcore provides the management of three types of elements that cover any kind of data: Documents, Assets and Objects. Following the principle of single-source publishing, each type is saved only once with a single ID that serves as a reference ID whenever it is reused somewhere.

The Core Framework comes with several core features that can be fully adapted or extended with additional bundles (see the Pimcore Extensions section for existing extensions maintained by Pimcore).

## Documentation Overview

The Core Framework documentation is divided into three sections that aim to guide the reader through its first use of the platform:

* See the [Getting Started](#getting-started) section for an overview of the Core Framework or information about the installation process and the MVC pattern integration within Pimcore.
* See the [Element Types](#element-types) section for details about managed elements in Pimcore and associated actions.
* See the [Platform Topics](#platform-topics) section for documentation about all features implemented within Pimcore.

### Getting Started
* [Overview](./00_Overview/README.md) 
* [Getting Started](./01_Getting_Started/README.md) 
* [MVC](./02_MVC/README.md) 

### Element Types
* [Documents - *Managing Web Pages*](./03_Documents/README.md) 
* [Assets - *Media Library / Digital Asset Management*](./04_Assets/README.md) 
* [Objects - *Custom Data Models / Entities, PIM / MDM*](./05_Objects/README.md) 

### Platform Topics
* [Multilanguage & Localization](./06_Multi_Language_i18n/README.md) 
* [Workflow Management](./07_Workflow_Management/README.md) 
* [Tools & Features](./18_Tools_and_Features/README.md) 
* [Development Tools & Details](./19_Development_Tools_and_Details/README.md) 
* [Extending & Advanced Topics](./20_Extending_Pimcore/README.md) 
* [Deployment](./21_Deployment/README.md) 
* [Administration](./22_Administration_of_Pimcore/README.md) 
* [Installation & Upgrade](./23_Installation_and_Upgrade/README.md) 


## Additional resources for getting started with Pimcore
- [Pimcore Demo Application](https://demo.pimcore.fun): See Pimcore in action and also use it as a blueprint application
  for your own implementations.
- [Pimcore Academy](https://pimcore.com/academy): The training platform Pimcore Academy offers on-demand video courses
  about many Pimcore topics. 
