# Grid Column Configuration Operators

Grid Configuration Operators allow you to add special columns to your grid which first are somehow assembled or converted using operators listed below.

Please note that the portfolio of available operators (and how they work in detail) may change until release 5.1.0).

![Configurator Button](../../img/gridconfig/operator_overview.png)

## Grid renderer

By default the string representation of the result value is displayed. 

![Render example 1](../../img/gridconfig/gridrenderer1.png)

This can be changed wrapping the result into a renderer operator. 
In the following example, the image (the result of getImage_1()) would be rendered as image.

![Render example 2](../../img/gridconfig/gridrenderer2.png)

## Operator Overview

* [Alias](./Operators/Alias.md) 
* [Anonymizer](./Operators/Anonymizer.md) 
* [AnyGetter](./Operators/AnyGetter.md)
* [Arithmetic](./Operators/Arithmethic.md)
* [AssetMetadata](./Operators/AssetMetadataGetter.md)
* [Base64](./Operators/Base64.md)
* [Boolean](./Operators/Boolean.md)
* [Boolean Formatter](./Operators/BooleanFormatter.md)
* [CaseConverter](./Operators/CaseConverter.md)
* [CharCounter](./Operators/CharCounter.md)
* [Concatenator](./Operators/Concatenator.md)
* [DateFormatter](./Operators/DateFormatter.md)
* [ElementCounter](./Operators/ElementCounter.md)
* [FieldCollectionGetter](./Operators/FieldCollectionGetter.md)
* [IsEqual](./Operators/IsEqual.md)
* [Iterator](./Operators/Iterator.md)
* [JSON Encode/Decode](./Operators/JSON.md)
* [LFExpander](./Operators/LFExpander.md)
* [LocaleSwitcher](./Operators/LocaleSwitcher.md)
* [Merge](./Operators/Merge.md)
* [ObjectBrickGetter](./Operators/ObjectBrickGetter.md)
* [ObjectFieldGetter](./Operators/ObjectFieldGetter.md)
* [PHP Serialize/Unserialize](./Operators/PHP.md)
* [PHPCode](./Operators/PHPCode.md)
* [RequiredBy](./Operators/RequiredBy.md)
* [StringContains](./Operators/StringContains.md)
* [StringReplace](./Operators/StringReplace.md)
* [Substring](./Operators/Substring.md)
* [Text](./Operators/Text.md)
* [TranslateValue](./Operators/TranslateValue.md)
* [Trimmer](./Operators/Trimmer.md)


  
