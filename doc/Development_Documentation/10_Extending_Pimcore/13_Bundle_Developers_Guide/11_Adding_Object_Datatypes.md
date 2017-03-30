# Adding Object Datatypes
With plugins, it is also possible to add an individual datatypes for Pimcore Objects. 
Following steps are necessary to do so: 

1) Create a php class for server-side implementation:
 This class needs to extend `Pimcore\Model\Object\ClassDefinition\Data` and defines how your data type is stored into 
  database, how the getters and setters for Pimcore objects are generated and how data is sent to and read from 
  Pimcore Admin Ui. 
   
   For examples have a look at the Pimcore core datatypes at 
   [github](https://github.com/pimcore/pimcore/tree/master/pimcore/models/Object/ClassDefinition/Data). 

   In Pimcore 4 the php class had to be in namespace `Pimcore\Model\Object\ClassDefinition\Data` to be loaded. This is 
   still possible, but not necessary any more (see below).

2) Create javascript class for Class Definition editor (object data): 
This javascript class defines the representation of the data type in the *Class Definition editor* and therefore where
it is allowed (object, objectbricks, fieldcollections, localizedfields), its group, its label, its icon and its config
options in class editor. 

   It needs to extend `pimcore.object.classes.data.data`, be located in namespace `pimcore.object.classes.data` and named after the 
   `$fieldtype` property of the corresponding php class.
     
   For examples have a look at the Pimcore core datatypes at  
   [github](https://github.com/pimcore/pimcore/tree/master/web/pimcore/static6/js/pimcore/object/classes/data)


3) Create javascript class for object editor (object tag):
This javascript class defines the representation of the data type in the *object editor* and therefore defines how data
is presented and an can be entered within Pimcore objects. 

   It needs to extend `pimcore.object.tags.abstract`, be located in namespace `pimcore.object.tags` and named after the 
   `$fieldtype` property of the corresponding php class.
     
   For examples have a look at the Pimcore core datatypes at  
   [github](https://github.com/pimcore/pimcore/tree/master/web/pimcore/static6/js/pimcore/object/tags)
   
   
4) Register datatype in Pimcore
To register the datatype in Pimcore the `pimcore.objects.class_definitions.data.map` configuration has to be extended. 
This can be done in any config file which is loaded (e.g. `app/config/config.yml`), but if you provide the datatype 
with a bundle you should define it in a configuration file which is [automatically loaded](./03_Auto_Loading_Config_And_Routing_Definitions.md). 

   Example:

```yaml
# src/AppBundle/Resources/config/pimcore/config.yml

pimcore:
    object:
        class_definitions:
            data:
                map:
                  myDataType: \AppBundle\Model\Object\Data\MyDataType
```

