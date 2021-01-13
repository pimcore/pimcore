# AnyGetter

![Symbol](../../../img/gridconfig/operator_anygetter_symbol.png)

The AnyGetter aims to allow you to get virtually everything.

Configuration Settings: 
 - Label: Label of column
 - Attribute: The name of the attribute you want to get
 - Parameter: A parameter you want to pass to the attribute getter
 - Array Type: If true then the getter will be called on all child result values and the result 
will be passed up as array.
 - Return last result:  Return the last non-empty result even if a child returns an empty result.
 - Forward Attribute: By default, child operators use the original object. By defining a 
`Forward Attribute` you can replace the "target" with the result of the forward attribute's getter.
 - Forward Parameter: Same as above. 

Additional aspects: 
- If operator has no child element assigned, operator tries to call `get<Attribute>(<Parameter>)` on the current object. 
- If operator has a child element, it calls `get<Attribute>` on the value of this child element. 
- If method `get<Attribute>()` does not exist, operator tries to call method `<Attribute>()`.
- If `Attribute` not set or neither of `get<Attribute>()` and `<Attribute>()` exist, operator returns child element value without
  calling any additional method. 
  
  
- If the any getter has multiple child elements assigned, whole getter logic is executed to every child 
  element, and it returns an array of result values. 
- If setting `Array Type` is set and child of operator returns a list of elements (e.g. many-to-many-object relation attribute), 
  operator calls `get<Attribute>(<Parameter>)` on all value and returns an array of the results.  


- With the `Forward Attribute` setting, the default principle for getting the data attribute value can be 
  changed. Instead of getting the data attribute value of the main data object, a set `Forward Attribute`
  results in calling `get<Forward Attribute>(<Forward Attribute>)` on the current data object first and 
  then the actual data attribute value is calculated on its result. Thus, his would result in something similar to 
  `$object->get<Forward Attribute>(<Forward Attribute>)->get<Child Element>()->get<Attribute>(<Parameter>)`. 
