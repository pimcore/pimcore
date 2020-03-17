# Core Testing

Pimcore uses [Codeception](https://codeception.com/) for testing its core features.

## Requirements

1. A Pimcore installation. Read this [guide](../../01_Getting_Started/00_Installation.md) for instructions.
2. A **dedicated database** used only for testing. In other words, if the Pimcore installation is not only used for testing, create a separate database!
3. Redis cache (optional, but needed for executing cache tests)

## Executing tests

#### Important notes

> Read this before you start.

##### Test directories

Always set

```
PIMCORE_TEST=1
```

This will switch special directories used for testing (like /var/classes) and prevent that you existing installation gets messed up. 

##### Error reporting 

always add 

```
PIMCORE_PHP_ERROR_REPORTING=32767
```

if not preset by your system.


#### Run all tests

This will run all tests.
```
PIMCORE_TEST_DB_DSN="mysql://[USERNAME]:[PASSWORD]@[HOST]/[DBNAME]" PIMCORE_ENVIRONMENT=test PIMCORE_TEST=1 vendor/bin/codecept run -c vendor/pimcore/pimcore
```

#### Only run a specific suite

Only runs the `model` tests. For a list of suites see the list below.

```
PIMCORE_TEST_DB_DSN="mysql://[USERNAME]:[PASSWORD]@[HOST]/[DBNAME]" PIMCORE_ENVIRONMENT=test PIMCORE_TEST=1 vendor/bin/codecept run -c vendor/pimcore/pimcore model
```

#### Only run a specific test group

This can be a subset of a suite. You also have the option to provide a comma-seperated list of groups.
For an overview of available groups see the table below. 

```
PIMCORE_TEST_DB_DSN="mysql://[USERNAME]:[PASSWORD]@[HOST]/[DBNAME]" PIMCORE_ENVIRONMENT=test PIMCORE_TEST=1 vendor/bin/codecept run -c vendor/pimcore/pimcore rest -g dataTypeIn    
```

##### Redis Cache tests

For Redis, the `PIMCORE_TEST_CACHE_REDIS_DATABASE` option is mandatory. Set to a value that does not conflict to any
other Redis DBs on your system.

```
PIMCORE_TEST_DB_DSN="mysql://[USERNAME]:[PASSWORD]@[HOST]/[DBNAME]" PIMCORE_ENVIRONMENT=test PIMCORE_TEST=1 PIMCORE_TEST_CACHE_REDIS_DATABASE=1 vendor/bin/codecept run -c vendor/pimcore/pimcore cache    
```


#### Important Environment Variables

| Env Variable                              | Example          | Comment                                                                                                                        |
|-------------------------------------------|------------------|--------------------------------------------------------------------------------------------------------------------------------|
| PIMCORE_ENVIRONMENT                       | test             | Test environment                                                                                                               |
| PIMCORE_PHP_ERROR_REPORTING               | 32767            | Should be set to E_ALL because Travis uses the same setting.                                                                   |
| PIMCORE_TEST                              | 1                | **important** this will switch several directories (like /var/classes)                                                         |
| PIMCORE_TEST_SKIP_DB                      | 1                | Skips DB setup. This does not skip the db-related tests but it<br>reduces the setup time for tests that don't need a database. |
| PIMCORE_TEST_CACHE_REDIS_DATABASE         | 1                | **required for REDIS tests**                                                                                                   |
| PIMCORE_TEST_CACHE_REDIS_PORT             | defaults to 6379 | Redis port                                                                                                                     |
| PIMCORE_TEST_CACHE_REDIS_PERSISTENT       |                  |                                                                                                                                |
| PIMCORE_TEST_CACHE_REDIS_FORCE_STANDALONE | 0                |                                                                                                                                |
| PIMCORE_TEST_CACHE_REDIS_CONNECT_RETRIES  | defaults to 1    |                                                                                                                                |
| PIMCORE_TEST_CACHE_REDIS_TIMEOUT          | defaults to 2.5  |                                                                                                                                |
| PIMCORE_TEST_CACHE_REDIS_READ_TIMEOUT     | defaults to 0    |                                                                                                                                |
| PIMCORE_TEST_CACHE_REDIS_PASSWORD         |                  |                                                                                                                                |
| ...                                       |                  |                                                                                                                                |                        

#### Suites

The tests are organized into suites, each one covering specific areas of the core.

| Suite name | Description                                                    |
|------------|----------------------------------------------------------------|
| cache      | Cache tests                                                    |
| ecommerce  | Ecommerce bundle tests                                         |
| model      | Dataobject tests                                               |
| rest       | REST Webservice API tests                                      |
| service    | Test covering common or shared element tasks (versioning, ...) |
| unit       | Other tests (may need restructuring)                           |
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

## Travis

Pimcore uses [Travis CI](https://travis-ci.com/) for continuous integration.
Open https://travis-ci.com/pimcore/pimcore for the current build status. 

### Test Matrix

The build matrix (which can change at any time) consists of a mixture of

* different PHP versions (7.2, 7.3, 7.4)
* different Symfony versions (3.4 and 4)

In addition it
* verifies the state of the documentation (broken links, etc) 
* runs [PHPStan](https://github.com/phpstan/phpstan) (PHP Static Analysis Tool). For a list verification performed by
PHPStan see this [list](https://gist.github.com/carusogabriel/62698312f451589afd956eddac2dc07a). Current level 1. 

### Build Artifacts

Travis will automatically upload build artifacts to Amazon S3 (currently everything in `var/logs`).

Look for something like this in your job output and open it in your web browser.

![Artifact](../../img/travis_artifact.png)

## Providing new tests & extending existing ones

In general, contributions in form extending and improving tests is highly appreciated.
Please follow the structure and principles described above.

If you have the extend the data model then please have a look at [Model.php](https://github.com/pimcore/pimcore/blob/master/tests/_support/Helper/Model.php).
There you will find all class definitions used for testing.

### Perform PHPStan Analysis 

First, get a copy of this [sample configuration file](../../../Samples/phpstan.local.neon) and place it in your `PIMCORE_PROJECT_ROOT` root directory.

Replace all occurrences of `PIMCORE_PROJECT_ROOT` with the real directory according to your setup.

Add dependencies:
```sh
# minimum
composer require "phpstan/phpstan:^0.12" "phpstan/phpstan-symfony:^0.12"

# required if you want to do a full analysis
composer require "heidelpay/heidelpay-php:^1.2.5.1" "klarna/checkout:^3.0.0" "elasticsearch/elasticsearch:2.0.0" "paypal/paypal-checkout-sdk:^1" "mpay24/mpay24-php:^4.2" "composer/composer:*"
```

Run
```sh
TMPDIR=/tmp/[dedicateddir] ./vendor/bin/phpstan analyse -c phpstan.local.neon vendor/pimcore/pimcore/bundles/ vendor/pimcore/pimcore/lib/ vendor/pimcore/pimcore/models/ -l 1 --memory-limit=-1
```

where `/tmp/[dedicateddir]` must be a writable temporary directory.

> Note regarding PRs: Please try to meet all 
level 3 requirements (run it with `-l 3` instead) for all files you touch or add.

Travis also performs level 2 tests but allows them to fail in case that not all rules are satisfied.

![PHPStan Job](../../img/phpstan1.png)

Open the build log and check for problems.

![PHPStan Log](../../img/phpstan2.png) 

## PHPStan Baseline Feature

PHPStan can create a baseline file, which contain all current errors. See this [blog](https://medium.com/@ondrejmirtes/phpstans-baseline-feature-lets-you-hold-new-code-to-a-higher-standard-e77d815a5dff) entry.
 
To generate a new baseline file you have to do following steps:

1. Deactivate baseline file include (comment out) in phpstan.neon
    ```sh
    sed -e "s?- phpstan-baseline.neon?#- phpstan-baseline.neon?g" -i phpstan.neon
    ```
2. Generate new baseline file
    ```sh
    vendor/bin/phpstan analyse -c .travis/phpstan.s4.travis.neon bundles/ lib/ models/ -l 3 --memory-limit=-1 --error-format baselineNeon > phpstan-baseline.neon
    ```
3. Activate baseline file include in phpstan.neon
    ```sh
    sed -e "s?#- phpstan-baseline.neon?- phpstan-baseline.neon?g" -i phpstan.neon
    ```

With this baseline file include, Travis can detect new errors without having to fix all errors first.

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
