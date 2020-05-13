# Targeting and Personalization

The following section describes the technical concepts and aspects of the Pimcore targeting enginge. For usage 
description and feature listing see your [user docs](../../../User_Documentation/05_Targeting_and_Personalization/README.md)
first. 

## Setup

Basically the targeting engine works out of the box, however if you'd like to use geo-related conditions in your 
targeting rules it's necessary to configure the underlying data provider first. 

### Configuring the MaxMind GeoIP Data Provider

Follow the [official instructions](https://dev.maxmind.com/geoip/geoipupdate/) for obtaining and updating the GeoIP database.
Store the database file at the location of your choice, the default location used by _geoipupdate_ is `/usr/share/GeoIP/GeoLite2-City.mmdb`

Set the path to the database file in your `parameters.yml` to enable the geo support in Pimcore: 
```yaml
pimcore.geoip.db_file: /usr/share/GeoIP/GeoLite2-City.mmdb
``` 

 
## Basic concepts

| Concept          | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
|------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `VisitorInfo`    | Contains run-time information on the current visitor and is used by all conditions and action handlers to collect/apply data.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `DataProvider`   | Can be used by other components such as conditions to fetch data about the current visitor. E.g. the `Device` data provider loads device info from the user-agent string. Data is only loaded on demand if really needed by a component.                                                                                                                                                                                                                                                                                                                                                                                                                           |
| `Condition`      | A condition which is being matched. Conditions are configured in the Admin UI for global targeting rules.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `ActionHandler`  | A targeting rule can have one or multiple actions which are executed when its conditions match. These actions are executed by an action handler. E.g. the `Redirect` action handler creates a `RedirectResponse` which is used to redirect the visitor to another page.                                                                                                                                                                                                                                                                                                                                                                                            |
| `Storage`        | Where data about the visitor is persisted to. Each storage implementation supports storage for the `session` and the `visitor` scope. Session scoped data is only valid for the current session - either defined by a expiry time or by technical implications of the storage (e.g. the Cookie storage just sets a session cookie for the session storage). Visitor data is valid for the lifetime of the visitor and should be persisted, but depending on the used storage (e.g. Session Storage) this might not be possible. Different storages exist, e.g. cookie, JWT signed cookie, DB, session and redis which all have their advantages and disadvantages. |
| `Targeting Rule` | A condition/action combination executing one or more actions if a set of conditions match (similar to pricing rules in the ecommerce framework). Targeting rules are usually executed on every request but depending on their scope (hit, visitor, session, session with variables) this can be limited.                                                                                                                                                                                                                                                                                                                                                           |
| `Target Group`   | An entity which is used to target content for. A document/snippet can define personalized content for a specific target group (e.g. a special banner for a target group "Outdoor Interested"). By matching targeting rules and by configuring target groups to be tracked when visiting documents, a visitor will be assigned a list of target groups (they will be stored to storage and be added to the `VisitorInfo` object at runtime). When rendering the document, a `DocumentTargetingConfigurator` selects the content which matches best for the target groups which are available on the `VisitorInfo`.                                                  |


## VisitorInfo and Visitor ID

When a request is handled, the targeting engine first creates a new `VisitorInfo` instance. This instance is a data container
which will be used throughout the targeting process to store and pass data between different components of the system.

See [Visitor Info](./01_Visitor_Info.md) for details.


## Target Rule Matching

After building the `VisitorInfo` and resolving the visitor ID, the matching engine processes every defined targeting rule.
Based on the rule's scope a rule might be skipped (e.g. a `session` rule is applied only once per session). Every condition
gets the instance of the `VisitorInfo` and needs to decide if it matches its configured data.

To fetch additional data about the visitor, a condition can request data from one or more `DataProvider` implementations.
As an example the `Device` data provider is able to resolve and cache device info from the user agent string by using the
`DeviceDetector` library. The `Operating System` condition just defines the `Device` data provider as dependency and can
rely on the device data being added the the `VisitorInfo` before matching. The targeting engine takes care of requesting
data from a data provider only once per request, even if multiple conditions rely on its data.

If a rule matches, a list of actions is applied. Example actions are issuing a redirect or assigning a target group to the
current visitor. Actions (which are executed by action handlers) can request data from data providers in the same way as 
conditions.


## Targeting Storage

Some data needs to be persisted between requests. To do so, the targeting implementations makes use of a `TargetingStorage`
which implements a key-value store for targeting data. Data can be set for the `visitor` (valid for the whole lifetime of a
visitor) or for the `session` level. When storing data to an external storage, the previously resolved visitor ID is used
to store data for a specific visitor. 

Examples for data which is persisted to storage:

* assigned target groups - either set from targeting rules or automatically tracked when visiting a document which defines
  target groups to apply (as set on the document settings tab)
* already matched rules with `session` scope to avoid to repeatedly execute them for the same visitor
* first request to site in the current session (e.g. to calculate the time on site) 

For further details see [Targeting Storage](./09_Targeting_Storage.md).


## Content Targeting

After matching all rules, a `VisitorInfo` may have different assigned target groups. When rendering a document which has
targeted content for multiple target groups, the following logic is applied to determine which version to use for the 
current visitor:

* If the visitor has only one assigned target group and content exists for that target group, the personalized version for
  that target group will be used
* If the visitor has multiple assigned target groups, they will be sorted by their assignment count. The first target group
  in the sorted list which has personalized content for the document will be used.
  
This logic is repeated for every document and sub-request. This means you can show personalized content for a given target
group on a document, but have content for different target groups in snippets or renderlets rendered on the same page. 

You can see which target groups were applied to which document in the profiler. As you can see in the screenshot below,
the main document used the target group `basketball` while the footer snippet was rendered with the target group `female`.

![Targeting Profiler Target Groups](../../img/targeting_profiler_target_groups.png) 


### Manually applying Content Targeting

The logic defined above can be applied to any document. Documents which are loaded through Pimcore's standard routing and
rendering features will be automatically configured, but you also manually configure any document to use personalized content
by using the `DocumentTargetingConfigurator` service. As example, when handling a document inside a controller which is
registered as service:


```php
<?php

namespace AppBundle\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\Document;
use Pimcore\Targeting\Document\DocumentTargetingConfigurator;

class ContentController extends FrontendController
{
    public function pageAction(DocumentTargetingConfigurator $targetingConfigurator)
    {
        // load any document
        $document = Document::getByPath('/my/page');

        // configure personalized content based on resolved target groups
        // and available personalized content on the document
        $targetingConfigurator->configureTargetGroup($document);
        
        //...
    }
}
```

## Extending the Targeting Engine

The targeting engine is designed in a way to be easily extendable by third party code to extend and customize the engine's
behaviour.

See the following resources for further details:

* [Conditions](./03_Conditions.md)
* [Data Providers](./05_Data_Providers.md)
* [Action Handlers](./07_Action_Handlers.md)
* [Targeting Storage](./09_Targeting_Storage.md)
* [Frontend Javascript](./11_Frontend_Javascript.md)


## Debugging Targeting Data

As shown above, targeting date is added to the Symfony profiler. In addition you can enable a dedicated targeting toolbar
which also works outside the `dev` environment when you are logged into the admin interface.

<p><img class="img-narrow" src="../../img/targeting_toolbar.png" alt="Targeting Debug Toolbar" /></p>

The toolbar is only shown if a `pimcore_targeting_debug` cookie exists and is set and its value evaluates to true. You can
set the cookie with the following [bookmarklet](https://en.wikipedia.org/wiki/Bookmarklet) (just drag the link to your bookmarks
bar):

* <a href="javascript:(function()%7Bdocument.cookie %3D 'pimcore_targeting_debug%3D1%3B path%3D%2F'%7D)()">Enable Pimcore Targeting Toolbar</a> 

## Opt-out from targeting
You can give the user the possibility to opt-out from targeting at any time, by setting the following cookie: `pimcore_targeting_disabled=1`. 

