# Pimcore E-Commerce Framework

The pimcore E-Commerce Framework extension is a general framework that adds advanced e-commerce functionalities to pimcore.
It's most important features are:
- Product Index, Product Listings and Filter Services
- Functionality for implementing carts
- Functionality for implementing checkout processes
- Functionality for working with prices and availabilities 
- Functionality for implementing pricing systems
- Functionality for working with orders

### Documentation Chapters: 
- [Design Principles](doc/Design-Principles.markdown)
- [Big Picture](doc/Big-Picture.markdown)
- [Getting Started](doc/Getting-Started.markdown)

### Please also have a look at the Update Notices: 
[Update Notice](doc/update-notices.markdown)


### Quick install
Add the following to your `composer.json`
```json
  "require": {
    "pimcore-partner/ecommerce-framework": "*"
  },
  "repositories": [
    { "type": "vcs", "url": "https://github.com/pimcore-partner/ecommerce-framework" },
    { "type": "vcs", "url": "https://github.com/pimcore-partner/Elements_OutputDataConfigToolkit" }
  ],
```
Run `composer update` 

### E-Commerce Framework Demo

The e-commerce framework demo implementation can be downloaded at https://www.pimcore.org/download/pimcore-ecommerce-demo.zip. 
This demo is always based on the latest build of pimcore. 
> Database user needs the permission FILE in order to install the demo. 
