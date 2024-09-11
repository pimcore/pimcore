# Application Testing

Pimcore applications can be tested with any PHP testing solution, but this page demonstrates 2 viable approaches:

1. [Symfony's default testing setup](https://symfony.com/doc/current/testing.html) with PHPUnit
2. [Codeception](https://codeception.com/) (which is based on PHPUnit) for more advanced features like Selenium testing by using Codeception's module system

In general it's recommended to start with the first approach as it is simpler to set up and to get started with testing. Note, however, that the PHPUnit setup does not include any out-of-the-box solution how to prepare your application for tests (e.g. how to make sure you are always testing with the same reproducible data set), so that's up to you. You could prepare test data in your bootstrap file or run some script before you start the test suite.

In addition to Codeception's general features, Pimcore's Codeception modules provide a set of helpers to bootstrap a Pimcore installation from an empty installation. The `Pimcore` module is able to drop and re-create the database and addtional modules like the `ClassManager` provide helper code to create Pimcore classes from JSON exports. As the DB initialization is configurable, you should be able to use the module as you need it (e.g. by bootstrapping your application yourself or by just running tests without any DB/data initialization logic. You can find examples how to use those modules by looking through [Pimcore's test setup](https://github.com/pimcore/pimcore/tree/11.x/tests).

## PHPUnit

As Pimcore is a standard Symfony application, you can use Symfony's PHPUnit testing setup exactly as described in
[Symfony's Testing Documentation](https://symfony.com/doc/current/testing.html). All you need to do is to create a custom
bootstrap file to ensure the Pimcore startup process has everything it needs. Start by adding Symfony's PHPUnit bridge
to your project:

```bash
$ composer require --dev 'symfony/phpunit-bridge:*'
```

With `symfony/phpunit-bridge` comes `vendor/bin/simple-phpunit` which uses its own PHPUnit version. For `simple-phpunit` to use the right version, you need to exclude `phpunit` from the autoloader's classmap and afterwards update the autoloader with `composer dump-autoload -o`

```json
  "autoload": {
    ...

    "exclude-from-classmap": [
      "vendor/phpunit"
    ]
  }
```

Next, add a PHPUnit config file named `phpunit.xml.dist` in the root directory of your project. The config file below
expects your tests in a `tests/` directory and processes files in `src/` when calculating code coverage reports.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/7.4/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuite name="default">
        <directory suffix="Test.php">tests</directory>
    </testsuite>

    <filter>
        <allowlist processUncoveredFilesFromAllowlist="true">
            <directory suffix=".php">src</directory>
        </allowlist>
    </filter>

    <php>
        <env name="SYMFONY_PHPUNIT_VERSION" value="7.4" />
    </php>
</phpunit>
``` 

Now we're ready to write a first test. Assuming we have an `App\Calculator` class which has an `add(int $a, int $b): int`
method, add a test in `tests/App/CalculatorTest.php`. It is not necessary but recommended to resemble the directory
structure from your application code in your test directory.

```php
<?php

// tests/App/CalculatorTest.php

namespace Tests\App;

use App\Calculator;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    private Calculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new Calculator();
    }

    public function testAdd(): void
    {
        $this->assertEquals(15, $this->calculator->add(10, 5));
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAddWithProvider(int $a, int $b, int $expected): void
    {
        $this->assertEquals($expected, $this->calculator->add($a, $b));
    }

    public function addDataProvider(): array
    {
        return [
            [1, 2, 3],
            [10, 5, 15],
            [-5, 5, 0],
            [5, -5, 0],
            [0, 10, 10],
            [-50, -50, -100],
            [-50, 10, -40]
        ];
    }
}
```

This is everything you need to write simple unit tests which do not depend on Pimcore's environment. Just run the tests
with Symfony's PHPUnit wrapper:

```
$ vendor/bin/simple-phpunit

PHPUnit 7.4.5 by Sebastian Bergmann and contributors.

Testing default
........                                                            8 / 8 (100%)

Time: 174 ms, Memory: 10.00MB

OK (8 tests, 8 assertions)
```

### Bootstrapping Pimcore

If you want to write more advanced tests involving Pimcore objects or Symfony's container - e.g. functional tests testing
controllers - you need to make sure Pimcore is properly bootstrapped before tests are run. Alter the config file to point
to a custom bootstrap file and to add environment variables needed to bootstrap the application:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/7.4/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuite name="default">
        <directory suffix="Test.php">tests</directory>
    </testsuite>

    <filter>
        <allowlist processUncoveredFilesFromAllowlist="true">
            <directory suffix=".php">src</directory>
        </allowlist>
    </filter>

    <php>
        <!-- adjust as needed -->
        <env name="SYMFONY_PHPUNIT_VERSION" value="7.4" />
        <env name="PIMCORE_PROJECT_ROOT" value="." />
        <env name="KERNEL_DIR" value="app" />
        <env name="KERNEL_CLASS" value="AppKernel" />
    </php>
</phpunit>
``` 

The example above expects a `tests/bootstrap.php` file which is executed before tests are run. Create the bootstrap file
with the following content (customize as needed):

```php
<?php

// tests/bootstrap.php

include "../../vendor/autoload.php";

\Pimcore\Bootstrap::setProjectRoot();
\Pimcore\Bootstrap::bootstrap();
```

Now we're ready to write tests which depend on a bootstrapped environment. Symfony already provides `KernelTestCase` and
`WebTestCase` as base classes for tests involving the container, but Pimcore expects the container to be set via `Pimcore::setContainer()`
after bootstrapping. This is automatically done for you if you use `Pimcore\Test\KernelTestCase` and `Pimcore\Test\WebTestCase`
as base classes, otherwise you need to make sure to overwrite `createKernel` and set the container on the `Pimcore` class.

Let's create a functional test which tests a controller response (see Symfony's test documentation for details). The example
below assumes an installation running the `demo-basic` install profile.

```php
<?php

// tests/App/Controller/ContentControllerTest.php

declare(strict_types=1);

namespace Tests\App\Controller;

use Pimcore\Test\WebTestCase;

class ContentControllerTest extends WebTestCase
{
    public function testRedirectFromEn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en');

        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect();

        $this->assertEquals('/', $client->getRequest()->getPathInfo());
    }

    public function testPortal(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful(), 'response status is 2xx');

        $this->assertTrue($response->headers->contains('X-Custom-Header', 'Foo'));
        $this->assertTrue($response->headers->contains('X-Custom-Header', 'Bar'));
        $this->assertTrue($response->headers->contains('X-Custom-Header2', 'Bazinga'));

        $this->assertEquals(
            1,
            $crawler->filter('h1:contains("Ready to be impressed?")')->count()
        );
    }
}
```

If you would run the test suite now, it would fail with a list of errors as the test can't connect to the database. This 
is because the tests run in the `test` environment and that environment is set up to use a different database connection
which is defined as `PIMCORE_TEST_DB_DSN` environment variable by default (see [config/packages/test/config.yaml](https://github.com/pimcore/pimcore/blob/11.x/.github/ci/files/config/packages/test/config.yaml#L19)).

You can either define the database DSN as environment variable on your shell, hardcode it into the PHPUnit config file (not
recommended) or remove/alter the customized `doctrine` section from `config/packages/test/config.yaml` completely. What to use depends highly on your environment and your tests - if you have
tests which make changes to the database you'll probably want to run them on a different database with a predefined data
set. The example below just passes the DB connection as env variable:

```
$ PIMCORE_TEST_DB_DSN="mysql://username:password@localhost/pimcore" vendor/bin/simple-phpunit
PHPUnit 7.4.5 by Sebastian Bergmann and contributors.

Testing default
..........                                                        10 / 10 (100%)

Time: 2.69 seconds, Memory: 36.00MB

OK (10 tests, 15 assertions)
```

For more information you can follow [Symfony's Testing Documentation](https://symfony.com/doc/current/testing.html). Just keep
in mind to make sure Pimcore is properly bootstrapped before tests are run.


## Codeception

For Pimcore's core tests, Pimcore uses Codeception which wraps PHPUnit and adds a lot of nice features, especially for 
organizing tests and for adding helper code which can be used from tests. You can basically use the same setup as Pimcore's
core by defining a custom test suite and by using Pimcore's core helpers. The most important helper is `\Pimcore\Tests\Support\Helper\Pimcore`
which extends Codeception's [Symfony Module](https://codeception.com/docs/modules/Symfony) for functional testing and adds
logic to bootstrap Pimcore and to drop/re-create the database and class directory to an empty installation to have every 
test suite start from a clean installation. 

<div class="alert alert-warning">
<strong>WARNING:</strong> Pimcore's codeception setup is targeted for CI use, where database and data structures are created
from an empty installation. This means unless not configured otherwise the codeception helpers will <strong>DROP</strong> and
re-create the database and <strong>DELETE</strong> class files. Use with caution and only use on a test setup!
</div>  

To get started, add a `tests/codeception.dist.yml` file for your custom test setup which defines directories and basic 
behaviour:

```yaml
# tests/codeception.dist.yml

namespace: Tests
support_namespace: Support
actor_suffix: Tester
paths:
    tests: .
    output: ./_output
    data: ./Support/Data
    support: ./Support
    envs: ./_envs
settings:
    bootstrap: _bootstrap.php
    colors: true
params:
    - env
extensions:
    enabled:
        - Codeception\Extension\RunFailed
```

Pimcore already ships a `codeception.dist.yml` which is set up to run Pimcore's core tests. You might want to change this
to run your own test setup by default:

```yaml
# codeception.dist.yml

settings:
    memory_limit: 1024M
    colors: true
paths:
    output: var/log
include:
  - tests
```

You can create any amount of test suites in Codeception. To match the PHPUnit example above, we'll create 2 test suites
`unit` and `functional` for unit and functional testing. The following commands should create the basic directory/file 
structure in `tests/`:

```bash
$ vendor/bin/codecept -c tests/codeception.dist.yml generate:suite unit
$ vendor/bin/codecept -c tests/codeception.dist.yml generate:suite functional
```

The config file above references a `_bootstrap.php` file. Create `tests/_bootstrap.php` with the following contents to make
sure Pimcore can be bootstrapped during tests. Adjust according to your needs.


```php
<?php

// tests/_bootstrap.php

use Pimcore\Tests\Support\Util\Autoloader;

// define project root which will be used throughout the bootstrapping process
define('PIMCORE_PROJECT_ROOT', realpath(__DIR__ . '/..'));

// set the used pimcore/symfony environment
putenv('APP_ENV=test');


require_once PIMCORE_PROJECT_ROOT . '/vendor/autoload.php';

\Pimcore\Bootstrap::setProjectRoot();
\Pimcore\Bootstrap::bootstrap();
\Pimcore\Bootstrap::kernel();

// add the core pimcore test library to the autoloader - this could also be done in composer.json's autoload-dev section
// but is done here for demonstration purpose
require_once PIMCORE_PROJECT_ROOT . '/vendor/pimcore/pimcore/tests/Support/Util/Autoloader.php';

Autoloader::addNamespace('Pimcore\Tests\Support', PIMCORE_PROJECT_ROOT . '/vendor/pimcore/pimcore/tests/Support');
```

The `tests/unit.suite.yml` should be fine for a standard unit testing setup without dependencies, but we need to alter the
functional test suite to initialize a test database and to boot Pimcore's kernel before running tests. Configure the 
suite to use the `\Pimcore\Tests\Support\Helper\Pimcore` helper:

```yaml
# tests/functional.suite.yml

actor: FunctionalTester
modules:
    enabled:
        - \Tests\Support\Helper\Functional
        - \Pimcore\Tests\Support\Helper\Pimcore:
            # CAUTION: the following config means the test runner
            # will drop and re-create the Pimcore DB and purge var/classes
            # use only in a test setup (e.g. during CI)!
            connect_db: true
            initialize_db: true
            purge_class_directory: true
            # If true, it will create database structures for all definitions
            setup_objects: false
```

This will set up a functional test which sends a request directly through Symfony's kernel (similar to the PHPUnit setup above). However, Codeception makes it easy to use a full-blown browser for acceptance testing by configuring additional modules such as the [WebDriver](https://codeception.com/docs/modules/WebDriver) module for Selenium testing. 

Let's start writing tests by adding a simple unit test:

```php
<?php

// tests/unit/ExampleTest.php

namespace Tests\Unit\App;

use Codeception\Test\Unit;

/**
 * This test is just a dummy for demonstration purposes and
 * doesn't actually test any class.
 */
class ExampleTest extends Unit
{
    /**
     * Tester actor exposing methods added by helpers
     */
    protected \Tests\UnitTester $tester;

    public function testPhpCanCalculate(): void
    {
        $this->assertEquals(15, 10 + 5);
        $this->assertEquals(100, pow(10, 2));
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testPhpCanAddWithProvider(int $a, int $b, int $expected): void
    {
        $this->assertEquals($expected, $a + $b, sprintf('%d + %d = %d', $a, $b, $expected));
    }

    public function testSomethingElse(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $obj1->obj = $obj3;
        $obj2->obj = $obj3;

        $this->assertNotNull($obj1);
        $this->assertNotNull($obj2);
        $this->assertNotNull($obj3);

        $this->assertNotSame($obj1, $obj2);
        $this->assertSame($obj1->obj, $obj2->obj);
        $this->assertSame($obj3, $obj1->obj);
        $this->assertSame($obj3, $obj2->obj);
    }

    public function testException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This test is about to fail');

        throw new \RuntimeException('This test is about to fail');
    }

    public function addDataProvider(): array
    {
        return [
            [1, 2, 3],
            [10, 5, 15],
            [-5, 5, 0],
            [5, -5, 0],
            [0, 10, 10],
            [-50, -50, -100],
            [-50, 10, -40]
        ];
    }
}
```

And a functional test testing the empty index page. As the `Pimcore` helper is based Codeception's `Symfony` module you 
can directly use Symfony tests such as `$I->amOnRoute()`.

```php
<?php

// tests/functional/App/IndexPageCest.php

namespace Tests\Functional\App;

use Tests\FunctionalTester;

class IndexPageCest
{
    public function testFrontpage(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->canSeeResponseCodeIs(200);
        $I->amOnRoute('document_1');

        $I->seeElement('#site #logo a', ['href' => 'http://www.pimcore.com/']);
        $I->seeElement('#site #logo img', ['src' => '/bundles/pimcoreadmin/img/logo-claim-gray.svg']);
    }
}
```

As in the PHPUnit setup, the test setup expects the database connection as env variable by default. Run your new test setup
by configuring the DB DSN before running codeception:

```
$ PIMCORE_TEST_DB_DSN="mysql://username:password@localhost/pimcore" vendor/bin/codecept run -c tests/codeception.dist.yml

Codeception PHP Testing Framework v2.3.8
Powered by PHPUnit 7.4.5 by Sebastian Bergmann and contributors.

  [DB] Initializing DB pimcore5_test
  [DB] Dropping DB pimcore5_test
  [DB] Creating DB pimcore5_test
  [DB] Successfully connected to DB pimcore5_test
  [DB] Initialized the test DB pimcore5_test
  [INIT] Purging class directory var/classes

Tests.functional Tests (1) --------------------------------------------------------------------------------------------------------------------------
Testing Tests.functional
✔ IndexPageCest: Test frontpage (3.23s)
-----------------------------------------------------------------------------------------------------------------------------------------------------

Tests.unit Tests (10) -------------------------------------------------------------------------------------------------------------------------------
✔ ExampleTest: Php can calculate (0.14s)
✔ ExampleTest: Php can add with provider | #0 (0.00s)
✔ ExampleTest: Php can add with provider | #1 (0.00s)
✔ ExampleTest: Php can add with provider | #2 (0.00s)
✔ ExampleTest: Php can add with provider | #3 (0.00s)
✔ ExampleTest: Php can add with provider | #4 (0.00s)
✔ ExampleTest: Php can add with provider | #5 (0.00s)
✔ ExampleTest: Php can add with provider | #6 (0.00s)
✔ ExampleTest: Something else (0.01s)
✔ ExampleTest: Exception (0.01s)
-----------------------------------------------------------------------------------------------------------------------------------------------------


Time: 6.96 seconds, Memory: 44.25MB

OK (11 tests, 21 assertions)
```
