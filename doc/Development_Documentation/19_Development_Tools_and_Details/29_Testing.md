# Testing

## PHPUnit

As Pimcore is a standard Symfony application, you can use Symfony's PHPUnit testing setup exactly as described in
[Symfony's Testing Documentation](https://symfony.com/doc/3.4/testing.html). All you need to do is to create a custom
bootstrap file to ensure the Pimcore startup process has everything it needs. Start by adding Symfony's PHPUnit bridge
to your project:

```
$ composer require --dev 'symfony/phpunit-bridge:^3.4'
```

Next, add a PHPUnit config file named `phpunit.xml.dist` in the root directory of your project. The config file above
expects your tests in a `tests/` directory and processes files in `src/` when calculating code coverage reports.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/6.5/phpunit.xsd"
         bootstrap="../../vendor/autoload.php"
         colors="true">
    <testsuite name="default">
        <directory suffix="Test.php">tests</directory>
    </testsuite>

    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">src</directory>
        </whitelist>
    </filter>
</phpunit>
``` 

Now we're ready to write a first test. Assuming we have an `AppBundle\Calculator` class which has an `add(int $a, int $b): int`
method, add a test in `tests/AppBundle/CalculatorTest.php`. It is not necessary but recommended to resemble the directory
structure from your application code in your test directory.

```php
<?php

// tests/AppBundle/CalculatorTest

namespace Tests\AppBundle;

use AppBundle\Calculator;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    /**
     * @var Calculator
     */
    private $calculator;

    protected function setUp()
    {
        $this->calculator = new Calculator();
    }

    public function testAdd()
    {
        $this->assertEquals(15, $this->calculator->add(10, 5));
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAddWithProvider(int $a, int $b, int $expected)
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

PHPUnit 6.5.6 by Sebastian Bergmann and contributors.

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
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/6.5/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuite name="default">
        <directory suffix="Test.php">tests</directory>
    </testsuite>

    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">src</directory>
        </whitelist>
    </filter>

    <php>
        <!-- adjust as needed -->
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

// use the env var set from the XML config, but transform it into an absolute path
define(
    'PIMCORE_PROJECT_ROOT',
    realpath(getenv('PIMCORE_PROJECT_ROOT'))
);

// bootstrap Pimcore
require_once PIMCORE_PROJECT_ROOT . '/pimcore/config/bootstrap.php';
```

Now we're ready to write tests which depend on a bootstrapped environment. Symfony already provides `KernelTestCase` and
`WebTestCase` as base classes for tests involving the container, but Pimcore expects the container to be set via `Pimcore::setContainer()`
after bootstrapping. This is automatically done for you if you use `Pimcore\Test\KernelTestCase` and `Pimcore\Test\WebTestCase`
as base classes, otherwise you need to make sure to overwrite `createKernel` and set the container on the `Pimcore` class.

Let's create a functional test which tests a controller response (see Symfony's test documentation for details). The example
below assumes an installation running the `demo-basic` install profile.

```php
<?php

declare(strict_types=1);

namespace Tests\AppBundle\Controller;

use Pimcore\Test\WebTestCase;

class ContentControllerTest extends WebTestCase
{
    public function testRedirectFromEn()
    {
        $client = static::createClient();
        $client->request('GET', '/en');

        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect();

        $this->assertEquals('/', $client->getRequest()->getPathInfo());
    }

    public function testPortal()
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

If you would run the test suite now, you would get a list of errors saying the test can't connect to the database. This 
is because the tests run in the `test` environment and that environment is set up to use a different database connection
which is defined as `PIMCORE_TEST_DB_DSN` by default (see [config_test.yml](https://github.com/pimcore/pimcore/blob/master/app/config/config_test.yml)).

You can either define the DB DSN as environment variable on your shell, hardcode it into the PHPUnit config file (not
recommended) or remove/alter the customized `doctrine` section from `config_test.yml` completely to have Pimcore connect
to the DB defined in `system.php` during tests. What to use depends highly on your environment and your tests - if you have
tests which make changes to the database you'll probably want to run them on a different database which a predefined data
set. The example below just passes the DB connection as env variable:

```
$ PIMCORE_TEST_DB_DSN="mysql://username:password@localhost/pimcore" vendor/bin/simple-phpunit
PHPUnit 6.5.6 by Sebastian Bergmann and contributors.

Testing default
..........                                                        10 / 10 (100%)

Time: 2.69 seconds, Memory: 36.00MB

OK (10 tests, 15 assertions)
```

For more information you can follow [Symfony's Testing Documentation](https://symfony.com/doc/3.4/testing.html). Just keep
in mind to make sure Pimcore is properly bootstrapped before tests are run.


## Codeception

For Pimcore's core tests, Pimcore uses Codeception which wraps PHPUnit and adds a lot of nice features, especially for 
organizing tests and for adding helper code which can be used from tests. You can basically use the same setup as Pimcore's
core by defining a custom test suite and by using Pimcore's core helpers. The most important helper is `\Pimcore\Tests\Helper\Pimcore`
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
actor: Tester
paths:
    tests: .
    log: ./_output
    data: ./_data
    support: ./_support
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
    log: var/logs
include:
  - tests
```

You can create any amount of test suites in Codeception. To match the PHPUnit example above, we'll create 2 test suites
`unit` and `functional` for unit and functional testing. The following commands should create the basic directory/file 
structure in `tests/`:

```
$ vendor/bin/codecept -c tests/codeception.dist.yml generate:suite unit
$ vendor/bin/codecept -c tests/codeception.dist.yml generate:suite functional
```

The config file above references a `_bootstrap.php` file. Create `tests/_bootstra.php` with the following contents to make
sure Pimcore can be bootstrapped during tests. Adjust according to your needs.


```php
<?php

// tests/_bootstrap.php

use Pimcore\Tests\Util\Autoloader;

// define project root which will be used throughout the bootstrapping process
define('PIMCORE_PROJECT_ROOT', realpath(__DIR__ . '/..'));

// set the used pimcore/symfony environment
putenv('PIMCORE_ENVIRONMENT=test');

// add the pimcore tests path to the autoloader - this could also be done in composer.json's autoload-dev section
// but is done here for demonstration purpose
require_once PIMCORE_PROJECT_ROOT . '/pimcore/tests/_support/Util/Autoloader.php';

Autoloader::addNamespace('Pimcore\Tests', PIMCORE_PROJECT_ROOT . '/pimcore/tests/_support');

require_once PIMCORE_PROJECT_ROOT . '/vendor/autoload.php';
```

The `tests/unit.suite.yml` should be fine for a standard unit testing setup without dependencies, but we need to alter 
functional test suite to initialize a test database and to boot the Pimcore kernel before running tests. Configure the 
suite to use the `\Pimcore\Tests\Helper\Pimcore` helper:

```yaml
# tests/functional.suite.yml

actor: FunctionalTester
modules:
    enabled:
        - \Tests\Helper\Functional
        - \Pimcore\Tests\Helper\Pimcore:
            # CAUTION: the following config means the test runner
            # will drop and re-create the Pimcore DB and purge var/classes
            # use only in a test setup (e.g. during CI)!
            connect_db: true
            initialize_db: true
            purge_class_directory: true
```

This should be everything you need to run tests. Let's start to add a simple unit test:

```php
<?php

namespace Tests\Unit\AppBundle;

use Codeception\Test\Unit;

/**
 * This test is just a dummy for demonstration purposes and
 * doesn't actually test any class.
 */
class ExampleTest extends Unit
{
    /**
     * Tester actor exposing methods added by helpers
     *
     * @var \Tests\UnitTester
     */
    protected $tester;

    public function testPhpCanCalculate()
    {
        $this->assertEquals(15, 10 + 5);
        $this->assertEquals(100, pow(10, 2));
    }

    /**
     * @dataProvider addDataProvider
     *
     * @param int $a
     * @param int $b
     * @param int $expected
     */
    public function testPhpCanAddWithProvider(int $a, int $b, int $expected)
    {
        $this->assertEquals($expected, $a + $b, sprintf('%d + %d = %d', $a, $b, $expected));
    }

    public function testSomethingElse()
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

    public function testException()
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

// tests/functional/AppBundle/IndexPageCest.php

namespace Tests\Functional\AppBundle;

use Tests\FunctionalTester;

class IndexPageCest
{
    public function testFrontpage(FunctionalTester $I)
    {
        $I->amOnPage('/');
        $I->canSeeResponseCodeIs(200);
        $I->amOnRoute('document_1');

        $I->seeElement('#site #logo a', ['href' => 'http://www.pimcore.com/']);
        $I->seeElement('#site #logo img', ['src' => '/pimcore/static6/img/logo-claim-gray.svg']);
    }
}
```

As in the PHPUnit setup, the test setup expects the database connection as env variable by default. Run your new test setup
by configuring the DB DSN before running codeception:

```
$ PIMCORE_TEST_DB_DSN="mysql://username:password@localhost/pimcore" vendor/bin/codecept run -c tests/codeception.dist.yml

Codeception PHP Testing Framework v2.3.8
Powered by PHPUnit 6.5.6 by Sebastian Bergmann and contributors.

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
