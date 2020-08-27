# Query Filters

Querying can be done using a limited and simplifed subset of the syntax described 
here.

[here](https://restdb.io/docs/querying-with-the-api)

Also note that there might restrictions regarding nesting levels.

## Supported Logic Operators

* $not
* $or
* $and
* $like

## Conditional operators

* $gt
* $gte
* $lt
* $lte

## Examples

```
q={"o_modificationDate" : {"$gt" : "1000"}}

... SQL equivalent ...
where ((`o_modificationDate` > '1000') )
```


```
q=[{"o_modificationDate" : {"$gt" : "1000"}}, {"o_modificationDate" : {"$lt" : "9999"}}]
...
where ( ((`o_modificationDate` > '1000') )  AND  ((`o_modificationDate` < '9999') )  )
```

```
q={"o_modificationDate" : {"$gt" : "1000"}, "$or": [{"o_id": "3"}, {"o_key": {"$like" :"%lorem-ipsum%"}}]}
...
where ((`o_modificationDate` > '1000') AND  ((`o_id` = '3') OR  ((`o_key` LIKE '%lorem-ipsum%') )  )  )
```

```
q={"$and" : [{"o_published": "0"}, {"o_modificationDate" : {"$gt" : "1000"}, "$or": [{"o_id": "3"}, {"o_key": {"$like" :"%lorem-ipsum%"}}]}]}
...        
where ( ((`o_published` = '0') )  AND  ((`o_modificationDate` > '1000') AND  ((`o_id` = '3') OR (`o_key` LIKE '%lorem-ipsum%') )  )  )
```

```
q={"o_type":%20{"$not":%20"folder"}}
...
(( NOT `o_type` ='object') )
```

## Legacy Mode

SQL condition filters are not supported anymore. However, you can still enable them by implementing a event listener as scribbled below.
If you don't but still pass a condition parameter an exception will be thrown.

```
<?php

namespace AppBundle\EventListener;

use Pimcore\Event\Webservice\FilterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LegacyListener implements EventSubscriberInterface
{
     public static function getSubscribedEvents()
    {
        return [
            \Pimcore\Event\WebserviceEvents::BEFORE_LIST_LOAD => 'beforeLoad'
        ];
    }


    public  function beforeLoad(FilterEvent $e)
    {
        $request = $e->getRequest();
        $condition = $request->get("condition");

        if ($condition) {
            $e->setCondition($condition);
        }
    }
}
```
