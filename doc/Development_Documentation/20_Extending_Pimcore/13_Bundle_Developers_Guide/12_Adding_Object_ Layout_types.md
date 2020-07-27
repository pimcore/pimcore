# Adding Object Layout types

> Note: This feature is available since v6.6.1 

With plugins, it is also possible to add individual layout types for Pimcore Objects. 
Following steps are necessary to do so: 

1) Create a PHP class for server-side implementation:
   This class needs to extend `Pimcore\Model\DataObject\ClassDefinition\Layout` and defines which settings your data type has and how it is read for the Pimcore Admin Ui.
   
   For examples have a look at the Pimcore core layout types at 
   [github](https://github.com/pimcore/pimcore/tree/master/models/DataObject/ClassDefinition/Layout). 

2) Create JavaScript class for Class Definition editor: 
   This JavaScript class defines the config options in class editor. 

   It needs to extend `pimcore.object.classes.layout.layout`, be located in namespace `pimcore.object.classes.layout` and named after the 
   `$fieldtype` property of the corresponding PHP class.
     
   For examples have a look at the Pimcore core layout types at  
   [github](https://github.com/pimcore/pimcore/tree/master/bundles/AdminBundle/Resources/public/js/pimcore/object/classes/layout)

3) Create JavaScript class for object editor:
   This JavaScript class defines the representation of the layout type in the *object editor*. You can use very simple ExtJS elements here.

   It needs to extend `pimcore.object.abstract`, be located in namespace `pimcore.object.layout` and named after the 
   `$fieldtype` property of the corresponding PHP class.
     
   For examples have a look at the Pimcore core layout types at  
   [github](https://github.com/pimcore/pimcore/tree/master/bundles/AdminBundle/Resources/public/js/pimcore/object/layout)
    
4) Register a layout type in Pimcore by extending the `pimcore.objects.class_definitions.data.layout` configuration. 
   This can be done in any config file which is loaded (e.g. `app/config/config.yml`), but if you provide the layout type 
   with a bundle you should define it in a configuration file which is [automatically loaded](./03_Auto_Loading_Config_And_Routing_Definitions.md). 

   Example:
    ```yaml
    # src/AppBundle/Resources/config/pimcore/config.yml
    
    pimcore:
        objects:
            class_definitions:
                Layout:
                    map:
                      myLayoutType: \AppBundle\Model\DataObject\ClassDefinition\Layout\MyLayoutType
    ```

5) Add your layout type in the context menu of the Class Definition editor.
   You can do this via the JavaScript UI events.

   Here is an example:
   ```javascript
   // src/AppBundle/Resources/public/js/MyTestBundle.js
   
   pimcore.registerNS("pimcore.plugin.MyTestBundle");
   
   pimcore.plugin.MyTestBundle = Class.create(pimcore.plugin.admin, {
       getClassName: function () {
           return "pimcore.plugin.MyTestBundle";
       },

       initialize: function () {
           pimcore.plugin.broker.registerPlugin(this);
       },

       prepareClassLayoutContextMenu: function (allowedTypes, source) {
           if (allowedTypes.root !== undefined) {
               allowedTypes.root.push('myLayoutType');
           }
   
           return allowedTypes;
       }
   });

   var MyTestBundle = new pimcore.plugin.MyTestBundle();
   ```
   
   You can also add the layout type to any currently supported layout types by using this code snippet:
   ```javascript
   for (let layout in allowedTypes) {
       if (allowedTypes[layout] !== undefined
           && allowedTypes[layout].length > 0
       ) {
           allowedTypes[layout].push('myLayoutType')
       }
   }
   ```
