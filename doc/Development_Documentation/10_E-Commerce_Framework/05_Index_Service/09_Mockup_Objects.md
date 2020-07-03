# Mockup Objects in Product List Results

Normally the result of Product Lists contain Pimcore product objects. When retrieving lists with many entries this can
result in performance issues during rendering product listings - especially on high traffic applications when Pimcore 
product objects are very complex, heavily use content inheritance and have lots of relations. 
 
To address this issue, Product Lists can return so called Mockup objects instead of the original Pimcore product objects. 
The idea is that these Mockup objects are a lightweight a selection of the product data, therefore are much smaller and 
load faster.
 
By default the optimized product index implementations of the E-Commerce Framework support Mockup objects in a 
transparent way and you can take advantage from the faster loading times. 

This means that the corresponding product lists return Mockup objects which contain all data that is 
stored into the product index. If a getter is called on the Mockup object (e.g. `$product->getName()`), it first tries 
to get the requested data from the index data. If the data is not available, the call is delegated to the original 
Pimcore object.
Then the call is delegated, following log message is written to Pimcore log files - if system is in debug mode as `WARN`, 
 otherwise as `INFO`. 
```
"Method $method not in Mockup implemented, delegating to object with id {$this->id}."
```

So, the only thing you need to take care about: make sure that all the data that is printed in product listing is 
available in the product index. 


## Using Custom Mockup Object Implementation
Of course, it might be necessary to use custom Mockup implementations. To do you, overwrite the 
`createMockupObject($objectId, $data, $relations)` method in your `Config` implementation. For an example see 
[`MyOptimizedMysql`](https://github.com/pimcore/demo-ecommerce/blob/master/src/AppBundle/Ecommerce/IndexService/Tenant/Config/MyOptimizedMysql.php#L37) 
in the Demo. 