# Parent Class for Objects

In addition to [dependency injection](./.md) (which can overwrite Pimcore object model classes) 
 it is also possible to make the object model classes extend a certain parent class. 
 
So this is technically not a dependency injection, but is another way to add your own methods to an object class.

To do so, define a `Parent class` in the classes' basic configuration as in the screen below. 
The class will then extend the given parent class.

![Parent Class Configuration](../img/parent-class.png)

Please make sure, that your custom class itself extends `Pimcore\Model\Object\Concrete` at some point in its class hierarchy. 
Otherwise the object class will not work. 

 
## Rule of Thumb for When Use What
Often the question occurs is when to use Dependency Injection and when use the Parent Class functionality? 

A rule of thumb is: 
* Use Parent Class functionality when Pimcore Plugins or reused libraries need to inject functionality to Pimcore classes or 
  if certain custom functionality needs to be added to more than one Pimcore object class.

* Use Dependency Injection for extensions made by the solution implementation only. 

