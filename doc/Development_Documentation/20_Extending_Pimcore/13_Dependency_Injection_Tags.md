
# Dependency Injection Tags

Please read the intro of [dependency injection tags](https://symfony.com/doc/5.2/reference/dic_tags.html) of Symfony first.
 
In addition to the tags provided by Symfony, Pimcore adds it's own tags to flag special purpose services. 

Following an overview of all additional service tags provided by Pimcore: 
 
| Name                               | Usage                                                                           |
|------------------------------------|---------------------------------------------------------------------------------|
| `pimcore.templating.helper_broker` | Add a helper broker service to the templating engine. Using `templating.helper` is more common, and recommended |
| `pimcore.area.brick`               | Used to register your [custom area bricks](../03_Documents/01_Editables/02_Areablock/02_Bricks.md), which are not loaded by the discovering service |

