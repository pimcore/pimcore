# Plugins Developers Guide

Plugins are the most advanced way but also the most complex way of extending Pimcore. 

With plugins several things can be archived - they can be just a library of reuseable code 
components, they can utilize [Pimcores event API](../11_Event_API_and_Event_Manager.md) to
extend backend functionality and they can modify and  extend the Pimcore Backend UI by utilizing
Javascript user interface hooks. 

The following sections explain how to design and structure plugins and how to 
register for and utilize the events provided in the PHP backend and the Ext JS frontend.

* [Plugin Anatomy](./01_Plugin_Anatomy.md) to getting started with building plugins.
* [Plugin_Class](./03_Plugin_Class.md) is the starting point for each plugin.
* [Plugin_Backend_UI](./05_Plugin_Backend_UI.md) for extending the Pimcore Backend UI with Javascript. 

In addition to these topics also have a look at the [Example](./07_Example.md) provided in 
the documentation. 

Additional aspects in plugin development are 
* [Adding Document Editables](./11_Adding_Document_Editables.md)
* [Working with ExtJS6 and ExtJS3](09_ExtJS6_and_ExtJS3.md)