# Architecture Overview

At this point we want to give a short overview of the architecture of Pimcore. 

As usual, picture tells more than thousand words:  

![Pimcore Architekture](../img/architectural-chart.png) 

This charts shows the architecture of a typical Pimcore application. Every 
thing in blue is shipped directly with Pimcore or a integral part of it. 
The other components are printed in different colors.

Pimcore itself consits of the Pimcore Core and the MVC component. 
The Pimcore Core is the core application, which provides all the basic
functionality and and be started within the MVC component or in a headless way, for example via CLI scripts.
The Pimcore Core is also responsible or accessing the persistence layer with Database, Filesystem and Cache-System. 

Build upon the Pimcore Core, the MVC component provides all the necessary 
functionalities for interacting with Pimcore via the Browser or any other 
API client.
 
Plugins and other Custom Modules can be added via Composer and complement the
Pimcore functionality with custom extensions and additional functions. 
Of course Plugins and Custom Modules can access the Pimcore Core functionality 
via its API and also can be used by the MVC component. 

When implementing solutions with Pimcore, your custom parts should go into following locations within the
 total architecture: 

 * Apps/Website within the MVC component: Here go all the solution specific implementations 
 like all controllers, views, models for your website. 
 * Plugins, Custom Modules: Here go all implementations and modules you might want to reuse 
 with other solutions. 
 
  
For more detailed information about the architecture also have a look at the
[architecture page](../09_Development_Tools_and_Details/01_Architecture_Overview.md)
in the Development Tools and Details section. 

