# REST Webservice API
Pimcore provides a web service to retrieve and save objects, documents and assets through a RESTful API. When the web 
service feature is enabled in the system settings (by default it is disabled), any admin user can access  and utilize 
the REST API.

When the web service API is enabled, for each user his API key is displayed in `Settings` > `Users`. Please be aware 
that the API Key changes when the user changes his/her password.

For testing in the browser it's not necessary to add the apikey if you have a valid user session from the admin interface 
(session authentication). 

 
[TOC]


## Permissions

Unrestricted access is only granted to admin users. For all other users the following restrictions are enforced:

* Classes permission for the following calls:
    * Classes list
    * Object bricks list
    * Field Collections list
    * Class Definition
    * Object Brick Definition
    * Field collection Definition
    * Key Value Definition
    * Object metadata
* Asset permission:
    * Asset List
    * Asset Count
* Document permission
    * Document List
    * Document Count
* Object permission
    * Object List
    * Object Count
* System Settings Permission
    * Get Server Info
* Workspace View permission
    * Get Asset|Document|Object
* Workspace Delete permission
    * Delete Asset|Document|Object
* Workspace Create permission
    * Create Asset|Document|Object
* Workspace Save permission
    * Publish Asset|Document|Object  
    
## Available Calls
The following methods are available for web service calls:

### Get Object By ID
* **Method**: GET
* **URL**: http://YOUR-DOMAIN/webservice/rest/object/id/1281?apikey=[API-KEY\
* **Returns**: JSON--encoded object data.

### Delete Object By ID
* **Method**: DELETE
* **URL**: http://YOUR-DOMAIN/webservice/rest/object/id/1281?apikey=API-KEY
* **Returns**: JSON-encoded success value