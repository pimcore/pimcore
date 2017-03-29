# Adding Document Editables 
With plugins, it is also possible to add an individual Document Editable. 

In order to create an individual document editable, all that needs to be done ist creating 
a PHP class `\Pimcore\Model\Document\Tag\Mytag` which extends `\Pimcore\Model\Document\Tag`. 
It must be within that namespace!

For the frontend, a JavaScript class needs to be added `pimcore.document.tags.mytag`. It can 
extend any of the existing `pimcore.document.tags` and must return it's type by overwriting 
the function `getType()`.

This JS file must be included in editmode. You can tell Pimcore to do so by adding
`<pluginDocumentEditmodeJsPaths>` to the `plugin.xml`. 
