# Routing and URLs 

## Introduction
Routing definies which requests are delegated to which controller based on the URL they are sent to. Therefore routing
 is an essential part in each MVC web application. 
 
Beside the pure technical aspect (which controller is called) there are several other aspects like 
 URL structure and hierarchy, SEO and multi domain sites that need to be considered in combination with routing.
   
Knowing how routing works in Pimcore is essential to understand how Pimcore application can be implemented and how 
 they work. Technically the whole routing process is based on the ZF routing. For details please have a look at the 
 [ZF documentation](https://framework.zend.com/manual/1.12/en/zend.controller.router.html). 
 

## Routing in Pimcore
In Pimcore, there are several ways how controllers can be reached. These routes are processed in a specific priority 
order as described below. 

#### 1. System Routes:
Pimcore has a few system routes that are required for Pimcore base functionality. The list below lists all of them, 
but only the following few are relevant on a daily basis: 
##### Route for Pimcore plugins - `/plugin/:module/:controller/:action/*`
This route is relevant when implementing plugins with their own controllers. By default these controllers are reachable 
with this route whereas `:module` is the plugin name. For more details on plugin development see the 
[plugin documentation section](../../10_Extending_Pimcore/13_Plugin_Developers_Guide/01_Plugin_Anatomy.md). 
 
##### Route for Pimcore web services - `/webservice/:controller/:action/*`
This route is relevant when using Pimcore web services. For details see the 
[web services documentation section](../../14_Web_Services/_index.md). 

  
##### List of all system routes:    
* `/install/:controller/:action/*` - Route for Pimcore installer
* `/plugin/:module/:controller/:action/*` - Route for Pimcore plugins
* `/admin/:controller/:action/*` - Route for Pimcore admin backend interface
* `/admin/update/:controller/:action/*` - Route for Pimcore live updater
* `/admin/extensionmanager/:controller/:action/*` - Route for Pimcore extension manager
* `/admin/reports/:controller/:action/*` - Route for Pimcore backend reports
* `/admin/search/:controller/:action/*` - Route for Pimcore backend search
* `/webservice/:controller/:action/*` - Route for Pimcore webservices
   
   
#### 2. Redirects with priority 99:  
Redirects with priority 99 come second in the processing priority. See [Redirects](./04_Redirects.md) for details. 
   
#### 3. Pimcore Documents and Pretty URLs:
The path of Pimcore Documents also defines its URL. In addition to the path, so called pretty URLs can be defined for
 documents. The Document path and pretty URLs come third in the processing priority. 
 See [Documents and Pretty URLs](./00_Documents_and_Pretty_URLs.md) for details. 


#### 4. Static Routes / Custom Routes: 
When your application has functionality where there is no Pimcore Document necessary (e.g. product lists and detail pages 
 or cart pages, or checkout process, ...), Custom Routes allow the definition of URL patterns that are delegated to specific
 controllers. Custom Routes come fourth in the processing priority. See [Custom Routes](./02_Custom_Routes.md) for details.


#### 5. Redirects: 
All Redirects with priority lower than 99 come fifth in the processing priority. 
See [Redirects](./04_Redirects.md) for details. 


#### Multi domain sites
The routing process also supports multi domain sites. 
See [Working with Sites](./08_Working_with_Sites.md) for more details on that. 