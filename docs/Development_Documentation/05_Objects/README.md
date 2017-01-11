# Objects

Objects are the PIM part of Pimcore and are the way to go for managing structured data within Pimcore. Based on a class 
definition that defines structure and attributes object can be used for pretty much any structured data – may it be 
products, categories, persons, customers, news, orders, blog entries, … For the attributes many datatypes (simple ones 
and really complex ones) are available.

Pimcore objects are literally objects in the sense of object oriented programming. The class definition can be defined 
through a user friendly graphical user interface (GUI), but nevertheless in the background a plain php class is created, 
which can profit from inheritance and can be utilized and accessed within your custom php code. 
So managing data becomes really easy. Data objects can be instantiated and filled within Pimcore backend or within your
custom code by using the PHP API and common programming paradigms (create new instances, using getter and setter, ...).
 For saving objects just call the `save`` method and Pimcore takes care of the rest. 
 So it is also really eays to serve object from external systems like CRM, ERP, PIM or asset management systems.
 

This chapter describes following aspects of objects from a technical point of view: 
 * [Object Classes](./01_Object_Classes/README.md) 
 * [Working with Objects via PHP API](./03_Working_with_PHP_API.md)
 * [External System Interaction](./05_External_System_Interaction.md)

For all the provided backend functionality within Pimcore have a look at the User Documentation of Pimcore.

[comment]: #(TODO add link)

 










