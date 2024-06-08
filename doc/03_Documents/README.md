# Documents
Documents are the CMS part of Pimcore and are the way to go for managing unstructured contents using pages, content snippets and navigations. 

## Document Types
Pimcore offers different types of documents and each of them offers functionality specific for the intended use-case. 

| Type           | Description                                                                                                                                                 | 
|----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Page           | Represents a typical web-page, the path in the tree is equal to the address in the browser.                                                                 |
| Snippet        | Makes it easier to extract often used contents into reusable containers. Can be embedded in pages or nested into other snippets.                            |
| Link           | A simple web-link to be used in navigation.                                                                                                                 |
| Email          | A document like the page, but with special functionality for transactional emails.                                                                          |
| Hardlink       | Create links to other document structures and reuse them within a different structure / context. (see [Hard link](https://en.wikipedia.org/wiki/Hard_link)) |
| Folder         | Just like the folders you know from your local filesystem.                                                                                                  |


## Document Configuration

Many documents types are tight to the [MVC](../02_MVC/README.md) and therefore they need a underlying controller/action and a template. 
They are directly specified in the document settings in the admin interface: 

![Documents: controller and view settings](../img/documents_controller_and_view_settings.png)

Not all of them are necessary, the table below shows which configurations are possible:

| Type | Controller  | Template | Description                                                                                                                            |
|------|------------ |----------|----------------------------------------------------------------------------------------------------------------------------------------|
| 1    | X           |          | The specified controller/action is executed. If the action returns a response object, it is used for rendering.                        |
| 2    | X           | X        | Same as above but the template specified is rendered and not the auto-discovered template (only if action does not return a response). |
| 3    |             | X        | Renders the template with the default controller/action, this is practical if there is only templating stuff.                          |


Pimcore is shipped with a default controller containing a default action, which is called when only a template is given to the document.

You can set a default module/bundle, controller and action in the symfony configuration:

```yaml
pimcore:
    documents:
        default_controller:App\Controller\DefaultController::defaultAction
```  

## Properties

[Properties](../18_Tools_and_Features/07_Properties.md) are very powerful in combination with documents.
Below, you can find some examples where properties can be very useful for the use with documents. 

1. **Navigation** - If you build the navigation based on the document-tree, sometimes you need special settings for the frontend, like separators or highlightings.
2. **Header Images** - Often there are header images on a website, if you don't want to define it for every page, you can use properties with inheritance. Then you can define a default one at the root document, and overrule this on a deeper level in the tree structure.
3. **Sidebars** - You can easily manage visibility of sidebars in specific documents.
4. **SEO** - It's also possible to use properties for SEO. It's very painful to define a nice title and description for every page on your site, with properties this is not necessary (inheritance).
5. **Protected Areas** - Closed user groups
6. Change the appearance of the website depending on the properties (eg. micro-sites, nested sites)
7. Mark them for some automated exports (PDF, RPC's, …)

As you can see there are really useful cases for properties, feel free to use them for whatever they seem to be useful.

## A Few Facts

* Documents follow the MVC pattern; therefore, Pimcore requires that there is at least one controller with an action and a template file.
* Pimcore comes with a DefaultController containing a defaultAction and a template file.
* Because of the MVC architecture, there is a clear separation between your data, the functionality and the templates.
* Templates for Pimcore are created with [Twig](https://twig.symfony.com/).
* As the templates are written in Twig, there is a clear separation of logic (controllers) and design (view / template).

## Create Your First Document 
Working with documents is described in detail in our [Create a First Project](../01_Getting_Started/06_Create_a_First_Project.md) manual. 

## Cleanup Documents Types
Uninstalling bundles may not always clean up data or database tables in the process to avoid data loss.
Running the following command removes type specific tables and enum types from the documents table.
The following types are protected and cannot be cleaned up via this command: `page, link, snippet, folder, hardlink, email`

You can clean up multiple types at once.
```bash
bin/console pimcore:documents:cleanup <type1> <type2> <type3>
```


## Document Topics
- [Creating editable templates](./01_Editables/README.md) 
- [Navigation](./03_Navigation.md)
- [Inheritance](./11_Inheritance.md)
- [Working with the PHP API](./09_Working_with_PHP_API.md) 
- [Predefined Document Types](./07_Predefined_Document_Types.md)
