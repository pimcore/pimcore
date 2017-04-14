# E-Commerce Framework

## Why Pimcore E-Commerce Framework
The e-commerce environment has fundamentally changed and e-commerce projects often face new challenges like these: 
 - complex product structures
   - lots of data attributes
   - complex product relations
   - configurable product systems
   - different sources for products
 - complex pricing structures
   - product dependent price sources
   - tier pricing
   - pricing matrices
   - individual pricing rules
   - integration of remote pricing services
 - complex availability calculations
 - thousands customer groups with
   - customer group specific prices
   - customer group specific assortments
 - individual checkouts
   - individual integration of backend systems
   - integration of multi-channel processes into checkout workflow
 - individual design
 - highly agile projects with changing requirements
   - 'fail fast'
   - fast changing environments and requirements
 - etc. 
 
We think for these challenges a default shop system that pops out of a box, that has a fixed product data model and 
workflows and a template based frontend and needs to be connected and integrated with other systems via interfaces 
is not the tool to go for. 

We think for these challenges you need...
- a framework for developers to build outstanding e-commerce solutions for customers
- with a native integration into Pimcore
- with a component based architecture
- and a strict separation between backend functionality and frontend presentation. 

That is the idea behind the E-Commerce Framework of Pimcore. Like Pimcore it self it is not a ready made system,
it is a set of tools and functionality to help building e-commerce applications really fast and flexible. 

 
## Provided Functionality in a Nutshell 
- Tools for indexing, listing, searching and filtering products 
- Implementations of carts, wish lists, comparison lists, etc.
- Concepts for flexible and complex pricing, taxes and availability functionality 
- Functionality and tools for implementing checkout processes
- Pricing Rules and Vouchers
- Tools for working with and managing Orders
- Concepts for setting up multi tenant and multi shop solutions

For a first impression have a look at our [E-Commerce Demo](http://ecommercedemo.pimcore.org). For more complex solutions
have a look at our [case studies](https://www.pimcore.org/en/resources/casestudies). 


## Working With E-Commerce Framework
 
Following aspects are short cuts into the documentation for starting working with the E-Commerce Framework: 

- [Architecture Overview](./01_Architecture_Overview.md)
- [Installation and Configuration](./03_Installation.md)
- [Indexing and Listing Products](./05_Indexing_And_Listing_Products/README.md)
- [Filtering Products](./07_Filter_Service.md)
- [Working with Prices](./09_Working_with_Prices/README.md)
- [Working with Carts](./11_Cart_Manager.md)
- [Setting up Checkouts](./13_Checkout_Manager/README.md)
- [Integrating Payment Functionality](./15_Payment/README.md)
- [Working with Orders](./17_Order_Manager/README.md)
- [Tracking Manager](./19_Tracking_Manager.md)


## Migration from former E-Commerce Framework Plugin
If you are migrating a project from the former E-Commerce Framework Plugin have a look at the migration notes. 
