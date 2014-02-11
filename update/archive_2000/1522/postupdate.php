Now you need to define the availability of your custom data types in the js class pimcore.object.classes.data.???? like this: <br/>

<pre>
     /**
      * define where this datatype is allowed
      */
     allowIn: {
         object: true,
         objectbrick: true,
         fieldcollection: true,
         localizedfield: true
     },
</pre>

By default, all custom data types are disabled.
<br/>
