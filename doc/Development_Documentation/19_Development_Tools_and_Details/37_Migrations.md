# Migrations

A common tasks in evolving application is the need to migrate data and data structures to a specific format. Common examples
are adding a new column to a database table or importing or altering a Pimcore class definition. 

To be able to execute migration changes across environments, Pimcore integrates the [Doctrine Migrations](http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/index.html)
library which provides a powerful migration framework. Building on Doctrine Migrations, Pimcore adds the following features:

* Migrations can be split up into multiple migration sets which are independent from each other. By default, there is one
  global `app` migration set and one set for every bundle which implements migrations in its installer, but you configure
  Pimcore to handle additional migration sets.
* As Doctrine Migrations is targeted to DB migrations only, Pimcore adds a couple of features to use migrations also for 
  other changes such as changing class definitions. For example, a migration in Pimcore is able to determine if it is in
  `--dry-run` state (simulation) in order to decide if it changes things or not. For pure DB changes this is easy, as collected
  SQL statements are simply not executed, but when changing a class definition much more is changed in the background. Therefore
  the migration itself needs to decide if it executes its change or not.
  
Besides of those details, please refer to the [Doctrine Migrations documentation](http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/index.html)
for further information on migrations. You can also take a look at the [DoctrineMigrationsBundle](https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html)
documentation, but Pimcore uses its commands internally and does not load the bundle itself. Therefore, configurations under
the `doctrine_migrations` key will not be available (this is handled by Pimcore as we support multiple migration sets).

In general, the migration commands provided by Pimcore are the same as provided by the `DoctrineMigrationsBundle`, but start
with `pimcore:migrations`, e.g. `pimcore:migrations:migrate`.

Migrations can be either used as global migration set or as a bundle specific one. For bundles, a dedicated `MigrationInstaller`
takes care of defining a migration set and of interacting with migrations. There is a dedicated [documentation page on installers](../20_Extending_Pimcore/13_Bundle_Developers_Guide/05_Pimcore_Bundles/01_Installers.md)
which describes the interaction between installers and migration in detail. This pages describes the basic functionality 
which apply to all migrations.


## Using migrations

Let's use a simple example to demonstrate how to use migrations inside Pimcore. Assume your project needs a DB table which
is not handled via Pimcore's classes but is just a plain DB table you'll use in your code. To create this table, we'll use
a first migration which defines the basic table structure. 

Start off by looking at the basic migration configuration. If you don't pass a migration set name via `--set`, it will
default to the `app` migration set which creates migration classes in `app/Resources/migrations`.

```bash
$ bin/console pimcore:migrations:status

 == Configuration

    >> Name:                                               Migrations
    >> Database Driver:                                    pdo_mysql
    >> Database Name:                                      pimcore5
    >> Configuration Source:                               manually configured
    >> Version Table Name:                                 pimcore_migrations
    >> Version Column Name:                                version
    >> Migrations Namespace:                               App\Migrations
    >> Migrations Directory:                               app/Resources/migrations
    >> Previous Version:                                   Already at first version
    >> Current Version:                                    0
    >> Next Version:                                       Already at latest version
    >> Latest Version:                                     0
    >> Executed Migrations:                                0
    >> Executed Unavailable Migrations:                    0
    >> Available Migrations:                               0
    >> New Migrations:                                     0
```

### Creating a first migration

Migrations can be generated with the `pimcore:migrations:generate` command:

```bash
$ bin/console pimcore:migrations:generate
Generated new migration class to "app/Resources/migrations/Version20171005123020.php"
```

As you can see, the migration class defines an `up` and a `down` method which will be executed when migrating in one
of those migrations. In general, the `down` method should reverse the changes done in `up`. Each method receives a 
`Doctrine\DBAL\Schema\Schema` object containing an abstract database schema representation which you can use to alter
the database structure. If this is not enough, you can add raw SQL queries via `$this->addSql()`. Please refer to the
[Doctrine Schema-Representation documentation](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-representation.html)
for details on the schema object.

```php
<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171005123020 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
```

Let's update our migration to create a table.

```php
<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20171005123020 extends AbstractPimcoreMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->createTable('foo');
        $table->addColumn('title', 'string');
        $table->addColumn('description', 'string');
    }

    public function down(Schema $schema)
    {
        $schema->dropTable('foo');
    }
}
```

Executing the `pimcore:migrations:status` command now will show us one migration to execute:

```bash
$ bin/console pimcore:migrations:status

 == Configuration

    >> Name:                                               Migrations
    >> Database Driver:                                    pdo_mysql
    >> Database Name:                                      pimcore5
    >> Configuration Source:                               manually configured
    >> Version Table Name:                                 pimcore_migrations
    >> Version Column Name:                                version
    >> Migrations Namespace:                               App\Migrations
    >> Migrations Directory:                               app/Resources/migrations
    >> Previous Version:                                   Already at first version
    >> Current Version:                                    0
    >> Next Version:                                       2017-10-05 12:30:20 (20171005123020)
    >> Latest Version:                                     2017-10-05 12:30:20 (20171005123020)
    >> Executed Migrations:                                0
    >> Executed Unavailable Migrations:                    0
    >> Available Migrations:                               1
    >> New Migrations:                                     1
```

To actually execute the migration, you can use the `pimcore:migrations:migrate` command which will migrate to the latest
known version. Executing this command will apply every defined migration which wasn't executed yet.

```bash
$ bin/console pimcore:migrations:migrate

                    Migrations


WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)y
Migrating up to 20171005123020 from 0

  ++ migrating 20171005123020

     -> CREATE TABLE foo (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8MB4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB

  ++ migrated (0.72s)

  ------------------------

  ++ finished in 0.72s
  ++ 1 migrations executed
  ++ 1 sql queries
```


### Updates to the initial schema

This works as expected. Our application now can use the created database table and re-create the table in every environment
by simply migrating to the latest version. Now assume after some time you decide to add a new column to your table. To do so
just generate a new migration and alter its code:

```php
<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20171005124853 extends AbstractPimcoreMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable('foo');
        $table->addColumn('description', 'text');
    }

    public function down(Schema $schema)
    {
        $table = $schema->getTable('foo');
        $table->dropColumn('description');
    }
}
```

A `pimcore:migrations:status` will now show a new migration which is ready to be applied:

```bash
$ bin/console pimcore:migrations:status
 
  == Configuration
 
     >> Name:                                               Migrations
     >> Database Driver:                                    pdo_mysql
     >> Database Name:                                      pimcore5
     >> Configuration Source:                               manually configured
     >> Version Table Name:                                 pimcore_migrations
     >> Version Column Name:                                version
     >> Migrations Namespace:                               App\Migrations
     >> Migrations Directory:                               app/Resources/migrations
     >> Previous Version:                                   0
     >> Current Version:                                    2017-10-05 12:30:20 (20171005123020)
     >> Next Version:                                       2017-10-05 12:48:53 (20171005124853)
     >> Latest Version:                                     2017-10-05 12:48:53 (20171005124853)
     >> Executed Migrations:                                1
     >> Executed Unavailable Migrations:                    0
     >> Available Migrations:                               2
     >> New Migrations:                                     1
```

Changes can again be applied via `pimcore:migrations:migrate`:

```bash
$ bin/console pimcore:migrations:migrate
  
                      Migrations
  
  
  WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)y
  Migrating up to 20171005124853 from 20171005123020
  
    ++ migrating 20171005124853
  
       -> ALTER TABLE foo ADD description LONGTEXT NOT NULL
  
    ++ migrated (0.92s)
  
    ------------------------
  
    ++ finished in 0.92s
    ++ 1 migrations executed
    ++ 1 sql queries
```


## Migration Sets

As mentioned above, Pimcore's migrations support multiple migration sets which are completely independent from each other.
Each migration set defines an own migration namespace and an own directory where migration classes will be located. When
executing migrations for a specific migration set it has no effect on other migration set. E.g. a bundle can handle its own
DB schema updates in its own migration set while being fully independent from `app` migrations which update class definitions
which are valid for the whole application. 

By default Pimcore defines the following migration sets:

* A global `app` migration set which looks for migrations in `app/Resources/migrations`. This is the default migration set
  for all migrate commands if no specific set name was passed as option. All examples on this page refer to the `app` migration
  set.
* One migration set for every bundle which implements a `MigrationInstaller`.

When interacting with migrations, you can use 2 command line options to choose the right migration set:

* `--bundle/-b` with a bundle name: `$ bin/console pimcore:migrations:status -b AppBundle`
* `--set/-s` with a set name: `$ bin/console pimcore:migrations:status -s app`   


### Defining custom migration sets

Additional migration sets can be added via configuration. To add a new set, add an entry to the `pimcore.migrations.sets`
config entry:

```yaml
pimcore:
    migrations:
        sets:
            custom_migrations:
                name: My Custom Migrations
                namespace: CustomMigrations
                directory: "%kernel.project_dir%/src/CustomMigrations"
```

After adding the entry, you can start using your migration set:

```bash
$ bin/console pimcore:migrations:status -s custom_migrations
  
   == Configuration
  
      >> Name:                                               My Custom Migrations
      >> Database Driver:                                    pdo_mysql
      >> Database Name:                                      pimcore5
      >> Configuration Source:                               manually configured
      >> Version Table Name:                                 pimcore_migrations
      >> Version Column Name:                                version
      >> Migrations Namespace:                               CustomMigrations
      >> Migrations Directory:                               src/CustomMigrations
      >> Previous Version:                                   Already at first version
      >> Current Version:                                    2017-10-05 12:54:29 (20171005125429)
      >> Next Version:                                       Already at latest version
      >> Latest Version:                                     0
      >> Executed Migrations:                                0
      >> Executed Unavailable Migrations:                    0
      >> Available Migrations:                               0
      >> New Migrations:                                     0
```

### Using a predefined database connection in a migration set

By default, every migration set will operate on the `default` DBAL connection (the one used by Pimcore). If you want your
set to always use another connection, you can add a `connection` entry to the set configuration.

```yaml
# assuming there is an additional DBAL connection configured
# please refer to the doctrine bundle documentation regarding DBAL configuration
# https://symfony.com/doc/master/bundles/DoctrineBundle/configuration.html
doctrine:
    dbal:
        connections:
            custom_connection:
                # ...


pimcore:
    migrations:
        sets:
            custom_migrations:
                name: My Custom Migrations
                namespace: CustomMigrations
                directory: "%kernel.project_dir%/src/CustomMigrations"
                connection: custom_connection
```

Alternatively you can choose the connection to use by passing the `--db` option to the migration command.

> If a `connection` config entry is passed, it will override the connection selected via `--db`. 


## Using the DI container inside migrations

As described on the [DoctrineMigrationsBundle](https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html#container-aware-migrations)
documentation page, migrations implementing `ContainerAwareInterface` will have direct access to the service container:


```php
<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Version20171005125429 extends AbstractPimcoreMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema)
    {
        $service = $this->container->get('service-id');
    }

    public function down(Schema $schema)
    {
    }
}
```

## Non-DB Changes

Despite being a DB-oriented approach, migrations can be used to alter other structural elements as class definitions. Inside
migrations, you can make full use of Pimcore's APIs, for example to alter or import a class definition. As an example, we'll
alter the description field in a `blogArticle` class:

```php
<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject\ClassDefinition;

class Version20171005125429 extends AbstractPimcoreMigration
{
    public function up(Schema $schema)
    {
        // writeMessage writes to output and formats your message as doctrine would do for SQL queries
        $this->writeMessage('Adding description to blogArticle class');
        
        /** @var ClassDefinition $classDefinition */
        $classDefinition = ClassDefinition::getByName('blogArticle');
        $classDefinition->setDescription('[MIGRATIONS] ' . $classDefinition->getDescription() ?? '');
        $classDefinition->save();
    }

    public function down(Schema $schema)
    {
        $this->writeMessage('Removing description from blogArticle class');
        
        /** @var ClassDefinition $classDefinition */
        $classDefinition = ClassDefinition::getByName('blogArticle');
        $classDefinition->setDescription(preg_replace('/^\[MIGRATIONS\] /', '', $classDefinition->getDescription()));
        $classDefinition->save();
    }
}
```

This migration can be executed as every other migration by excuting `update`:

```bash
$ bin/console pimcore:migrations:migrate
  
                      Migrations
  
  
  WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)y
  Migrating up to 20171005125429 from 0
  
    ++ migrating 20171005125429
  
       -> Adding description to blogArticle class
  Migration 20171005125429 was executed but did not result in any SQL statements.
  
    ++ migrated (0.76s)
  
    ------------------------
  
    ++ finished in 0.76s
    ++ 1 migrations executed
    ++ 0 sql queries
```

As you can see, the change was successfully applied, but yielded a warning about SQL statements not being applied. As our
changes are not executed through the `schema` object or the `addSql` method, the migrations library does not know what has
been applied triggers the warning. To avoid this, the `AbstractPimcoreMigration` defines the following method, which can
be used to suppress the warning if it returns `false`:

```php
<?php

namespace AppBundle\Migrations;

// [...]

class Version20171005125429 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }
    
    // [...]
}
```

As a result, the warning is now gone

```bash
$ bin/console pimcore:migrations:migrate

                    Migrations


WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)y
Migrating up to 20171005125429 from 0

  ++ migrating 20171005125429

     -> Adding description to blogArticle class

  ++ migrated (0.34s)

  ------------------------

  ++ finished in 0.34s
  ++ 1 migrations executed
  ++ 0 sql queries
```

## Handling `--dry-run` in non-DB migrations

Doctrine Migrations can be executed in a `dry-run` mode which does not actually change data. For plain SQL migrations this
is quite easy as all SQL queries are collected before being executed. In `dry-run` mode those collected queries are simply
not executed. For migrations not only handling DB changes, this is more complicated as for example a class definition change
does not only change SQL structure but also writes class files, updates references etc.
By default, migrations do not know about the `dry-run` state, but the `AbstractPimcoreMigration` implements the `DryRunMigrationInterface`
which adds the `dry-run` context on the migration itself. If you use migrations to apply changes which not only affect the
database, you can use this information to decide if you really want to execute the changes.

```php
<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject\ClassDefinition;

class Version20171005125429 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    public function up(Schema $schema)
    {
        // dryRunMessage will prefix the message with "DRY-RUN:" in dry run mode
        $this->writeMessage($this->dryRunMessage('Adding description to blogArticle class'));

        if ($this->isDryRun()) {
            // nothing to do
            return;
        }

        /** @var ClassDefinition $classDefinition */
        $classDefinition = ClassDefinition::getByName('blogArticle');
        $classDefinition->setDescription('[MIGRATIONS] ' . $classDefinition->getDescription() ?? '');
        $classDefinition->save();
    }

    public function down(Schema $schema)
    {
        $this->writeMessage($this->dryRunMessage('Removing description from blogArticle class'));

        if ($this->isDryRun()) {
            return;
        }

        /** @var ClassDefinition $classDefinition */
        $classDefinition = ClassDefinition::getByName('blogArticle');
        $classDefinition->setDescription(preg_replace('/^\[MIGRATIONS\] /', '', $classDefinition->getDescription()));
        $classDefinition->save();
    }
}
```

If you execute this migration with the `--dry-run` option, it will output its message (prefixed with DRY-RUN) but not actually
change data. Also, the migration is not marked as executed and you can execute the migration by omitting the `-dry-run` option.

```bash
# simulate first
$ bin/console pimcore:migrations:migrate --dry-run

                    Migrations


Executing dry run of migration up to 20171005125429 from 0

  ++ migrating 20171005125429

     -> DRY-RUN: Adding description to blogArticle class

  ++ migrated (0.14s)

  ------------------------

  ++ finished in 0.14s
  ++ 1 migrations executed
  ++ 0 sql queries
  

# apply the change
$ bin/console pimcore:migrations:migrate

                    Migrations


WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)y
Migrating up to 20171005125429 from 0

  ++ migrating 20171005125429

     -> Adding description to blogArticle class

  ++ migrated (0.32s)

  ------------------------

  ++ finished in 0.32s
  ++ 1 migrations executed
  ++ 0 sql queries
```

---

For further details please see

* [Doctrine Migrations](http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/index.html)
* [Doctrine Migrations Bundle](http://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html)
* [Installers](../20_Extending_Pimcore/13_Bundle_Developers_Guide/05_Pimcore_Bundles/01_Installers.md)
