![Pimcore - Own the digital World](./doc/Development_Documentation/img/logo-readme.svg)
  
  
Pimcore - Open Source Digital Experience Platform: MDM/PIM, CDP, DAM, CMS/UX & eCommerce

[![Packagist](https://img.shields.io/packagist/v/pimcore/pimcore.svg)](https://packagist.org/packages/pimcore/pimcore)
[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat)](LICENSE.md)
[![Gitter](https://img.shields.io/badge/gitter-join%20chat-brightgreen.svg?style=flat)](https://gitter.im/pimcore/pimcore)

* 🌍 [Website](https://pimcore.com/) - Learn more about Pimcore
* 📖 [Documentation](https://pimcore.com/docs/)
* 🉐 **Help translating Pimcore!** Start with [Essentials](https://poeditor.com/join/project/VWmZyvFVMH), continue with [Extended](https://poeditor.com/join/project/XliCYYgILb).
* 👍 Like us on [Facebook](https://www.facebook.com/pimcore)
* 🕊 Twitter: [@pimcore](https://twitter.com/pimcore) - Get the latest news
* 🐞 [Issue Tracker](https://github.com/pimcore/pimcore/issues) - Report bugs or suggest new features
* 🗨  [Forums](https://github.com/pimcore/pimcore/discussions) - Community support and discussions
* 👪 [Community Chat](https://gitter.im/pimcore/pimcore) - Gitter
  

## Contribute  
**Bug fixes:** please create a pull request including a step by step description to reproduce the problem  
**Contribute features:** contact the core-team on our [Gitter channel](https://gitter.im/pimcore/pimcore) before you start developing   
**Security vulnerabilities:** please use [this form](https://pimcorehq.wufoo.com/forms/pimcore-security-report/)
  
For details, please have a look at our [contributing guide](CONTRIBUTING.md).

## Supported Versions

| Version | Supported | LTS** | LTS Version |
| ------- |    :---:  | :---: |    :---:    |
| `<= 4.x`|    ❌     |  ❌    |             |
| `5.x`   |    ❌     |  ✅    | `5.8`       |
| `6.x`   |    ✅     |  ✅    |             |

** [LTS](https://pimcore.com/en/services/lts) is only available as part of our [enterprise subscription](https://pimcore.com/en/platform/subscription). 

## Overview
![Technology and Architecture](./doc/Development_Documentation/img/pimcore-technology-architecture.svg)

## Key Benefits and Advantages
### ⚒ Data Modelling and UI Design at the same Time 
No matter if you're dealing with unstructured web documents or structured data for MDM/PIM, you define the 
UI design (web documents by a template and structured data with a intuitive graphical editor), Pimcore knows 
how to persist the data efficiently and optimized for fast access.

### 🎛 Agnostic and Universal Framework for your Data
Due to the framework approach, Pimcore is very flexible and adapts perfectly to your needs. Built on top of 
the well-known Symfony Framework you have a solid and modern foundation for your project. 

### 🚀 Extensible and huge Symfony Community
Benefit from all existing Symfony Components and Bundles provided by the community or create your own 
Bundles to extend your Projects with reusable components. 

### 💎 Your Digital World consolidated in one Platform
No more API, import/export and synchronization hell between MDM/PIM, E-Commerce, DAM, and your Web-CMS. 
All is working seamlessly together, natively ... this is what Pimcore is built for. 

### ✨️ Modern and Intuitive UI
We love good looking user interfaces, designed to be efficient for the daily use and optimized for a great
experience for editors. 

## Preview and Demo
### Documents
![Pimcore Admin Interface Screenshot CMS](./doc/Development_Documentation/img/pimcore-screenshot-1.png)
The CMS part of Pimcore for managing unstructured content such as the pages of a website and its navigation. Physical HTML/CSS pages which are displayed in the browser. Documents can be filled with various content areas, which consist of predefined layout elements. Pimcore documents provide multi-lingual capabilities and powerful features for managing multiple websites at once. Full frontend flexibility enables a complete blend of content and commerce.
### Digital Assets
![Pimcore Admin Interface Screenshot DAM](./doc/Development_Documentation/img/pimcore-screenshot-2.png)
Images, videos, PDF, Word/Excel documents and other files can be managed and organized into folders. Pimcore renders preview images for more than 200 file types. An integrated image editor is included. Facial recognition for focal points in images and support for VR/360° previews is integrated. Editors maintain images only once at the highest resolution in the system. The output formats for channels such as online shop, app, website, etc. are automatically created.
### Data Objects
![Pimcore Admin Interface Screenshot PIM/MDM](./doc/Development_Documentation/img/pimcore-screenshot-3.png)
Predefined structured data, which is centrally managed and created either manually or automatically via the API. Used for products & attributes (MDM/PIM), customers (CDP), blog articles (WCM), orders (digital commerce), and so much more. Objects can be used to fill content areas and elements of a website, portal or app with data from one central source. Single source administration of data ensures a consistent, up-to-date digital customer experience with little effort.

#### Demo (MDM/PIM, E-Commerce, DAM, CMS, ...)
_Admin-URL_ (stable): [https://demo.pimcore.fun/admin/](https://demo.pimcore.fun/admin/)  
_Admin-URL_ (dev): [https://x.pimcore.fun/admin/](https://x.pimcore.fun/admin/)  
_Username_: `admin`  
_Password_: `demo`

## Getting Started
_**Only 3 commands to start!**_ 😎
```bash
COMPOSER_MEMORY_LIMIT=-1 composer create-project pimcore/skeleton ./my-project
cd ./my-project
./vendor/bin/pimcore-install
```

This will install an empty skeleton application, 
but we're also offering a demo package for your convenience - of course also with 3 commands 💪
[Click here for more installation options and a detailed guide](https://pimcore.com/docs/pimcore/current/Development_Documentation/Getting_Started/Installation.html)

## Copyright and License 
Copyright: [Pimcore](https://www.pimcore.org) GmbH
For licensing details please visit [LICENSE.md](LICENSE.md)
