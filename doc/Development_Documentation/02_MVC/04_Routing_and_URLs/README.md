# Routing and URLs 

## Introduction
Routing defines which requests are delegated to which controller based on the URL they are sent to. Therefore, routing
 is an essential part in each MVC web application. 
 
Beside the pure technical aspect, (which controller is called) there are several other manners like 
 URL structure and hierarchy, SEO and multi domain sites that need to be considered in combination with routing.
   
Knowing how routing works in Pimcore/Symfony is essential to understand how an application can be implemented and how 
 routes actually work. Technically the entire routing process is based on the [Symfony routing](http://symfony.com/doc/3.4/routing.html). 
In addition to the default routing provided by Symfony (which is of course used as well), Pimcore has some special
routing capabilities for documents, custom routes, multi-site support and redirects. 
 
## Routing in Pimcore
In Pimcore, there are several ways how controllers can be reached. These routes are processed in a specific priority 
order as described below. 

#### 1. System / Symfony Routes:
Pimcore defines a few system routes that are required for Pimcore base functionality like the admin user-interface, the REST
Services and may also other routes provided by custom bundles. These routes are just standard Symfony routes and have the highest 
priority. 

To get a list of all configured Symfony routes, please use Symfony's router debugger on the command line: 
`./bin/console debug:router`
   
#### 2. Redirects with Priority 99:  
Redirects with priority 99 come second in the processing priority. See [Redirects](./04_Redirects.md) for details. 
   
#### 3. Pimcore Documents and Pretty URLs:
The path of Pimcore Documents also defines its public URL. In addition to the path, so called pretty URLs can be defined for
 individual documents. The Document path and pretty URLs come third in the processing priority. 
 See [Documents and Pretty URLs](./00_Documents_and_Pretty_URLs.md) for details. 


#### 4. URL Slugs of Data Objects 
With the special data type [URL Slug](../../05_Objects/01_Object_Classes/01_Data_Types/65_Others.md#page_URL-Slug-experimental) URLs for data objects can be defined. These need to be unique and are evaluated for the current site. See [URL Slug](../../05_Objects/01_Object_Classes/01_Data_Types/65_Others.md#page_URL-Slug-experimental) for details.


#### 5. Static Routes / Custom Routes: 
When your application has functionality where there is no Pimcore Document necessary (e.g. product lists, detail pages, 
 cart pages or checkout process, ...), Custom Routes allow the definition of URL patterns that are delegated to specific
 controllers. Custom Routes come fourth in the processing priority. See [Custom Routes](./02_Custom_Routes.md) for details.


#### 6. Redirects: 
All Redirects with priority lower than 99 come fifth in the processing priority. 
See [Redirects](./04_Redirects.md) for details. 


#### Multi domain sites
The routing process also supports multi domain sites. 
See [Working with Sites](./08_Working_with_Sites.md) for more details on that. 
