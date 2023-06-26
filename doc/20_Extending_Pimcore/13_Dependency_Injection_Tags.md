# Dependency Injection Tags

Please read the intro of [dependency injection tags](https://symfony.com/doc/current/reference/dic_tags.html) of Symfony first.
 
In addition to the tags provided by Symfony, Pimcore adds it's own tags to flag special purpose services. 

Following an overview of all additional service tags provided by Pimcore: 
 
| Name                               | Usage                                                                           |
|------------------------------------|---------------------------------------------------------------------------------|
| `pimcore.area.brick`               | Used to register your [custom area bricks](../03_Documents/01_Editables/02_Areablock/02_Bricks.md), which are not loaded by the discovering service |
