# Updating Pimcore

## Upgrading within Version X
- Carefully read our [Upgrade Notes](../09_Upgrade_Notes/README.md) before any update. 
- Check your version constraint for `pimcore/pimcore` in your `composer.json` and adapt it if necessary to match with the desired target version.
- Run `COMPOSER_MEMORY_LIMIT=-1 composer update`

Composer update runs Pimcore migrations automatically. 
If you do not want to run Pimcore migrations automatically please remove `"Pimcore\\Composer::executeMigrationsUp"` from the `post-update-cmd` scripts in your `composer.json`.

To run core migrations manually (e.g. when using composer install), 
use: `bin/console doctrine:migrations:list --prefix=Pimcore\\Bundle\\CoreBundle\\Migrations`

## Upgrading to Version X from earlier Versions
- [Upgrade from Version 6 to Version X](./10_V6_to_V10.md)

## Upgrading within Version 6
- Carefully read our [Upgrade Notes](../09_Upgrade_Notes/README.md) before any update. 
- Check your version constraint for `pimcore/pimcore` in your `composer.json` and adapt it if necessary to match with the desired target version.
- Run `COMPOSER_MEMORY_LIMIT=-1 composer update`

Composer update runs Pimcore migrations automatically. 
If you do not want to run Pimcore migrations automatically please remove `"Pimcore\\Composer::executeMigrationsUp"` from the `post-update-cmd` scripts in your `composer.json`.

To run core migrations manually (e.g. when using composer install), 
use: `bin/console pimcore:migrations:migrate -s pimcore_core -n`

## Upgrading to Version 6 from earlier Versions
- [Upgrade from Version 5 to Version 6](./01_V5_to_V6.md)
- [Upgrade from Version 4 to Version 6](./04_V4_to_V6.md) 
