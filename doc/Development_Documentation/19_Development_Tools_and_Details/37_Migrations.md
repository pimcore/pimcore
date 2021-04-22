# Migrations

A common tasks in evolving applications is the need to migrate data and data structures to a specific format. Common examples
are adding a new column to a database table or changing data.

To be able to execute migration changes across environments, Pimcore integrates the [Doctrine Migrations Bundle](https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html)
library which provides a powerful migration framework. 

To create your project or bundle specific migrations you can just follow the official guide linked above. 
However, Pimcore adds one small yet helpful feature to the provided commands by the Doctrine Migrations Bundle. 
Normally Doctrine Migrations just runs/lists all available migrations, defined in the config.
Since it could be useful to filter migrations for a certain path (core, bundle or project), Pimcore adds the `--prefix` option to all 
Doctrine commands, which let's you filter migrations by the given namespace. 

If you'd like to create migrations for your bundle, please have a look at our
[bundles documentation](../20_Extending_Pimcore/13_Bundle_Developers_Guide/05_Pimcore_Bundles/01_Installers.md). 
We're providing a customized abstract for bundles which makes using bundles even 
more comfortable. 


### Example Commands
```bash
# just run migrations of the Pimcore core
bin/console doctrine:migrations:migrate --prefix=Pimcore\\Bundle\\CoreBundle

# run migrations of a certain bundle
./bin/console doctrine:migrations:migrate --prefix=Vendor\\PimcoreExampleBundle

# run project specific migrations
./bin/console doctrine:migrations:migrate --prefix=App\\Migrations
```


# Config Examples for your Project (`config/config.yml`)
```yml
doctrine_migrations:
    migrations_paths:
        'App\Migrations': '%kernel.project_dir%/src/App'
```

# Run all available migrations after `composer update`
Pimcore does only run Pimcore core migrations after `composer update` automatically. 
If you'd like to run all available migrations including bundles and your app-specific 
migrations, just include the following part in your `composer.json`. 

```json
"post-update-cmd": [
    "./bin/console doctrine:migrations:migrate"
]
```