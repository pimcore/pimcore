# Data Providers

A data provider is a service implementing the [`DataProviderInterface`](https://github.com/pimcore/pimcore/blob/master/lib/Targeting/DataProvider/DataProviderInterface.php).
Components (e.g. conditions) which implement the [`DataProviderDependentInterface`](https://github.com/pimcore/pimcore/blob/master/lib/Targeting/DataProviderDependentInterface.php)
can define a set of data providers they depend on, triggering the data provider to load its data before the component
is used.

A data provider does not directly return its value, but is expected to set it on the `VisitorInfo` instance instead. As
best practice, the core data providers expose their storage key as constant. This constant is used to store and retrieve
the data from the `VisitorInfo` storage. As example: the [GeoIP](https://github.com/pimcore/pimcore/blob/master/lib/Targeting/DataProvider/GeoIp.php)
data provider defines the [GeoIP::PROVIDER_KEY](https://github.com/pimcore/pimcore/blob/master/lib/Targeting/DataProvider/GeoIp.php#L28)
constant which is used when storing and retrieving the data.

## Implementing a Data Provider

A data provider is simply a class implementing the `DataProviderInterface` which is registered as service. Basically a
data provider can do anything, however the core data providers do the following:

* They store their information on a storage key which is exposed as constant
* They always set their content key. If no data can be resolved (e.g. GeoIP is unable to resolve a location), `null` is set.
* Before loading data, core providers check if there already is an entry for the own storage key and abort loading if that
 is the case.

As an example let's assume the `DateTime` used in the `TimeOfTheDay` condition (as implemented on the [Conditions](./03_Conditions.md)
page) is more complex than a simple `new DateTime()`, i.e. because the date is fetched from a third party or involves
calculation logic. Instead of creating it inside the condition which does not have access to services we move it to a
reusable `DateTime` data provider which stores the current `DateTime` on the `VisitorInfo`.

```php
<?php

// src/AppBundle/Targeting/DataProvider/DateTime.php

namespace AppBundle\Targeting\DataProvider;

use Pimcore\Targeting\DataProvider\DataProviderInterface;
use Pimcore\Targeting\Model\VisitorInfo;

class DateTime implements DataProviderInterface
{
    const PROVIDER_KEY = 'datetime';

    public function load(VisitorInfo $visitorInfo)
    {
        if ($visitorInfo->has(self::PROVIDER_KEY)) {
            // abort if there already is data for this provider 
            return;
        }

        // assume creating the date is more complex (e.g. involves other services
        // which are injected via DI)
        $visitorInfo->set(self::PROVIDER_KEY, new \DateTimeImmutable());
    }
}
```

Next, register your new data provider as service:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    AppBundle\Targeting\DataProvider\DateTime: ~
```

And register the provider to the targeting engine with its provider key:

```yaml
pimcore:
    targeting:
        data_providers:
            datetime: AppBundle\Targeting\DataProvider\DateTime
```


## Consuming a Data Provider

To consume a data provider, implement the `DataProviderDependentInterface` in your components and specify a list of data
providers to use. As an example, let's update the `TimeOfTheDay` condition to fetch the current `DateTime` from our new
provider:

```php
<?php

// src/AppBundle/Targeting/Condition/TimeOfTheDay.php

namespace AppBundle\Targeting\Condition;

use AppBundle\Targeting\DataProvider\DateTime;
use Pimcore\Targeting\Condition\AbstractVariableCondition;
use Pimcore\Targeting\DataProviderDependentInterface;
use Pimcore\Targeting\Model\VisitorInfo;

class TimeOfTheDay extends AbstractVariableCondition implements DataProviderDependentInterface
{
    // ...

    public function getDataProviderKeys(): array
    {
        return [DateTime::PROVIDER_KEY];
    }

    public function match(VisitorInfo $visitorInfo): bool
    {
        $dateTime = $visitorInfo->get(DateTime::PROVIDER_KEY);
        if (!$dateTime) {
            // provider did not provide a valid date - nothing to match against
            return false;
        }

        $hour = (int)$dateTime->format('H');

        if ($hour >= $this->hour) {
            $this->setMatchedVariable('hour', $hour);

            return true;
        }

        return false;
    }
}
```

As you can see, instead of creating a new `DateTime` instance, the condition now expects an instance on the `DateTime::PROVIDER_KEY`
storage on the `VisitorInfo`. The targeting engine takes care of loading every provider the condition depends on before
starting to match. 

The `DataProviderDependentInterface` can not only be used from conditions, but also from action handlers and other 
data providers (a data provider can depend on another data providers' data).
