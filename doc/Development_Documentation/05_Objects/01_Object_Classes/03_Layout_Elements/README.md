# Layout Elements

To structure object data layout-wise, there are 3 panel types and 4 other layout elements available. Data fields are 
always contained in a panel. Panels can be nested and thereby a data input interface tailored to the users's needs 
can be designed.

![Layout Elements](../../../img/classes-layouts.png)

The three available panel types are:
* Panel - a plain panel holding fields
* Region - a region panel able to hold nested panel's in its regions north, east, west and south
* Tabpanel - a panel holding further nested panels as tabs

Moreover, within a panel fields can be put into the following layout Components
* Accordion
* Fieldset
* Field Container

And last but not least there are three extra layout elements:
* Button - with a custom handler. Context of the handler js is the edit tab of the object.  
* Text - to add minimally formatted text to an object layout. This can hold descriptions and hints which don't fit into 
a field's tooltip. Please note that since release 4.4.2 it is possible to generate this text dynamically.
Please read this [page](./01_Dynamic_Text_Labels.md) for further details.
* IFrame - provide a URL and make use of the context paramater to render the response of your choice.
Please read this [page](./02_Preview_Iframe.md) for further details.

Pimcore uses Ext JS layout components for all object layout elements. For a deeper understanding of the layout elements, 
please have a look at the [Ext JS documentation pages](http://docs.sencha.com/extjs/6.0/6.0.1-classic/) and 
[examples](http://www.sencha.com/products/js/).
