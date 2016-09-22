# Predefined Document-Types

## General

Pimcore provides the ability to define Document-Types which you're going to use in the documents tree.
Thanks to that, the user of the Pimcore editmode don't have to have knowledge of which controller/action/template 
should be chosen in the specific document.

## Example

<div class="inline-imgs">

To define document-type go to ![Settings](../../img/Icon_settings.png) **Settings -> Document-Types**. 
</div>

You should get the grid like below.

![Document types grid](../../img/documenttypes_grid.png)

Let's suppose that you've created controller, action and template for a books listing.

Reference to the action: `\BookController::listAction`
Reference to the template: `website/views/scripts/book/list.php`

To add new document-type which renders the book listing template you have to click on the **Add** button.
Now, in the grid, you can see the new row without values. Find correct values for the book listing action in the picture.

![New document type](../../img/documenttypes_new_row.png)

Template value is not required in that case because, the template name is the same as the action.

The type can be either a page or a snippet. 
After you have defined a type you can access it in the context menu or in the document settings:

Document settings preview:

![Document type - settings preview](../../img/documenttypes_predefined_document_types.png)

Context menu preview:

![Document type - context menu preview](../../img/documenttypes_context_menu.png)
