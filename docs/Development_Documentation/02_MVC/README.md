# MVC in Pimcore

In terms of sending output to the frontend, Pimcore follows the MVC pattern. 
Therefore it is crucial to know the fundamentals about the pattern in general and 
  the specifics in combination with Pimcore. 
 
 MVC is a software design pattern for web applications and separates the code into the following components:  
 * Model - defines basic functionality like data access routines, business, etc. 
 * View - defines what is presented to the user (the "template")
 * Controller - Controllers brings all the patterns together, they manipulate models, decide which view to display, etc. 

If you don't know the MVC pattern please read [this article](http://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) first.


The MVC module of Pimcore built on top of Symfony. If you are new to Symfony you can read about 
[controllers](http://symfony.com/doc/current/controller.html) in the Symfony manual. With this 
knowledge learning Pimcore will be much easier and faster.


## Basic file structure and naming conventions

The most common module for working within the MVC in your Pimcore project is the `AppBundle`. So the most frequently 
used folders and files concerning the MVC within the website module are the following:
 
| Path   |  Description |  Example
|--------|--------------|---------------------
| `/src/AppBundle/Controller` | The controllers directory | eg. `ContentController.php`
| `/app/Resources/views/` | The view (template) directory, the naming (sub folders and file names) follows also the naming-convention of Symfony (`/app/Resources/views/[Controller]/[action].html.php`) | `/app/Resources/views/Content/view-single.html.php` (if the controller above contains an action called `viewSingleAction`) 

All Pimcore plugins and other modules follow the same pattern.
 

The following sub chapters provide insight into details of the Pimcore MVC structure and explain the topics
 * [Controller](./00_Controller.md) 
 * [Template](./02_Template/README.md)
 * [Routing](./04_Routing_and_URLs/README.md) 
