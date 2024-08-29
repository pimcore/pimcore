# Restricting Public Asset Access

Pimcore has following default behavior in terms of asset delivery: 

> All Data that is stored as Pimcore Assets can be accessed via its URL (e.g. https://mydomain.com/my-assetfolder/my-asset.jpg) 
> and therefore is public available without login or other access restriction!
> 
> As a consequence confidential data **must not** be stored as Pimcore Assets without additional protection measures.

The reason for this wanted behavior is performance. Delivering an asset directly via the web server needs significantly 
less resources than starting a php process for every asset request (especially when it comes to delivering thumbnails).  


If further restriction is needed, Pimcore provides two options for doing so: 


### Option 1 - Restricting access to certain assets completely  

All confidential assets need to be stored within one (or a few) folder(s), e.g. within `/protected` (to set up Pimcore 
backend permissions this is necessary anyway). 

![Protected Folder](../img/asset-access-restriction.png)

**Apache**

In the `.htaccess` of the project, the access to this folder has to be restricted with an additional rewrite rule. It is
important, that this rule is placed **in front of** the rewrite rule for asset delivery. 

```apache
...
RewriteRule ^protected/.* - [F,L]
RewriteRule ^var/.*/protected(.*) - [F,L]
RewriteRule ^cache-buster\-[\d]+/protected(.*) - [F,L]

# ASSETS: check if request method is GET (because of WebDAV) and if the requested file (asset) exists on the filesystem, if both match, deliver the asset directly
...
```

**Nginx**

Add the following parts to your Nginx configuration directly after the index directive. 

```nginx
location ~ ^/protected/.* {
  return 403;
}

location ~ ^/var/.*/protected(.*) {
  return 403;
}

location ~ ^/cache-buster\-[\d]+/protected(.*) {
  return 403;
}
```

A full configuration example can be found [on this page](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md).


Because of this rule, all assets located within `/protected` (also all their thumbnails) are not delivered via the web 
server anymore. As a consequence also using the direct link for downloading or using the Pimcore generated img tags for 
thumbnails cannot be used anymore. All delivery of these assets has to be done manually via a custom controller action. 


### Option 2 - Checking permissions before delivery 

This option does not restrict the delivery in general, but routes the asset request to a controller action that can check 
access permissions with custom business logic and then deliver the asset or not. 

Again all confidential assets need to be stored within one (or a few) folders, e.g. within `/protected`. 

![Protected Folder](../img/asset-access-restriction.png)

**Apache**

In the `.htaccess` of the project, requests to assets of this folder need to be routed to `index.php`. Again, it is
important, that this rule is placed **in front of** the rewrite rule for asset delivery.

```apache
...
RewriteRule ^protected/(.*) %{ENV:BASE}/index.php [L]
RewriteRule ^var/.*/protected(.*) - [F,L]
RewriteRule ^cache-buster\-[\d]+/protected(.*) - [F,L]

# ASSETS: check if request method is GET (because of WebDAV) and if the requested file (asset) exists on the filesystem, if both match, deliver the asset directly
...
```

**Nginx**

Add the following parts to your Nginx configuration directly after the index directive. 

```nginx
rewrite ^(/protected/.*) /index.php$is_args$args last;

location ~ ^/var/.*/protected(.*) {
  return 403;
}

location ~ ^/cache-buster\-[\d]+/protected(.*) {
  return 403;
}
```

A full configuration example can be found [on this page](../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md).


In the application, there has to be a route in (config/routes.yaml) and a controller action that handles the request, e.g. like the following:

```yaml
# config/routes.yaml

# important this has to be the first route in the file!
asset_protect:
    path: /protected/{path}
    defaults: { _controller: App\Controller\MyAssetController::protectedAssetAction }
    requirements:
        path: '.*'
```

```php
<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Service;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MyAssetController extends FrontendController
{
    public function protectedAssetAction(Request $request): Response
    {
        // IMPORTANT!
        // Add your code here to check permission!


        // the following code is responsible to deliver asset & thumbnail contents
        // modify it the way you need it for your use-case
        $pathInfo = $request->getPathInfo();
        $asset = Asset::getByPath($pathInfo);
        if ($asset){
            $stream = $asset->getStream();
            return new StreamedResponse(function () use ($stream) {
                fpassthru($stream);
            }, 200, [
                'Content-Type' => $asset->getMimeType(),
            ]);
        } else {
            return Asset\Service::getStreamedResponseByUri($pathInfo);
        }

        throw new AccessDeniedHttpException('Access denied.');
    }
}
```

Of course this option has significant impact on server load and delivery performance of assets (and thumbnails). Therefore
it is **not** suggested to deliver all assets that way but only the confidential ones!  
