# Blocks

The block data type acts as a simple container for other data fields. 
Similar to a field collection, an unlimited number of block elements can be created.

A block element can be placed into a localized field but can also contain a localized field itself. 
Nesting is not possible.

![Block data type](../../../img/ObjectsBlocks_data_container.jpg)

![Block, edit peview](../../../img/ObjectsBlocks_edit_preview.png)


> The block data basically just gets serialized into a single database column. 
> As a consequence, this container type is not suitable, if you are planning to query the data.
