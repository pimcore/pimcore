# Extending Pimcore

When building solutions with Pimcore, normally one starts with configuring an object data model, 
create custom controller, actions and templates, creating documents and so on. Lots of things 
are possible without extending Pimcore itself. 
But depending on the desired result, sometimes it is necessary to extend the default functionality
of Pimcore. 

Pimcore provides several ways for extension for that purpose. Depending on the use case one or the other
way may fit best. 

Following a list of ways to extend Pimcore. See detail pages for additional information: 

* [**Add your own Dependencies and Packages**](./02_Add_Your_Own_Dependencies.md) for loading external libraries and functionalities 
 to be available in custom code. 
 
* Use [**Overriding Models**](./03_Custom_Extension_Guides/08_Overriding_Models.md) to overwrite Pimcore default models.

* [**Configuration**](../08_Development_Details/01_Configuration.md) regarding configuration (e.g. overwriting Pimcore constants
 like assets directory, temporary directory etc.)

* [**Parent Class for Objects**](../03_Objects/01_Object_Classes/04_Additional_Class_Settings/03_Data_Inheritance_and_Parent_Class/01_Parent_Class_for_Objects.md) to inject additional functionality
 to Pimcore object classes.

* [**Event API and Event Manager**](./01_Events/README.md) for hooking into standard
 Pimcore functions like creating, updating, deleting elements etc.

* Use [**Maintenance Mode**](../09_Development_Tools/09_Maintenance_Mode.md) to show users a maintenance page when
 changing system configurations. You also can create a custom maintenance page.

* Use [**Maintenance Task**](./03_Custom_Extension_Guides/07_Maintenance_Tasks.md) to register new maintenance task

* Add [**Custom Persistent Models**](./03_Custom_Extension_Guides/09_Custom_Persistent_Models.md) to save additional information.

* [**Create Bundles and Pimcore Bundles**](./04_Pimcore_Bundle_Developers_Guide/README.md) when you want to add complex and extensive functionalities to Pimcore.
