# Parent Class for Objects

In addition to [overriding model classes](./03_Overriding_Models.md) 
it is also possible to make the object model classes extend a certain parent class. 
 
To do so, define a `Parent class` in the classes' basic configuration as in the screen below. 
The class will then extend the given parent class.

![Parent Class Configuration](../img/parent-class.png)

Please make sure, that your custom class itself extends `Pimcore\Model\Object\Concrete` at some point in its class hierarchy. 
Otherwise the object class will not work. 

