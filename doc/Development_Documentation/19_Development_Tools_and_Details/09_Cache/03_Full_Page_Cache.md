# Full Page Cache (Output Cache)

## Overview
![Full Page Cache](../../img/output-cache.png)

## Configure the Full Page Cache

> **Please Note**  
> The full page cache is disabled by default if you're logged in in the admin interface or in the case 
> the debug mode (settings -> system -> debug) is on.

The full page cache only works with GET request, it takes the whole response (only for the frontend)
including the headers from a request and stores it into the cache. The next request to the same 
page (hostname and request-uri are used to build the checksum/hash identifier) will be served 
directly by the cache.

You can check if a request is served by the cache or not checking the response headers of the 
request. If there are X-Pimcore-Cache-??? (marked orange below) headers in the response they the 
page is coming directly from the cache, otherwise not.

If you have specified a lifetime, the response also contains the Cache-Control and the Expires 
header (perfect for HTTP accelerators like Varnish, ... ). 

![Full Page Cache Headers](../../img/pimcore-cache-headers.png)


You can find the settings for the full page cache in the system-settings (`Settings` -> `System`).
![Full Page Cache Config](../../img/pimcore-cache-config.png)

| Option | Description |
| ------ | ----------- |
| Enable | Tick to generally enable the full page cache. |
| Lifetime | You can optionally define a lifetime (in seconds) for the  full page cache. If you don't do, the cache is evicted automatically when there is a modification in the Pimcore Backend UI. If there is a lifetime the item stays in the cache even when it is changed until the TTL is over. The lifetime is useful if you have embedded some items which are not directly in the cms, like rss feeds, or twitter messages over the API. It is also highly recommended to specify a lifetime on high traffic websites so that the frontend (caches) isn't affected by changes in the admin-UI. Otherwise on every change in the admin-UI the whole output-cache is flushed, what can have drastic effects to the server environment. |
| Exclude Patterns | You can define some exclude patterns where the cache doesn't affect. The patterns have to be valid regular expressions (including delimiters). Type one pattern in each line. |
| Disable Cookie | You can define an additional cookie-name which disables the cache. The cookie "pimcore_admin_sid" (used for the Pimcore admin UI) ALWAYS disables the output-cache to make editor's life easier ;-) 


## Disable the Full Page Cache in your Code
Sometimes it is more useful to deactivate the full page cache directly in the code, for example when 
it's not possible to define an exclude-regex, or for similar reasons.

In this case you can use obtain the pull page cache service from the container and disable it, eg. in an action: 
```php
<?php
$this->get(\Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener::class)->disable("Your disable reason");
```

### Disable the Full Page Cache for a Single Request (only in DEBUG MODE)
Just add the parameter `?pimcore_outputfilters_disabled=true` to the URL.

### Disable the Full Page Cache with a Cookie and a Bookmarklet
Per default the disable-cookie in the system settings is set to `pimcore_admin_sid`. 

That means that if your're logged into Pimcore (have a session-id cookie) you will always get the 
content live and not from the cache. 

#### Bookmarklet
If you have the cookie `pimcore_admin_sid` in your system configuration you can use the following 
bookmarklet to disable the full page cache without having an active admin session in an other tab.
To use the bookmarklet, just drag the following Link into your bookmark toolbar (any browser): 

[Disable Pimcore Cache](javascript:(function() {document.cookie='pimcore_admin_sid=disablethecachebaby'+(Math.floor(Math.random() * 147483648) + 2000)+';path=/;';})())
[Enable Pimcore Cache](javascript:void((function(){var a,b,c,e,f;f=0;a=document.cookie.split("; ");for(e=0;e<a.length&&a[e];e++){f++;for(b="."+location.host;b;b=b.replace(/^(?:%5C.|[^%5C.]+)/,"")){for(c=location.pathname;c;c=c.replace(/.$/,"")){document.cookie=(a[e]+"; domain="+b+"; path="+c+"; expires="+new Date((new Date()).getTime()-1e11).toGMTString());}}}alert("Expired "+f+" cookies");})()))

