# Pimcore Overview
Pimcore is the leading open source platform for managing digital data and a fully integrated software stack for CMS, DAM, PIM 
and Commerce. 

As a platform, pimcore provides a solid foundation with generic functionality for all kinds of web applications and therefore 
allows rapid application development and building customer solutions really fast. 

Its API driven approach makes it easy to develop with Pimcore, allows integration into every IT infrastructure and makes it 
easily extendable. Even headless execution of Pimcore is possible. 

Pimcore is built for developers and should empower them to build great digital experiences easily. As a consequence, Pimcore 
is NOT an ‘out-of-the-box’ software product like Wordpress, Magento, Akeneo, WooCommerce, Shopify and others. You need a developer to get started. 

![Pimcore](../img/pimcore_basis.png)


# Pimcore in a Nutshell
Our mission is to provide ONE platform for ANY data, ANY channel, ANY process and ANY one. 


## ANY Data 
In Pimcore every digital content can be managed and put in relation with each other, so we're also talking about master data management. 
There are three main element data types in Pimcore:

### [Assets](../04_Assets/README.md)
Assets are the DAM part of Pimcore. Within assets every digital file (images, videos, pdfs, …) can be stored and managed in 
a folder structure. Additionally, previews for many file types and editing functionality for some file types are available and 
assets can be enriched with meta data. 


### [Documents](../03_Documents/README.md)
Documents are the CMS part of Pimcore and can be used to manage unstructured data. Based on a template, documents can contain 
any content and can be used to create webpages, print pages or any other output format. 


### [Objects](../05_Objects/README.md)
Objects are the PIM/MDM part of Pimcore and are the way to go for managing structured data within Pimcore. Based on a class 
definition that defines the structure and attributes, objects can be used for pretty much any structured data – may it be products, 
categories, persons, customers, news, orders, blog entries, … For the attributes, many data-types (simple ones and really 
complex ones) are available.  


Most important, all elements (assets, documents or objects) can be linked with and set into relation with each other.


## ANY Channel / ANY Process
As Pimcore is a platform that stores data independently from the channel ,it can provide the managed data to any channel – simple 
websites (B2B, B2C), commerce-systems ([integrated](../10_E-Commerce_Framework/README.md), third party), mobile apps, 
print, digital signage, ... there are basically no limits. 

In terms of output to the frontend or custom APIs, Pimcore follows the MVC pattern and is based on the Symfony Framework. 
 If you don't know the MVC pattern please read [this article](http://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) 
 first.
If you are new to Symfony you should read the [getting started guide](http://symfony.com/doc/current/) of Symfony first. With this 
knowledge learning Pimcore will be much easier.

In addition to that, Pimcore can also be executed in a headless way and therefore integrated into any environment. 


## ANY One 
Pimcore provides lots of functionality on top of its basic data elements that already cover lots of use cases. 
But being a platform, Pimcore can be used for pretty much any use case and easily extended if necessary. 


-----
Wanna see more - [Let's get started](../01_Getting_Started/00_Installation.md)

#### Also Have a Look at 
* [Pimcore Ecosystem](./00_Pimcore_Ecosystem.md)
* [Develop for Pimcore](./01_Develop_for_Pimcore.md)
