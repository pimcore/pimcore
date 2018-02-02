# Object Classes

To get started with Pimcore objects, classes must be defined. 
In [Create A First Project](../../01_Getting_Started/06_Create_a_First_Project.md) you can see the first steps how to 
create objects and classes.

Defining a class consists of two parts: 
* defining the attributes of the object and 
* defining the layout for the object editor. 

Layout object properties can be grouped into panels, which incorporate the layout areas north, east, west, south and 
center and additionally they can be positioned into tab panels. This allows logical structuring of object attributes 
into smaller units of data belonging together. It depends on the use case how data should be grouped and structured.
Common applications are tabs/groups for different languages or logical groups like basic data, media, sales data, etc.
In addition to the master editor layout, Custom Layouts for different views on the object data can be defined. 

To define a class, the menu `Settings` -> `Objects` -> `Classes` needs to be used in the Pimcore toolbar menu. 
The class name has to be a valid PHP class name. After creating a new class, the class attributes and layout can be built.

Class attributes are defined from a set of predefined data types. 
These data types define not only the type of data such as text, number, image, reference to another object etc. but 
also how data input can be achieved and how data is accessed. 

Each data type comes with an input widget. For instance, the text input data type comes with a simple text field, the 
image data type comes with a drop area to which a user can drag and drop an image.
 
For detailed documentation about these data types see [Data Types](./01_Data_Types/README.md).
Also have a look at [Layout Elements](./03_Layout_Elements/README.md) for information about the layout elements 
and [Class Settings](./05_Class_Settings/README.md) for further details and features of Pimcore classes like 
inheritance, variants, preview, custom layouts, etc.   
