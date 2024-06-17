# Updating Pimcore

## Our Backward Compatibility Promise
Since we're building on top of Symfony and in an app, Pimcore and Symfony code get mixed together, it makes sense that we're adopting the same backward compatibility promise for PHP code. 

For further information on how you can ensure that your application won't break when upgrading to a newer version of the same major release branch, please have a look at
https://symfony.com/doc/current/contributing/code/bc.html

## Upgrading within Version 11

:::tip

We recommend using the Pimcore Platform Version. Details see [here](https://pimcore.com/docs/platform/Platform_Version/).

:::

- Carefully read our [Upgrade Notes](../09_Upgrade_Notes/README.md) before any update. 
- Check your version constraint for `pimcore/pimcore` in your `composer.json` and adapt it if necessary to match with the desired target version.
- Run `COMPOSER_MEMORY_LIMIT=-1 composer update`
- Clear the data cache `bin/console pimcore:cache:clear`
- Run core migrations: `bin/console doctrine:migrations:migrate --prefix=Pimcore\\Bundle\\CoreBundle`
- (optional) Run [migrations of your app or bundles](../../19_Development_Tools_and_Details/37_Migrations.md)

## Upgrading from earlier Versions
- [Upgrade from Version 6 to Version 10](./10_V6_to_V10.md)
- [Preparing for Version 11](./11_Preparing_for_V11.md)
- [Upgrade from Version 10 to Version 11](./12_V10_to_V11.md)
