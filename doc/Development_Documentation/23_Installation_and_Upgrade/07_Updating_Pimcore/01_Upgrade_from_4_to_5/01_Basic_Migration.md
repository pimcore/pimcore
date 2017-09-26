# Basic Migration

The following steps are needed for every migration to Pimcore 5 and should be done before continuing migration either
to the Pimcore 4 compatibility bridge or the Symfony Stack.

- **Backup your system!**

- The [Pimcore CLI](https://github.com/pimcore/pimcore-cli) provides a set of commands to ease the migration. It is able
  to do the following:

  - extract Pimcore 5 build
  - create several necessary directories
  - move config files to new locations
  - move class files to new location
  - move versions to new location
  - move logs to new location
  - move email logs to new location
  - move assets to new location
  - move website folder to /legacy/website
  - move plugins folder to /legacy/plugins
  - update `system.php` to be ready for Pimcore 5
  
- A simpler [migration.sh](./migration.sh) script handles basic file moving and can be adapted to your needs
- Refactor `constants.php` and move it to `app/constants.php`
- Refactor `startup.php` and move content either to `AppKernel::boot()` or `AppBundle::boot()`

- Update system configs in `/var/config/system.php` (this will be done automatically by Pimcore CLI)
    - `email` > `method` => if `''`, change to `null`
    - `email` > `smtp` > `ssl` => if `''` change to `null`
    - `email` > `smtp` > `auth` > `method` => if `''` change to `null`
    - `email` > `smtp` > `auth` > `password` => add if not there with value `''`
    - `newsletter` > `method` => if `''` change to `null`
    - `newsletter` > `smtp` > `ssl` => if `''` change to `null`
    - `newsletter` > `smtp` > `auth` > `method` => if `''` change to `null`
    - `newsletter` > `smtp` > `auth` > `password` => add if not there with value `''`

- Change document root of your webserver to `/web` directory - document root must not be project root anymore

- Update your `composer.json` to include all dependencies and settings from Pimcore's `composer.json`. The Pimcore CLI will
  use Pimcore's `composer.json` and back up your existing one. If you have any custom dependencies or settings please make
  sure to re-add them to the `composer.json`

- Run `composer update` to install new dependencies. If you encounter errors, please fix them until the command works properly.
  You can use `--no-scripts` to install dependencies and then iterate through errors in subsequent calls to save some time.

- At this point, the basic application should work again. Please try to run `bin/console` to see if the console works

- The [pimcore-4-to-5.php](https://github.com/pimcore/pimcore/blob/master/update-scripts/pimcore-4-to-5.php) script contains
  all update scripts which were introduced during Pimcore 5 development and are needed when migrating from Pimcore 4. When
  using the Pimcore CLI, you should find the script in a `update-scripts` directory, otherwise please take the one provided 
  in the ZIP file. To execute the script, use the following command (making a backup at this stage is strongly recommended):
  
  ```bash
  $ bin/console pimcore:run-script -c update-scripts/pimcore-4-to-5.php
  ```
  
- Run `composer update` once again to update the autoloaded and class maps
- The admin interface of your system should now work again and you can proceed to [migrate your application code](./README.md). 
