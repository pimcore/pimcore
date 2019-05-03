# Updating Pimcore

Please check the [Upgrade Notes](../README.md) in detail before any update. 

## Upgrading from version 5.4.0 to a newer version
- Check your version constraint for `pimcore/pimcore` in your `composer.json` and adapt it if necessary
- Run `COMPOSER_MEMORY_LIMIT=-1 composer update`

Composer update runs Pimcore migrations automatically. To run core migrations manually (e.g. when using composer install), use: `bin/console pimcore:migrations:migrate -s pimcore_core -n`

## Upgrading from version >= 5.0 to <= 5.4.0
- Use the built-in update functionality:  *Tools* > *Update* or the `bin/console` update command.

## Updating from Version <= 4 to Version 5
Pimcore 5 is built on an entire different platform/framework (Symfony replaced ZF1), therefore we cannot 
offer an automatic update and migration scenario. But we've created a [migration guide](./01_Upgrade_from_4_to_5/README.md) 
that helps you to migrate your application step by step. 


