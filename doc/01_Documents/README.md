---
title: Documents
description: Introduction to Pimcore Documents, the CMS component for managing web pages, snippets, emails, and navigation structures.
keywords:
    - CMS
    - web content management
    - document types
    - page
    - snippet
---

# Documents

Documents are Pimcore's web content management component. Each document is a node in a hierarchical tree,
identified by a unique ID and a path that doubles as its public URL. A page at `/en/about/team` in the
document tree is reachable at exactly that address in the browser.

Documents combine structured content editing in [Pimcore Studio](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/README.md)
with Symfony-based rendering on the frontend.
Editors work with visual [Editables](./02_Templates/03_Editables/README.md) (input fields, WYSIWYG editors,
image pickers) directly inside the page, while developers control layout and logic through controllers
and Twig templates.

## Document Types

Pimcore provides several document types, each designed for a specific use case:

| Type      | Purpose                                                                                  |
|-----------|------------------------------------------------------------------------------------------|
| Page      | A web page. Its path in the tree equals its URL in the browser.                          |
| Snippet   | A reusable content fragment, embeddable in pages or other snippets.                      |
| Link      | A web link for use in navigation structures.                                             |
| Hardlink  | A reference to another document subtree, reusing its content in a different context.     |
| Folder    | Organizes documents, just like folders on a filesystem.                                  |
| Email     | A document with special functionality for transactional emails.                          |

## How Documents Work

Documents follow a document-controller-template pattern built on Symfony MVC.
A request is [routed](./05_Routing_and_URLs/README.md) to a document, which determines
the controller and template for rendering.
See [MVC in Pimcore](./01_MVC_in_Pimcore.md) for the rendering flow and configuration types.

## Properties

[Properties](../05_Content_Management_Features/04_Properties.md) add key-value metadata to documents.
Combined with tree inheritance, they become a powerful tool for managing cross-cutting concerns
without touching every page individually:

- **Navigation** - control separators, highlighting, or custom CSS classes for navigation rendering.
- **Header images** - set a default header image at the root and override it deeper in the tree.
- **Sidebars** - toggle sidebar visibility per section.
- **SEO** - define fallback meta titles and descriptions that inherit down the tree,
  overridden only where needed.
- **Protected areas** - mark subtrees for closed user groups.
- **Appearance** - switch themes or layouts for micro-sites or nested sites.

## Next Steps

- [Templates](./02_Templates/README.md) - Twig templates and editables
- [MVC in Pimcore](./01_MVC_in_Pimcore.md) - document rendering architecture and type configuration
- [Editables](./02_Templates/03_Editables/README.md) - in-page content editing widgets
- [Routing and URLs](./05_Routing_and_URLs/README.md) - how requests reach documents
- [Navigation](./06_Navigation.md) - building navigations from the document tree
- [Document Inheritance](./07_Document_Inheritance.md) - inheriting content between documents
- [Predefined Document Types](./04_Predefined_Document_Types.md) - preconfigured controller/template combinations
- [Working with Documents via PHP API](./14_Working_with_Documents_via_PHP_API.md) - CRUD operations and listings
- [Create a First Project](https://github.com/pimcore/platform-version/blob/2026.x/doc/03_Getting_Started/03_Create_a_First_Project/README.md) - hands-on tutorial
