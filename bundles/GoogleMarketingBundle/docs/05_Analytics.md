# Google Analytics
In order to use Google Analytics you have to install the PimcoreGoogleMarketingBundle.
Make sure to enable it in the bundles.php

## Disabling the Google Analytics Code
 
Injecting the code can be disabled by calling `disable()` in the `GoogleAnalyticsCodeListener`. For example, in a controller
action in an autowired controller:

```php
<?php

namespace App\Controller;

use Pimcore\Bundle\GoogleMarketingBundle\EventListener\Frontend\GoogleAnalyticsCodeListener;
use Symfony\Component\HttpFoundation\Response;

class TestController
{
    public function testAction(GoogleAnalyticsCodeListener $analyticsCodeListener): Response
    {
        $analyticsCodeListener->disable();
        
        // ...
    }
}
```

Of course you can also inject the listener into a custom service.


## Customizing the tracking code

If you want to influence the generated tracking code, you have multiple possibilities to do so. The tracker code is divided
into multiple code blocks which can be expanded and altered individually. As reference, please see:

* the definition of available blocks in the [Tracker implementation](https://github.com/pimcore/pimcore/blob/11.x/lib/Analytics/Google/Tracker.php#L66)
* the [templates](https://github.com/pimcore/pimcore/blob/11.x/bundles/CoreBundle/Resources/views/Analytics/Tracking/Google/Analytics)
  which are rendered when generating the tracking code and which define where the content of each blocks is rendered
  

### Adding code to a block

The central part of the Google tracking is the `Pimcore\Bundle\GoogleMarketingBundle\Tracker\Tracker` class which is defined as service and
which provides a `addCodePart()` method which allows you to add custom code snippets to a specific block:

```php
<?php

namespace App\Controller;

use Pimcore\Bundle\GoogleMarketingBundle\Tracker\Tracker;
use Pimcore\Bundle\GoogleMarketingBundle\SiteId\SiteId;
use Symfony\Component\HttpFoundation\Response;

class ContentController
{
    public function defaultAction(Tracker $tracker): Response
    {
        // append a part to the default block
        $tracker->addCodePart('console.log("foo");');
        
        // append a part to a specific block
        $tracker->addCodePart('console.log("foo");', Tracker::BLOCK_BEFORE_TRACK);
        
        // prepend a part to a specific block
        $tracker->addCodePart('console.log("foo");', Tracker::BLOCK_AFTER_TRACK, true);
        
        // you can also add the code only for a specific site
        // if you want to do so, you need to pass a SiteId object which identifies a tracking site
        $tracker->addCodePart('console.log("foo");', Tracker::BLOCK_AFTER_TRACK, true, SiteId::forMainDomain());
        
        // ...
    }
}
``` 


### Influencing generated code through the `CODE_TRACKING_DATA` event

Before the tracking code is generated, the `GoogleAnalyticsEvents::CODE_TRACKING_DATA` event is dispatched which gives
you full control over the generated code:

```php
<?php

namespace App\EventListener;

use Pimcore\Bundle\GoogleMarketingBundle\Model\Event\TrackingDataEvent;
use Pimcore\Bundle\GoogleMarketingBundle\Tracker\Tracker;
use Pimcore\Bundle\GoogleMarketingBundle\Event\GoogleAnalyticsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GoogleTrackingCodeListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            GoogleAnalyticsEvents::CODE_TRACKING_DATA => 'onTrackingData'
        ];
    }

    public function onTrackingData(TrackingDataEvent $event): void
    {
        // append data to a block
        $event->getBlock(Tracker::BLOCK_AFTER_TRACK)->append([
            'console.log("foo");'
        ]);

        // completely empty the after track block
        $event->getBlock(Tracker::BLOCK_AFTER_TRACK)->setParts([]);

        // the data array is the data which will be passed to the template
        $data = $event->getData();
        $data['foo'] = 'bar';
        
        $event->setData($data);

        // you can also completely replace the rendered template
        $event->setTemplate('@App/Analytics/Tracking/Google/trackingCode.html.twig');
    }
}
```

### Overriding the rendered template

As you can see in the sample event listener above, you can change the template which will be used for the tracking code.
If you set a custom template, you can extend the core one and just override the blocks you want:

```twig
{# templates/Analytics/Tracking/Google/Analytics/universalTrackingCode.html.twig #}
{% extends "@PimcoreGoogleMarketing/Analytics/Tracking/Google/Analytics/universalTrackingCode.html.twig" %}

{% block track %}
    {{ parent() }}

    console.log('hello world');
{% endblock %}

{% block afterScriptTag %}
    {{ parent() }}

    <script type="text/javascript">
        console.log('foo bar!');
    </script>
{% endblock %}
```
