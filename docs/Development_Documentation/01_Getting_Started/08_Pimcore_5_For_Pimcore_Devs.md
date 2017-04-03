# Pimcore 5 for Pimcore Developers

If you are already a experienced Pimcore developer, this page should give you a head start for diving into Pimcore 5 and 
answer a few typical developer questions. 

### What changed, what stayed as it was in a nutshell? 
Except every thing changed, Pimcore still stayed Pimcore :). We tried to keep Pimcore reconizeably as much as possible 
and so lots of things stayed as they where: 
- Pimcore Assets and API
- Pimcore Documents Usage and API
- Pimcore Objects and API
- Pimcore REST Webservices
- Pimcore Backend and Admin User Interface
- Workflow Management
- Tools and Features like Versioning, Scheduling, Notes & Events, Properties, Tags, Perspectives, Newsletter, Glossary, etc.

But there are also lots of things that changed or give us new possibilities: 
- The whole MVC and everything that is connected with it like 
  - Routing
  - Controller
  - Views
  
- Everything else that was directly dependend on ZF1 like 
  - Bootstraping and Application Structure
  - Logging
  - Sending Mails
  - Plugins
  - Multilanguage Support
  - Database Abstraction
  - Caching
  
- Now full power of the Symfony Framework, all its Ecosystem and Bundles can be used. 


### Which frameworks do I need to know? 
Pimcore is based on Symfony and its architecture is typical Symfony application. So, if you don't know Symfony framework, 
get to know it [here](http://symfony.com/what-is-symfony) :) 


### Where do I find what? 
Have a look at our [directories structure docs](./02_Directories_Structure.md). 


### What are the breaking changes I need to know about besides MVC?
Have a look at our [Upgrade Notes](../13_Installation_and_Upgrade/09_Upgrade_Notes/02_V4_to_V5.md). 


### How Do I get my Application Up and Running with Pimcore 5?
Have a look at our [migration guides](../13_Installation_and_Upgrade/07_Updating_Pimcore/01_Upgrade_from_4_to_5/README.md) 
for migration [for running with compatibility bridge](../13_Installation_and_Upgrade/07_Updating_Pimcore/01_Upgrade_from_4_to_5/02_Migrate_for_Compatibility_Bridge.md) 
and [complete migration to Symfony stack](../13_Installation_and_Upgrade/07_Updating_Pimcore/01_Upgrade_from_4_to_5/04_Migrate_to_Symfony_Stack.md). 


### How to develop Plugins
Plugins are now Bundles and see our [Bundle Docs](../10_Extending_Pimcore/13_Bundle_Developers_Guide/README.md) for details. 



