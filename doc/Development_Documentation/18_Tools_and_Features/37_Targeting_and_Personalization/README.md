# Targeting and Personalization

<div class="alert alert-warning">
The new targeting engine is considered experimental and may be subject to change in later versions!
</div>

Pimcore 5.1 introduces completely new targeting and personalization features by implementing a server side targeting engine
which can be tightly integrated into other server side components such as the customer data framework or the ecommerce 
framework.


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

When a request is handled, the targeting engine first creates a new `VisitorInfo` instance. This instance will be used 
throughout the targeting process and will be used to store and pass data between different components of the system. As
some data needs to be persisted to uniquely identify returning visitors, the engine needs some kind of user identificator.

The identificator is expected to be generated or set by the browser and to be stored as a cookie. When creating a `VisitorInfo`
the targeting engine tries to load a visitor ID from the `_pc_vis` cookie. This ID is (by default) generated by the frontend
on 2 events:

* when loading a site with targeting enabled and no visitor ID was previously set, a random string is generated
* if Piwik integration is configured and the tracker is loaded, the unique Piwik visitor ID will be used. This has the 
  advantage that tracking for returining visitors or logged in users can be implemented based on Piwik's [User ID](https://piwik.org/docs/user-id/)
  feature

If you want to manually set the visitor ID in your frontend code you can do so with the following call which is exposed by
the targeting JS implementation:

```js
_ptg.api.setVisitorId('my-custom-visitor-id');
```

## Target Rule Matching

After building the `VisitorInfo` the matching engine processes every defined targeting rule. Based on the rule's scope a
rule might be skipped (e.g. a `session` rule is applied only once per session). Every condition gets the instance of the
`VisitorInfo` and needs to decide if it matches its configured data.

To fetch additional data about the visitor, a condition can request data from one or more `DataProvider` implementations.
As an example the `Device` data provider is able to resolve and cache device info from the user agent string by using the
`DeviceDetector` library. The `Operating System` condition just defines the `Device` data provider as dependency and can
rely on the device data being added the the `VisitorInfo` before matching. The targeting engine takes care of requesting
data from a data provider only once per request, even if multiple conditions rely on its data.

If a rule matches, a list of actions is applied. Example actions are issuing a redirect or assigning a target group to the
current visitor. Actions (which are executed by action handlers) can request data from data providers in the same way as 
conditions.

## Storage

Some data needs to be persisted between requests. To do so, the targeting implementations makes use of a `TargetingStorage`
which implements a key-value store for targeting data. Data can be set on a visitor (valid for the whole lifetime of a visitor)
or on a session level. 

Example data is for example the assigned target groups which are set from rules or which are automatically tracked if a 
document defines target groups to apply when visiting the document (as set on the document settings tab).


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

![Targetging Profiler Target Groups](../../img/targeting_profiler_target_groups.png) 
