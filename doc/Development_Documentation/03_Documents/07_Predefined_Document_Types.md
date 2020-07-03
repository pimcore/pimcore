# Predefined Document-Types

## General

Pimcore provides the ability to define preconfigurations for documents, which makes it easier for editors to work with 
many different controller/action and templates. 
By using this feature the editors don't have to have any knowledge of which controller/action/template 
should be used in a specific document because they can choose out of a predefined set of configurations with a friendly name. 

## Example

To define document-type go to *Settings* > *Document-Types*.
![Document types grid](../img/documenttypes_grid.png)

Let's suppose that you've created controller, action and template for a books listing.

Reference to the action: `\BookController::listAction`  
Reference to the template: `website/views/scripts/book/list.php`

To add a new document-type which renders the book listing template, you have to click on the *Add* button first and then
fill out the newly created configuration row accordingly. 

![New document type](../img/documenttypes_new_row.png)

The type can be either a page, snippet, email, newsletter, printcontainer or printpage.   
After you have defined a type you can access it in the context menu or in the document settings:

##### Document Settings Preview
![Document type - settings preview](../img/documenttypes_predefined_document_types.png)

##### Context Menu Preview
![Document type - context menu preview](../img/documenttypes_context_menu.png)
