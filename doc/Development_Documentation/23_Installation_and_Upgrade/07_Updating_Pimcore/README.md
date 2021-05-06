# Updating Pimcore

## Our Backward Compatibility Promise
Since we're building on top of Symfony and in an app, Pimcore and Symfony code gets mixed together, 
it just makes sense that we're adopting the same backward compatibility promise for PHP code. 

So for further information how you can ensure that your application won’t break completely 
when upgrading to a newer version of the same major release branch, please have a look at
https://symfony.com/doc/current/contributing/code/bc.html

The code for the admin user interface (mostly `AdminBundle` but also parts of `EcommerceFrameworkBundle`) is not covered by this promise.

## Upgrading within Version X
- Carefully read our [Upgrade Notes](../09_Upgrade_Notes/README.md) before any update. 
- Check your version constraint for `pimcore/pimcore` in your `composer.json` and adapt it if necessary to match with the desired target version.
- Run `COMPOSER_MEMORY_LIMIT=-1 composer update`

Composer update runs Pimcore migrations automatically. 
If you do not want to run Pimcore migrations automatically please remove `"Pimcore\\Composer::executeMigrationsUp"` from the `post-update-cmd` scripts in your `composer.json`.

To run core migrations manually (e.g. when using composer install), 
use: `bin/console doctrine:migrations:migrate --prefix=Pimcore\\Bundle\\CoreBundle`

## Upgrading to Version X from earlier Versions
- [Upgrade from Version 6 to Version X](./10_V6_to_V10.md)

