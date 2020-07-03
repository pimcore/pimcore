# Architecture Overview

The following architecture overview shows available framework components. The component configuration takes place in 
the `pimcore_ecommerce_framework` configuration node and a factory is responsible for resolving of the implementations. 
For each component a custom implementation can be configured and used. 

![bigpicture](../img/ecommerce-architecture.png)

# Separation of Concerns
We try to separate different functionality and encapsulate them as good as possible in the different components. 
Following list should state the basic ideas and represent a guideline for future extensions: 

## Index Service
The Index Service is responsible for all aspects concerning indexing products and providing an API to access product 
data from the index. 

These aspects contain among other things: 
* Defining which attributes should be indexed.
* Implementations for different search backends (e.g. mysql, elasticsearch, fact finder, findologic).
* *Product Lists* as the API to access the product data stored in the search index. *Product Lists* can be used 
  by controllers to display product listings.


## Filter Service
The Filter Service is responsible for all aspects concerning filtering products and setting up a filtered navigation. 
 It is tightly coupled with the *Product Lists* of the Index Service, provides the developer a simple API for building
 a layered navigation and takes care of query generation, counting results and stuff.  


## Availability System
The *Availability System* is responsible for all aspects concerning product availability. Here the logic for calculating 
availabilities, calling external availability services, etc. is encapsulated. Each product has an *Availability System* 
assigned and when it comes to getting the availability of a product, this should be delegated to the *Availability 
System* of the product.  

## Price System and Taxes
Like *Availability Systems*, there are *Price Systems* which are responsible for all aspects of getting product prices. 
Here the logic of calculating the product prices should be encapsulated. Each product has a *Price System* and when it 
comes to getting the price of a product, this should be delegated to the *Price System* of the product.

Also *Tax Management* is located in this component. 


## Pricing Manager
In addition to the *Price System*, there is the Pricing Manager which is responsible for all aspects concerning 
modification of prices for marketing purposes. Therefore pricing rules (e.g. certain products, certain time frames, ...)
build conditions which need to be valid that certain pricing actions (e.g. product price discount, free shipping, ...) 
are executed. 


## Voucher System
The Voucher System is responsible for all aspects concerning vouchers for customers. These aspects contain among 
other things:

* Voucher Services
* Voucher Tokens
* Voucher Statistics
* Modification of product prices based on vouchers which is done with the help of the Pricing Manager. 


## Cart Manager
The Cart Manager is responsible for all aspects concerning carts. These aspects contain among other things:

* Different cart implementations
* CRUD-Operations on carts
* Price calculations for carts 
* Price modifications for carts (like shipping costs, etc.) 


## Checkout Manager
The Checkout Manager is responsible for all aspects concerning checkout process and an one-stop API for getting 
through the checkout process. The Checkout Manager always works with a cart and at certain points in the checkout 
process it delegates to other components to convert the cart to an order object (Order Manager), start payment 
operations (Commit Order Processor and Payment Manager) and commits the order (Commit Order Processor). 

### Commit Order Processor
The Commit Order Processor is part of the Checkout Manager namespace and is responsible for all aspects concerning 
committing and order. 

These aspects contain among other things:
* Handle payment response
* commit order payment
* commit order and send order to other backend systems (e.g. erp system) 

In contrast to the Checkout Manager, the Commit Order Processor does not work with carts anymore and can be 
initialized without a cart. Therefore it also can be used to commit orders in combination with server side payment 
notifications when no cart is available anymore. 


## Payment Manager
The Payment Manager is responsible for all aspects concerning payment. The main aspect is the implementation
of different Payment Providers to integrate them into the framework. 


## Order Manager 
The Order Manager is responsible for all aspects of working with orders except committing them (which is the 
responsibility of the Commit Order Processor). These aspects contain among other things:
* Creating orders based on carts
* Order Storage
* Loading orders 
* Loading order lists and filter them (Order List)
* Working with orders after order commit (Order Agent) 
