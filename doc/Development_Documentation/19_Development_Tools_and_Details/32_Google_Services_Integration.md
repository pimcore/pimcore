# Google Services Integration
Menus and buttons may vary depending on the current GUI version

## Console - APIs
* <https://console.developers.google.com/project>
* *Menu:* `[+ Create project]`
  * Project name: **project-sk**
* *Menu:* `Select project`
* *Menu:* `IAM & admin` / `Service accounts`
  * `[+ Create service account]`
    * Service account name: **api_id** (email)
    * Role: App Engine / **App Engine Viewer**
    * `[Done]`
  * `[+ Create service account]`
    * Service account name: **api_email** (email)
    * Role: Project / **Viewer**
    * Create key (optional): `[Create key]`
      * Key type: **JSON**
      * `Download json`
    * `[Done]`
* *Menu:* `APIs & Services` / `Credentials`
  * `[+ Create credential]` > `API key`
    * **s_api_key** > `[Restrict key]`
      * Name: **s_api_key**
      * IP addresses (web servers, ...)
      * Add an item: **80.241.1.1**
    * **b_api_key** > `[Restrict key]`
      * Name: **b_api_key**
      * HTTP referrers (web sites)
      * Add an item: **\*.l-project**, **\*.project.sk**
* *Menu:* `APIs & services` / `Library`
    - **Google Analytics API** > `[Enable]`
    - **Custom Search API** > `[Enable]`

## Analytics
* <https://analytics.google.com/>
* *Menu:* `Administrator`
  * `[Create new account]`
    * Account name: **project**
    * Web
    * Website name: **project.sk**
    * Website URL: **https://www.project.sk/**
    * Industry category:
    * Reporting Time Zone:
    * `Get Tracking ID`: **track_id**
* *Menu:* `All website data`
  * `View settings`
    * Search tracking: ON
      * query parameter = **q**
  * `User Management`
    * `[+]` > `Add user`
      * Add email address = **api_email**
      * Read and analysis
    * `[+]` > `Add user`
      * Add email address: owner@gmail.com
      * Read and analysis

## CSE - Custom Search Engine
* <https://cse.google.com/cse/all>, <http://support.google.com/customsearch/>
* `[Add]`
  * Sites to search: **www.project.example/***
  * Language: **slovak**
  * Name: **project.example/**
* *Menu:* `Setup`
  * Search engine ID: **cse_id**
  * Image search: OK
* *Menu:* `Statistics & logs`
  * *Tab:* `Google analytics`
    * Select profile: **project: All website data**

## Configuring Google Services in Pimcore
**APIs**
* copy **JSON** to `/app/config/pimcore/google-api-private-key.json`
* edit `/var/config/system.yml` or use System Settings in admin interface
  * services/google/client_id = **api_id**
  * services/google/email = **api_email**
  * services/google/simple_api_key = **s_api_key**
  * services/google/browser_api_key = **b_api_key**

**CSE**
* edit `www/app/config/parameters.yml`
  * search.google.cse_cx = **cse_id**

**Analytics**
* *Pimcore admin menu:* `Marketing` / `Marketing settings`
  * *Tab:* `Google Analytics`
    * Track-ID = **track_id**
    * Advanced Integration > Profile = **All website data**
  * *Tab:* `Google Search Console`
    * Verification Filename = **html_ver**
