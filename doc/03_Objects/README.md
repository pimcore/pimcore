---
title: Data Objects
description: Structured data management in Pimcore for products, categories, customers, and any business entity.
---

# Data Objects

Data Objects are the foundation of Pimcore's PIM, MDM, and CDP capabilities.
They represent structured data: products, categories, customers, suppliers, orders, or any other
business entity you need to manage.

The structure of a Data Object is defined by a **class definition**. Class definitions specify which
attributes (fields) an object has and what data types they use (text, number, date, relation,
select, and many more). Once saved, Pimcore generates the underlying database schema and PHP classes
automatically. For a high-level overview of Data Objects and how they fit alongside Documents and
Assets, see the
[Data Elements overview](https://github.com/pimcore/platform-version/blob/2026.x/doc/01_Pimcore_Overview/03_Pimcore_Data_Elements.md).

## Class Definitions

Classes are created and maintained through the visual class editor in Pimcore Studio under
**Data Management > Data Model Definitions > Classes**, no coding required. A class definition
consists of two parts:

- **Attributes** - the data fields of the object, selected from a wide range of
  [data types](./01_Object_Classes/01_Data_Types/README.md)
- **Editor layout** - how fields are organized in the object editor using
  [layout elements](./01_Object_Classes/02_Layout_Elements/README.md) (panels, tabs, fieldsets)

See [Object Classes](./01_Object_Classes/README.md) for the full documentation.

## Key Capabilities

- **Flexible data modeling** - define any entity with any combination of data types, from simple
  text fields to complex relational structures
- **Localization** - fields can be configured as localized, allowing different values per language
- **[Data inheritance](./01_Object_Classes/04_Additional_Class_Settings/03_Data_Inheritance.md)** -
  child objects inherit field values from parent objects, reducing redundancy
- **[Variants](./01_Object_Classes/04_Additional_Class_Settings/10_Object_Variants.md)** - model
  product variants (sizes, colors) as lightweight children of a parent product
- **[Classification Store](./01_Object_Classes/01_Data_Types/15_Classification_Store.md)** - handle
  dynamic, category-specific attributes without changing the class definition

## Working with Data Objects

- [Working with Data Objects via PHP API](./02_Working_with_Objects_via_PHP_API.md) - CRUD
  operations, listings, filtering, and advanced queries
- [External System Interaction](./03_External_System_Interaction.md) - importing and exporting
  data with external systems

## Getting Started

To create your first Data Object class and populate it with data, follow the
[Create a First Project](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/03_Create_a_First_Project/README.md)
tutorial.
