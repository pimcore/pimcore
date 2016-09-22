# Basic Document Types

## General

Pimcore becomes with a few predefined document types. 

## Available types

Several document types are built-in in the fresh Pimcore installation. 
Basic types are listed, below.

### Folder

Folder is just a container which contains other document.

The editmode for folders is the most simple in comparison to other types. 
Only [properties](../../08_Tools_and_Features/07_Properties.md), dependencies, [notes & events](../../08_Tools_and_Features/05_Notes_and_Events.md) 
and [tags](../../08_Tools_and_Features/09_Tags.md) are available there. 


![Folder preview](../../img/basictypes_folder_preview.png)


Folder doesn't have dedicated url where the content is rendered.

### Page

You can find out detailed information aout that type in the [Documents part](../../03_Documents/README.md).
This is a representation of the single content page in Pimcore. 

### Snippet

Use the snippet editable to embed a document snippet, for example teasers, boxes, footers, etc.

Snippets are like little documents which can be embedded in other documents. 
You have to create them the same way as other documents.

To know more about Snippet have a look at the [Snippet section](../../03_Documents/01_Editables/32_Snippet.md).

### Link / Hardlink

If you need reference to the other place in the tree structure. Use links and hardlinks. 
Link and Hardlink would be also the container for an other documents collection. 

![Link preview - editmode](../../img/basictypes_link_preview.png)

As a result from the example above. The `/categories` url will redirect users to `/categories/category1`.

Hardlinks for documents work similar to hardlinks in Linux file systems. 
One position within the document tree can link to another sub tree. 
As a result exciting navigation trees become possible without additional editing efforts due to copies of documents.

### Email / Newsletter

Detailed specification of these document types you can find in the [dedicated Newsletter documentation page](../../08_Tools_and_Features/19_Newsletter.md).