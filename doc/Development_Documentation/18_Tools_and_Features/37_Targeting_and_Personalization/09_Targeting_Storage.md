# Targeting Storage

[TOC]

To persist data between requests, the targeting engine makes use of a targeting storage service which is responsible for
persisting the given data to a storage. In general, the storage always receives an instance of the `VisitorInfo` it should
store/retrieve data for and a `scope` which defines how data is handled.

There are 2 fixed scopes which are used depending on what is needed:

| Scope     | Description                                                                                                                                                                                                                                                                                                                                                                         |
|-----------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `session` | Valid for the current session, expires afterwards. Depending on the implementation, a session is either defined by a timeout of inactivity or by natively being handled by the storage system. E.g. the DB and Redis storages handle expiration by expiring data after a certain amount of time while Session and Cookie storages just make use of the session and cookie lifetime. |
| `visitor` | Valid for the whole lifetime of the visitor. When a visitor returns with its unique ID, its data will still be usable while session data would have expired.                                                                                                                                                                                                                        |  


## Configuring the Targeting Storage

The targeting storage needs to be defined as service and its service ID needs to be configured with the following config
entry:

```yaml
pimcore:
    targeting:           
        storage_id: Pimcore\Targeting\Storage\CookieStorage
```


## Implement a Custom Targeting Storage

Basically, a targeting storage is a class implementing the [`TargetingStorageInterface`](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Targeting/Storage/TargetingStorageInterface.php)
which is registered as service. Details how to handle data varies heavily on the underlying storage, but you can take the
[core storages](https://github.com/pimcore/pimcore/tree/master/pimcore/lib/Pimcore/Targeting/Storage) as starting point.


## Core Storage Implementations

Pimcore implements different storage engines which each has its pros and cons. In general it is recommended to start with
the default implementation (JWT signed cookie) and choose the engine to use based on requirements.

In the future more storage implementations are planned which combine features of multiple engines together, e.g. a storage
handling multiple backends which are selected depending on if the visitor already has a visitor ID or not.
 

### Cookie

Stores data in a cookie in the user's browser. Can either be used with a plaintext cookie or with a JWT signed one to make
sure the cookie data isn't being tampered with. The cookie storage delegates the actual cookie write/read operation to a
`CookieSaveHandler` which can either save the cookie data as plain text JSON or as JSON Web Token signed cookie.

To change the save handler, override the [service definition](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/CoreBundle/Resources/config/targeting.yml#L24)
and set your own handler.

<div class="alert alert-danger">
Note that using plain text cookie data is inherently insecure and can open vulnerabilities by injecting malicious data into
the client cookie. Use only for testing!
</div>

Pros

* Easy to use as no additional config is needed and it can store data without needing a visitor ID
* Fast and easy to debug

Cons

* Cookie size is limited and bloats requests - can only be used when the amount of generated targeting data is limited and
  implementations take care of keeping data to a minimum.
* Inherently insecure when used with an unsigned cookie.


### Db

Stores data in the database.

Pros

* Easy to use as no additional config is needed

Cons

* Can only store data when a visitor ID is present as the ID is part of the primary key
* DB can fill up quickly - not to be used on large sites


### Redis

Stores data in a redis DB. To use this storage, define a service using the storage implementation as class and add connection
details to the service definition. An example is shipped with the [core service definitions](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/CoreBundle/Resources/config/targeting.yml#L35).

Pros

* Can efficiently handle large amounts of data
* Natively supports data expiration

Cons

* Can only store data when a visitor ID is present as the ID is part of the key
* Needs a dedicated Redis DB independent of the cache one (needs to be configured on the service definition)


### Session

Stores data in the session.

To use the session storage, an additional config entry is needed as the session listeners are disabled by default for 
performance reasons:

```yaml
pimcore:
    targeting:
        # enable session support
        session:
            enabled: true
            
        # use the session storage
        storage_id: Pimcore\Targeting\Storage\SessionStorage
```

Pros

* Easy to use as no additional config is needed
* Can store data without a visitor ID

Cons

* Can't persistently store data for a visitor
* Session size is limited
* Slow depending on session storage
* Might not work properly in conjunction with full page caches. Pimcore's full page cache is disabled when the session
  contains targeting data, but if using something else it might be difficult to handle. Use with care when using full
  page caches!
