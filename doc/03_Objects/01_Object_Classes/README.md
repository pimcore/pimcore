---
title: Object Classes
description: Defining data structure and editor layout for Pimcore data objects.
---

# Object Classes

Object classes define the structure and editor layout of Data Objects. To get started, follow the
[Create a First Project](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/03_Create_a_First_Project/README.md)
tutorial for a hands-on introduction.

## Defining a Class

A class definition consists of two parts:

- **Attributes** - the data fields of the object
- **Editor layout** - how fields are organized in the object editor

Layout elements can be grouped into panels and placed into tab panels. This allows logical
structuring of object attributes into smaller units of related data. Common groupings include
tabs for logical groups like basic data, media, and sales data.

In addition to the main editor layout,
[Custom Layouts](./03_Custom_Layouts.md) can define alternative views on the object data.

## Creating a Class

To define a class, navigate to **Data Management > Data Model Definitions > Classes** in Pimcore
Studio. The class name must be a valid PHP class name. After creating a new class, build its
attributes and layout.

Use the `title` field in class definitions to add a translation key (e.g., `app.classes.product`)
for use in your translation files.

## Data Types and Layout

Class attributes are selected from a set of predefined data types. Each data type defines the type
of data (text, number, image, reference to another object, etc.) and provides a corresponding
input widget in the editor (text field, drop area for images, etc.).

- [Data Types](./01_Data_Types/README.md) - all available data types and their configuration
- [Layout Elements](./02_Layout_Elements/README.md) - panels, tabs, fieldsets, and other layout
  components
- [Custom Layouts](./03_Custom_Layouts.md) - alternative editor views on the same data
- [Additional Class Settings](./04_Additional_Class_Settings/README.md) - inheritance, variants,
  custom icons, link generators, and more
- [Object Bricks vs Classification Store](./05_Object_Bricks_vs_Classification_Store.md) -
  choosing between the two approaches for extensible class definitions
