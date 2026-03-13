# Objects

Objects are the PIM part of Pimcore and the primary way to manage structured data. Based on a class definition that defines structure and attributes, objects can be used for virtually any structured data - products, categories, persons, customers, news, orders, blog entries, and more. Many data types are available for attributes, from simple to complex.

Pimcore objects are literally objects in the sense of object-oriented programming. The class definition can be configured through a user friendly graphical user interface (GUI), but in the background a plain PHP class is created, which can use inheritance and be utilized within your custom PHP code. Data objects can be instantiated and filled within Pimcore backend or within your custom code by using the PHP API and common programming paradigms (creating new instances, using getters and setters, etc.). For saving objects, call the `save` method and Pimcore takes care of the rest. This also makes it straightforward to integrate objects from external systems like CRM, ERP, PIM or asset management systems.

This chapter describes the following aspects of objects from a technical point of view:
 * [Object Classes](./01_Object_Classes/README.md)
 * [Working with Objects via PHP API](./02_Working_with_Objects_via_PHP_API.md)
 * [External System Interaction](./03_External_System_Interaction.md)
