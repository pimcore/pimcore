# Feature Flags and Debug Mode 

Starting with version 5.2.0, Pimcore adds a system to handle feature flags in a unified manner. Pimcore itself uses the 
feature flag system for its own Debug Mode and Dev Mode flags. One or more features can be registered and queried on a 
`FeatureManager` object which is either accessible via `Pimcore::getFeatureManager()` or by type hinting against
`FeatureManagerInterface` in your code in a DI context.

> As soon as you set a custom state on the feature manager, the default behavior (e.g. reading debug mode setting from the
  `debug-mode.php` config file) will be omitted.
  
To define and query a set of features define a feature as a bit field enum extending the `Pimcore\FeatureToggles\Feature`
class (internally the class uses the [myclabs/php-enum library](https://github.com/myclabs/php-enum) to implement an enum-like
type in PHP). This class can be used to set and query a feature state on the feature manager. Besides the flags you define on
your feature class, the `Feature` base class will define a special `NONE` and an `ALL` value representing all flags turned
off and all flags turned on. To register a state on the feature manager, set a `FeatureState` object on the manager which
contains a `type` (e.g. `debug_mode`) and a `value` (an integer value representing one or more flags in the bit field).

> Due to integer limits on 32-bit systems, you can define a maximum of 31 flags in a feature class.

The example below uses Pimcore's own `DebugMode` and `DevMode` flags to demonstrate the API of the feature manager.

```php
<?php

// the code below could be added to startup.php, but you can this basically everywhere if it soon enough in the
// application bootstrap context

use Pimcore\FeatureToggles\Features\DebugMode;
use Pimcore\FeatureToggles\Features\DevMode;
use Pimcore\FeatureToggles\FeatureState;

$featureManager = Pimcore::getFeatureManager(); // or inject FeatureManagerInterface in a DI context

// enable selected features
$featureManager->setState(new FeatureState(
    DebugMode::getType(),
    DebugMode::MAGIC_PARAMS | DebugMode::ERROR_REPORTING | DebugMode::RENDER_DOCUMENT_TAG_ERRORS
));

// enable everything, but exclude selected features
// ALL is dynamic, so instead of the constant, the magic method to build the enum
// instance needs to be used
$featureManager->setState(new FeatureState(
    DebugMode::getType(),
    DebugMode::ALL()->getValue() & ~DebugMode::DISABLE_HTTP_CACHE & ~DebugMode::MAGIC_PARAMS
));

// enable a single feature (this will overwrite previous calls)
$featureManager->setState(FeatureState::fromFeature(DebugMode::MAGIC_PARAMS()));

// enable all flags by using the special ALL enum member
$featureManager->setState(FeatureState::fromFeature(DebugMode::ALL()));

// disable all flags by using the special NONE enum member
$featureManager->setState(FeatureState::fromFeature(DebugMode::NONE()));

// same logic applies to other feature sets
$featureManager->setState(FeatureState::fromFeature(DevMode::ALL()));

// query for a specific feature
$featureManager->isEnabled(DebugMode::MAGIC_PARAMS());

// alternative method to build the feature instance (see https://github.com/myclabs/php-enum)
$featureManager->isEnabled(new DebugMode(DebugMode::MAGIC_PARAMS));

// get the current flag value (e.g. for merging)
$featureManager->getState(DebugMode::getType())->getValue();

// pimcore shortcuts to check for debug and dev flags
Pimcore::inDebugMode(DebugMode::MAGIC_PARAMS);
Pimcore::inDevMode(DevMode::UNMINIFIED_JS);
```

Pimcore ships a default `FeatureState`, but you can build your own with custom logic as long as it implements the `FeatureStateInterface`. 
For example, you could implement a feature state which activates certain features only during a given time span.

## Migrating from existing `Pimcore::inDebugMode()` calls

As the debug mode isn't simply a boolean anymore, the call to `\Pimcore::inDebugMode()` without any arguments queries for
the `DebugMode::ALL()` state with all debug features enabled. Always specify a specific flag instead to have the condition 
match the debug feature you need.

## Create your own feature flags

First, create a feature class holding one or more flags:

```php
<?php

// src/AppBundle/FeatureToggles/AppFeatures.php

namespace AppBundle\FeatureToggles;

use Pimcore\FeatureToggles\Feature;

/**
 * This docblock adds type hints for IDEs as the enum class exposes
 * every flag as static factory method, e.g. AppFeatures::FEATURE_1().
 *
 * @method static AppFeatures FEATURE_1()
 * @method static AppFeatures FEATURE_2()
 * @method static AppFeatures FEATURE_3()
 * @method static AppFeatures FEATURE_4()
 */
class AppFeatures extends Feature
{
    // features must be powers of 2
    const FEATURE_1 = 1;
    const FEATURE_2 = 2;
    const FEATURE_3 = 4;
    const FEATURE_4 = 8;

    public static function getType(): string
    {
        return 'app';
    }
}
```

You can now set and query the features everwhere you need. As an example, in a controller:

```php
<?php

namespace AppBundle\Controller;

use AppBundle\FeatureToggles\AppFeatures;
use Pimcore\FeatureToggles\FeatureManagerInterface;
use Pimcore\FeatureToggles\FeatureState;

class TestController
{
    public function testAction(FeatureManagerInterface $featureManager)
    {
        // false
        $featureManager->isEnabled(AppFeatures::FEATURE_1());

        // enable FEATURE_1 and FEATURE_2
        $featureManager->setState(new FeatureState(
            AppFeatures::getType(),
            AppFeatures::FEATURE_1 | AppFeatures::FEATURE_2
        ));

        // true
        $featureManager->isEnabled(AppFeatures::FEATURE_1());
    }
}
```
