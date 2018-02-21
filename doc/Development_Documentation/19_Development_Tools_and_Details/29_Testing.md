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

