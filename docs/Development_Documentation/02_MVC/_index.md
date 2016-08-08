# MVC in Pimcore

In terms of output to the frontend, Pimcore follows the MVC pattern. 
Therefore it is crucial to know the fundamentals about the pattern in general and 
  the specifics in combination with pimcore. 
 
 MVC is something like a standard in the design of web applications and tries
 to separate the code into 
 * Model - defines basic functionality like data access routines, business, etc. 
 * View - defines what is presented to the user.
 * Controller - Controllers bind the whole pattern together, they manipulate models, decide which view to display, etc. 

If you don't know the MVC pattern please read [this article](http://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) first.


The MVC module of Pimcore is based on Zend Framework. If you are new to the Zend Framework or the ZF MVC you can read about 
[controller](http://framework.zend.com/manual/1.12/en/zend.controller.html) in Zend Framework manual. With this 
knowledge learning Pimcore will be much easier and faster.


## Basic file structure and naming conventions

The most common module for working within the MVC is the website module. So the most frequently used  
folders and files concerning the MVC within the website module are the following:
 
| Path   |  Description |  Example
|--------|--------------|---------------------
| ```/website/models``` | Place for all the specific models of your application. Please keep in mind, that pimcore Objects are located at an other place | 
| ```/website/controllers``` | The controllers directory, the naming follows the Zend Framework naming-convention. | ```ContentController.php```
| ```/website/views/scripts``` | The view (template) directory, the naming (sub folders and file names) follows also the naming-convention of ZF (```/website/views/scripts/[controller]/[action].php```) | ```/website/views/scripts/content/view-single.php``` (if the controller above contains an action called ```viewSingleAction```) 
| ```/website/views/layouts``` | Optionally: here you can put your layouts which are used by pages | ```layout.php``` (this is the default when enabled)

But all Pimcore plugins and other modules follow the same schema and you will always find the folders ```models```, ```controllers``` and ```scripts```. 
 

The following sub chapters provide insight into details of the Pimcore MVC structure and explain the topics
 * [Controller](./00_Controller.md) 
 * [Template](./02_Template/_index.md)
 * [Routing](./04_Routing_and_URLs/_index.md) 
