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

### Example Commands
```bash
# just run migrations of the Pimcore core
bin/console doctrine:migrations:list --prefix=Pimcore\\Bundle\\CoreBundle\\Migrations

# run migrations of a certain bundle
./bin/console doctrine:migrations:list --prefix=Vendor\\PimcoreExampleBundle\\Migrations

# run project specific migrations
./bin/console doctrine:migrations:list --prefix=App\\Migrations
```


# Config Examples for your Project (`config/config.yml`)
```yml
doctrine_migrations:
    migrations_paths:
        'App\Migrations': '%kernel.project_dir%/src/App'
```

