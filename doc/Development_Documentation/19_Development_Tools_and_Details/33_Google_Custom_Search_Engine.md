# Google Custom Search Engine (Site Search ~ CSE) Integration

## Introduction
Pimcore provides a simple interface to the Google Custom Search Engine which 
makes it easy to integrate a search engine into your website. 

[Further information about Google CSE](http://www.google.com/cse/)

## Setup in CSE Control Panel and Google Cloud Console
* Create and configure a new search engine at [http://www.google.com/cse/](http://www.google.com/cse/) - 
for more information please visit: [http://support.google.com/customsearch/](http://support.google.com/customsearch/) .
* Test your search engine first using the preview on the right hand side. 
* If your results are as expected, go back to "Setup" (-> left navigation) and note the search engine ID 
- You'll need it later in the code (parameter 'cx'  in configuration): 

![Google CSE Setup](../img/cse1.png)

* Once you are finished go to [https://console.developers.google.com/](https://console.developers.google.com/) 
create a new project, then search for `Custom Search API` and click on `Enable`.
`Custom Search API` should now be listed under `Enabled APIs` in your project overview.
* To get the necessary access keys, click on: `Credentials` -> `Create credentials` -> `API key` -> `Server key`
Complete the setup as described and note the server API key.

![Google CSE Server Key](../img/cse2.png)


## Pimcore Setup
So now we got the search engine ID and a server API key which we need to finish the 
configuration in Pimcore. 

### Server API Key
Configure the API key in `simple_api_key` config in config.yaml as follows:
```yaml
pimcore:
    services:
        google:
            client_id: 73893596983.apps.googleusercontent.com
            email: 73893596983@developer.gserviceaccount.com
            simple_api_key: AIzaSyCo9Wj49hYJWW2WgOju4iMYNTvdcBxmyQ8
            browser_api_key: AIzaSyBJX16kWAmUVEz1c1amzp2iKqAfumbcoQQ
```

### Search engine ID
The search engine ID is used in your controller/action to configure the search service: 
`\Pimcore\Google\Cse::search()`.

The place of interest (parameter cx) is marked with a comment in the code example below.


### Code Example
> Please note: These are examples from old demo application. Please adapt to the latest code style for implementation.

#### Controller Action
See: https://github.com/pimcore/demo-basic/tree/master/src/AppBundle/Controller/AdvancedController.php#L85-L85

#### View
See: https://github.com/pimcore/demo-basic/tree/master/app/Resources/views/Advanced/search.html.php

#### Partial View Script (includes/paging.php)
See: https://github.com/pimcore/demo-basic/tree/master/app/Resources/views/Includes/paging.html.php