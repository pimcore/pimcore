# Conditions

Basically a condition a class implementing the [`ConditionInterface`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Targeting/Condition/ConditionInterface.php).
Please have a look at [existing implementations](https://github.com/pimcore/pimcore/tree/master/pimcore/lib/Pimcore/Targeting/Condition)
to get an idea how to implement your own condition. The most important method is `match` which receives a `VisitorInfo`
and needs to return a bool to determine if it matches or not.

After implementing your condition, you need to register it to the system with the following configuration:

```yaml
pimcore:
    targeting:
        conditions:
            custom: MyVendor\MyBundle\Targeting\Conition\Custom
```

## Building a Condition Instance

When an instance of your condition is build, by default the [`ConditionFactory`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Targeting/ConditionFactory.php)
will call the static `fromConfig()` method with the data configured in the admin UI. Please avoid injecting any services
or custom data into your conditions, but use the data provider system instead to add data to the `VisitorInfo`. If you
need to influence how your condition is build you can either:

* overwrite the `ConditionFactory` service definition (not recommended)
* or handle the `TargetingEvents::BUILD_CONDITION` event and set an instance of your condition on the event.  


## Condition Data

If your condition needs any outside data, implement the [`DataProviderDependentInterface`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Targeting/DataProviderDependentInterface.php)
and define a list of data provider keys which need to be set on the `VisitorInfo` before matching. You can take a look at
existing core conditions for examples how this is used.


## Condition Variables

An important part are [variable conditions](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Targeting/Condition/VariableConditionInterface.php)
which support the `session_with_variables` rule matching scope. A condition implementing this interface is expected to return
an array of the variables which led to match the condition in the `getMatchedVariables()` method. This data will be used
to determine if the rule was already applied with the exact same data.

You should implement this interface whenever possible. To get started, you can use the [`AbstractVariableCondition`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Targeting/Condition/AbstractVariableCondition.php)
which contains helper methods to collect variable data.

As example: the country condition sets the ISO country code which led to match as its data.


## Frontend Code

To make your condition configurable, you need to create a JS class defining the admin inteface for your condition. To do so
create a class extending `pimcore.settings.targeting.condition.abstract` and register it to the system by calling
`pimcore.settings.targeting.conditions.register()`. 

Please have a look at the [customer data framework](https://github.com/pimcore/customer-data-framework/blob/master/src/Resources/public/js/pimcore/targeting/conditions.js)
condition definitions as an example of a bundle adding conditions to the system.``
