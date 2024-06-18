# Editables

The editables are placeholders in the templates, which are displayed as input widgets in the admin interface (so called editmode) and output the content in frontend mode.
They are the essential part of managing content in documents. 

## Example Usage 
The following code makes the `<h1>` headline editable in a document: 

```twig
<h1>{{ pimcore_input("headline") }}</h1>
```

In some cases, especially with areablocks, editables could throw exceptions. Since Pimcore internally uses the `__toString` method to render
the editables, Pimcore can't throw exceptions there. Therefore Pimcore catches the exception, and puts it out as string if DEBUG Mode is enabled.

To prevent that and to have Pimcore throw the Exception, you can call editables like:

```twig
<h1>{{ pimcore_input("headline").render()|raw }}</h1>
```

Pimcore automatically displays an input widget in the edit mode and renders the content when accessing the document via the frontend. 

## List of Editables 

| Name                                                               | Description                                                                                                                                                  |
|--------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [Areablock](./02_Areablock/README.md)                              | Areablock is the content construction kit which allows you to insert predefined **mini applications** / **content blocks** called bricks into an areablock.. |
| [Area](./04_Area.md)                                               | Area allows you to use area bricks of a certain type (just like the Areablock).                                                                              |
| [Block](./06_Block.md)                                             | Block is a loop component which can contain other editables.                                                                                                 |
| [Checkbox](./08_Checkbox.md)                                       | Checkbox / bool implementation for documents.                                                                                                                |
| [Date](./10_Date.md)                                               | Datepicker, showing the date in a specified format.                                                                                                          |
| [Relation](./12_Relation_Many-To-One.md)                           | Provides possibility to create a reference to any other element in Pimcore (document, asset, object).                                                        |
| [Relations (Many-To-Many Relation)](./13_Relations_Many-To-Many.md) | Provides possibility to edit multiple references to other elements in Pimcore (documents, assets, objects).                                                  |
| [Image](./14_Image.md)                                             | A place where you can assign an image (from the assets module).                                                                                              |
| [Input](./16_Input.md)                                             | A single-line text-input.                                                                                                                                    |
| [Link](./18_Link.md)                                               | An editable link component.                                                                                                                                  |
| [Multiselect](./22_Multiselect.md)                                 | Multiselect implementation for documents.                                                                                                                    |
| [Numeric](./24_Numeric.md)                                         | The numeric editable is like the input editable but with special options for numbers (like minimum value, decimal precision...).                             |
| [PDF](./26_PDF.md)                                                 | This editable allows you to embed asset documents (pdf, doc, xls, ...) into documents (like video, image, ...).                                              |
| [Renderlet](./28_Renderlet.md)                                     | The renderlet is a special container which is able to handle every object in Pimcore (Documents, Assets, Objects).                                           |
| [Select](./30_Select.md)                                           | Select box as an editable.                                                                                                                                   |
| [Snippet (embed)](./32_Snippet.md)                                 | Use the snippet editable to embed a reusable document, for example to create teasers, boxes, etc.                                                            |
| [Table](./34_Table.md)                                             | This editable allows you to add a fully editable table.                                                                                                      |
| [Textarea](./36_Textarea.md)                                       | Textarea implementation for documents.                                                                                                                       |
| [Video](./38_Video.md)                                             | Use the Video editable to insert asset movies in pages content.                                                                                              |
| [WYSIWYG](./40_WYSIWYG.md)                                         | WYSIWYG editor.                                                                                                                                              |
| [Scheduled Block](./42_Scheduled_Block.md)                         | Scheduled Blocks allow to schedule content for certain timestamps                                                                                            |
