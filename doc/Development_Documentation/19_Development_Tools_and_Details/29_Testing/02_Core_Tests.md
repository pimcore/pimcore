# Core Testing

Pimcore uses [Codeception](https://codeception.com/) for testing its core features.

## Requirements

1. A Pimcore installation. Read this [guide](../../01_Getting_Started/00_Installation.md) for instructions.
2. A **dedicated database** used only for testing. In other words, if the Pimcore installation is not only used for testing, create a separate database!
3. Redis cache (optional, but needed for executing cache tests)
4. Make sure that `require-dev` requirements of Pimcore's `composer.json` are installed, especially `codeception/codeception`, `codeception/module-symfony`, `codeception/phpunit-wrapper` for executing codeception tests.

## Executing tests

#### Important notes

> Read this before you start.

##### Test directories

Always set

```
PIMCORE_TEST=1
```

This will switch special directories used for testing (like /var/classes) and prevent that you existing installation gets messed up.

##### Check Logfiles
Don't forget to check logfiles (especially `test.log` and `php.log`) when problems occur.

#### Run all tests

This will run all tests.
```
PIMCORE_TEST_DB_DSN="mysql://[USERNAME]:[PASSWORD]@[HOST]/[DBNAME]" APP_ENV=test PIMCORE_TEST=1 vendor/bin/codecept run -c vendor/pimcore/pimcore
```

#### Only run a specific suite

Only runs the `Model` tests. For a list of suites see the list below.

```
PIMCORE_TEST_DB_DSN="mysql://[USERNAME]:[PASSWORD]@[HOST]/[DBNAME]" APP_ENV=test PIMCORE_TEST=1 vendor/bin/codecept run -c vendor/pimcore/pimcore Model
```

#### Only run a specific test group

This can be a subset of a suite. You also have the option to provide a comma-seperated list of groups.
For an overview of available groups see the table below.

```
PIMCORE_TEST_DB_DSN="mysql://[USERNAME]:[PASSWORD]@[HOST]/[DBNAME]" APP_ENV=test PIMCORE_TEST=1 vendor/bin/codecept run -c vendor/pimcore/pimcore Rest -g dataTypeIn
```

##### Redis Cache tests

For Redis, the `PIMCORE_TEST_REDIS_DSN` option is mandatory. Set to a value that does not conflict to any
other Redis DBs on your system.

```
PIMCORE_TEST_DB_DSN="mysql://[USERNAME]:[PASSWORD]@[HOST]/[DBNAME]" APP_ENV=test PIMCORE_TEST=1 PIMCORE_TEST_REDIS_DSN=redis://localhost vendor/bin/codecept run -c vendor/pimcore/pimcore Cache
```


#### Important Environment Variables

| Env Variable           | Example          | Comment                                                     |
|------------------------|------------------|-------------------------------------------------------------|
| APP_ENV                | test             | Test environment                         |
| PIMCORE_TEST           | 1                | **important** this will switch several directories (like /var/classes) |
| PIMCORE_TEST_SKIP_DB   | 1                | Skips DB setup. This does not skip the db-related tests but it<br>reduces the setup time for tests that don't need a database. |
| PIMCORE_TEST_REDIS_DSN | redis://localhost| **required for REDIS tests**   |

#### Suites

The tests are organized into suites, each one covering specific areas of the core.

| Suite name | Description                                                    |
|------------|----------------------------------------------------------------|
| Cache      | Cache tests                                                    |
| Ecommerce  | Ecommerce bundle tests                                         |
| Model      | Dataobject tests                                               |
| Service    | Test covering common or shared element tasks (versioning, ...) |
| Unit       | Other tests (may need restructuring)                           |
| ...        |                                                                |

#### Groups

The following table lists all groups currently used by Pimcore's core tests. If you extend the tests or write new
ones please tag them accordingly.

| Group                              |                                                                        |
|------------------------------------|------------------------------------------------------------------------|
| cache-cli                          | Cache cli tests                                                        |
| cache.core.array                   | Cache Array handler tests                                              |
| cache.core.db                      | Cache DB handler tests                                                 |
| cache.core.file                    | Cache File handler tests                                               |
| cache.core.redis                   | Cache Redis handler tests                                              |
| cache.core.redis_lua               | Cache Redis handler tests with LUA enabled                             |
| dataTypeIn                         | REST tests - objects are created via REST API and then fetched locally |
| dataTypeLocal                      | Dataobject - datatype tests                                            |
| dataTypeOut                        | REST tests - objects are created locally and fetched via REST API      |
| model.relations.multipleassignment | Dataobject - "allow multiple assignments" tests                        |
| ...                                |                                                                        |

#### Useful command line options

Useful examples:

| Option            | Example                      | Description                           |
|-------------------|------------------------------|---------------------------------------|
| --group (-g       | --group dataTypeLocal        | Only execute specified list of groups |
| --skip (-s)       | --skip cache,rest            | Skip Cache and Rest Tests             |
| --skip-group (-x) | --skip-group cache.core.file | Skip file cache tests                 |
| --fail-fast       | --fail-fast                  | Stop on first error                   |
| ...               |                              |                                       |

See [Codeception Commands](https://codeception.com/docs/reference/Commands) for more options.

## Providing new tests & extending existing ones

In general, contributions in form extending and improving tests is highly appreciated.
Please follow the structure and principles described above.

If you have the extend the data model then please have a look at [Model.php](https://github.com/pimcore/pimcore/blob/master/tests/_support/Helper/Model.php).
There you will find all class definitions used for testing.

### Perform PHPStan Analysis

First, get a copy of this [sample configuration file](../../../Samples/phpstan.neon) and place it in your root directory.

Add dependencies:
```sh
# minimum
composer require "phpstan/phpstan:^0.12" "phpstan/phpstan-symfony:^0.12"

# required if you want to do a full analysis
composer require "elasticsearch/elasticsearch:^7.11" "composer/composer:*"
cp -rv vendor/pimcore/pimcore/.github/ci/files/models/* var/classes/
```

Run
```sh
TMPDIR=/tmp/[dedicateddir] ./vendor/bin/phpstan analyse --memory-limit=-1
```

where `/tmp/[dedicateddir]` must be a writable temporary directory.


![PHPStan Job](../../img/phpstan1.png)

Open the build log and check for problems.

![PHPStan Log](../../img/phpstan2.png)

## PHPStan Baseline Feature

PHPStan can create a baseline file, which contain all current errors. See this [blog](https://medium.com/@ondrejmirtes/phpstans-baseline-feature-lets-you-hold-new-code-to-a-higher-standard-e77d815a5dff) entry.

To generate a new baseline file you have to execute following command:
```sh
vendor/bin/phpstan analyse --memory-limit=-1 --generate-baseline
```

With this baseline file include, we can detect new errors without having to fix all errors first.

## PHPStan Level Overview

| Level | Checks                                                                                                                                                                         |
| ----- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 0     | basic checks, unknown classes, unknown functions, unknown methods called on $this, wrong number of arguments passed to those methods and functions, always undefined variables |
| 1     | possibly undefined variables, unknown magic methods and properties on classes with __call and __get                                                                            |
| 2     | unknown methods checked on all expressions (not just $this), validating PHPDocs                                                                                                |
| 3     | return types, types assigned to properties                                                                                                                                     |
| 4     | basic dead code checking - always false instanceof and other type checks, dead else branches, unreachable code after return; etc.                                              |
| 5     | checking types of arguments passed to methods and functions                                                                                                                    |
| 6     | check for missing typehints                                                                                                                                                    |
| 7     | report partially wrong union types                                                                                                                                             |
| 8     | report calling methods and accessing properties on nullable types                                                                                                              |
