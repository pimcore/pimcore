# Migration Guide to Pimcore Version 5

Since Pimcore 5 is build on an entire different platform/framework (Symfony replaced ZF1), an automatic update from 
Pimcore 4 to Pimcore 5 is not possible.
This guide shows you how to migrate your Pimcore applications.


Migration of applications to Pimcore 5 can be seen as a two step process: 

## 1) Get your application up and running with the `Compatibility Bridge` of Pimcore 5 
Pimcore 5 ships with a `Compatibility Bridge` that should enable Pimcore 5 to run Pimcore 4 applications with some file 
 moves and minor code updates.
 
In theory you can stop your migration here and run your application with the `Compatibility Bridge`. But keep in mind that
this is not recommended and has some major consequences like
- Performance will be significantly poorer that running Pimcore without the `Compatibility Bridge`. 
- New features of Pimcore will not be available with the `Compatibility Bridge`. 
- The `Compatibility Bridge` will be removed in future Pimcore versions.
- Etc. 

See the [migration guide](./02_Migrate_for_Compatibility_Bridge.md) for details. 

## 2) Migrate your application to Pimcore 5 Symfony stack


 
 