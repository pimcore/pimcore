---
title: Core Framework
---

# Pimcore Core Framework Documentation

> This documentation section provides all information you need to use the Core Framework of Pimcore. 
> 
> We've aimed this part of the documentation at a developer's audience.

Pimcore provides a fully flexible and extendable platform for managing and exploiting data of any type. The Core Framework is the foundation of the whole platform and provides a lot of basic functionalities.

The system is written in PHP, follows the Model-View-Controller (MVC) pattern and relies on the Symfony Framework.

Pimcore provides the management of three types of elements that cover any kind of data: Documents, Assets and Objects. Following the principle of single-source publishing, each type is saved only once with a single ID that serves as a reference ID whenever it is reused somewhere.

The Core Framework comes with several core features that can be fully adapted or extended with additional bundles (see the Pimcore Extensions section for existing extensions maintained by Pimcore).

## Documentation Overview

The Core Framework documentation is divided into three sections that aim to guide the reader through its first use of the platform:

* See the [Element Types](#element-types) section for details about managed elements in Pimcore and associated actions.
* See the [Platform Topics](#platform-topics) section for documentation about all features implemented within Pimcore.

### Element Types
* [Documents - *Managing Web Pages*](./01_Documents/README.md)
* [Assets - *Media Library / Digital Asset Management*](./02_Assets/README.md)
* [Objects - *Custom Data Models / Entities, PIM / MDM*](./03_Objects/README.md)

### Platform Topics
* [Multilanguage & Localization](./04_Multi_Language_i18n/README.md)
* [Content Management Features](./05_Content_Management_Features/README.md)
* [Reporting](./06_Reporting/README.md)
* [Workflow Management](./07_Workflow_Management/README.md)
* [Development Details](./08_Development_Details/README.md)
* [Development Tools](./09_Development_Tools/README.md)
* [Extending Pimcore](./10_Extending_Pimcore/README.md)
* [Deployment Recommendations](./11_Deployment_Recommendations/README.md)
* [Implementation Inspirations](./12_Implementation_Inspirations/README.md)
* [Upgrade Notes](./13_Upgrade_Notes/README.md)


## Additional resources for getting started with Pimcore
- [Pimcore Demo Application](https://demo.pimcore.fun): See Pimcore in action and also use it as a blueprint application
  for your own implementations.
- [Pimcore Academy](https://pimcore.com/academy): The training platform Pimcore Academy offers on-demand video courses
  about many Pimcore topics. 
