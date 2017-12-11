# AnyGetter

![Setting](../../../img/gridconfig/operator_anygetter_symbol.png)

The AnyGetter aims to allow you to get virtually everyting.

Let us assume that you want to first get the related href (in this case an object). 

![Setting](../../../img/gridconfig/operator_anygetter_sample.png)

After that, you want to grab the field collection (fc), get the second item (not shown) and display the `works' field.

![Setting](../../../img/gridconfig/operator_anygetter_setting.png)

Please be aware that for fieldcollections there is a special operator which does exactly something like that!

* **Label**: The node label
* **Attribute**: The name of the attribute you want to get
* **Parameter**: A parameter you want to pass to the attribute getter
* **Array Type**: If true then the getter will be called on all all child result values and the result will be passed up as array.
* **Return last result**:  Return the last non-empty result even if a child returns an empty result.
* **Forward Attribute**: By default, child operators use the original object. By defining a `Forward Attribute` you can replace the "target" with the result of the forward attribute's getter.
* **Forward Parameter**: Same as above. 




