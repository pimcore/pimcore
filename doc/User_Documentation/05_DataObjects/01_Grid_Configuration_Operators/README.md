# Grid Column Configuration Operators

Grid Configuration Operators allow you to add special columns to your grid which are 
somehow assembled or converted using operators listed below.

![Configurator Button](../../img/gridconfig/operator_overview.png)

The basic concept is that an operator has one or more data attributes as child elements and executes
its operation on their values. For example, the 
[`ObjectFieldGetter`](./Extractors/ObjectFieldGetter.md) has the `manufacturer` data attribute as 
child and calls `getName` on the value of the `manufacturer` attribute (which is a manufacturer 
data object). This results in a column that contains the manufacturer name.    

With some operators this basic concept gets extended or reversed, please see detailed descriptions in 
their docs.  

In addition to that, there are also operators who work without any data attribute as 
child. One example for that is the [`Text`](./Formatters/Text.md) operator. 


## Operator Overview

### Extractors
* [AssetMetadata](./Extractors/AssetMetadataGetter.md)
* [FieldCollectionGetter](./Extractors/FieldCollectionGetter.md)
* [ObjectFieldGetter](./Extractors/ObjectFieldGetter.md)
* [RequiredBy](./Extractors/RequiredBy.md)
* [PropertyGetter](./Extractors/PropertyGetter.md)
* [WorkflowState](./Extractors/WorkflowState.md)
* [AnyGetter](./Extractors/AnyGetter.md)


### Formatters
* [Boolean Formatter](./Formatters/BooleanFormatter.md)
* [DateFormatter](./Formatters/DateFormatter.md)
* [Text](./Formatters/Text.md)

### Others
* [Alias](./Others/Alias.md) 
* [LocaleSwitcher](./Others/LocaleSwitcher.md)
* [Merge](./Others/Merge.md)
* [PHPCode](./Others/PHPCode.md)

### Renderer

By default, the string representation of the result value is displayed. 

<div class="image-as-lightbox"></div>

![Render example 1](../../img/gridconfig/gridrenderer1.png)

This can be changed wrapping the result into a renderer operator. 
In the following example, the image (the result of `getImage_1()`) would be rendered as image.

<div class="image-as-lightbox"></div>

![Render example 2](../../img/gridconfig/gridrenderer2.png)

### Transformers
* [Anonymizer](./Transformers/Anonymizer.md) 
* [Arithmetic](./Transformers/Arithmethic.md)
* [Base64](./Transformers/Base64.md)
* [Boolean](./Transformers/Boolean.md)
* [CaseConverter](./Transformers/CaseConverter.md)
* [CharCounter](./Transformers/CharCounter.md)
* [Concatenator](./Transformers/Concatenator.md)
* [ElementCounter](./Transformers/ElementCounter.md)
* [IsEqual](./Transformers/IsEqual.md)
* [JSON Encode/Decode](./Transformers/JSON.md)
* [LFExpander](./Transformers/LFExpander.md)
* [PHP Serialize/Unserialize](./Transformers/PHP.md)
* [StringContains](./Transformers/StringContains.md)
* [StringReplace](./Transformers/StringReplace.md)
* [Substring](./Transformers/Substring.md)
* [TranslateValue](./Transformers/TranslateValue.md)
* [Trimmer](./Transformers/Trimmer.md)
* [Iterator](./Transformers/Iterator.md)
