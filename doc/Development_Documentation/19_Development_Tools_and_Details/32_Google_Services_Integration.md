# Google Services Integration
Menus and buttons may vary depending on the current GUI version

## Console - APIs
* <https://console.developers.google.com/project>
* *Menu:* `[+ Create project]`
  * Project name: **jaks-sk**
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
      * Add an item: **80.241.220.152**
    * **b_api_key** > `[Restrict key]`
      * Name: **b_api_key**
      * HTTP referrers (web sites)
      * Add an item: **\*.l-jaks**, **\*.jaks.sk**
* *Menu:* `APIs & services` / `Library`
    - **Google Analytics API** > `[Enable]`
    - **Custom Search API** > `[Enable]`
    - **Maps Static API** > `[Enable]`
    - **Maps JavaScript API** > `[Enable]`
    - **Geocoding API** > `[Enable]`

## Analytics
* <https://analytics.google.com/>
* *Menu:* `Administrator`
  * `[Create new account]`
    * Account name: **jaks**
    * Web
    * Website name: **jaks.sk**
    * Website URL: **https://www.jaks.sk/**
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
  * Sites to search: **www.jaks.sk/sk/***
  * Language: **slovak**
  * Name: **jaks.sk/sk**
* *Menu:* `Setup`
  * Search engine ID: **cse_id**
  * Image search: OK
* *Menu:* `Statistics & logs`
  * *Tab:* `Google analytics`
    * Select profile: **jaks: All website data**

## Search Console
* <https://search.google.com/search-console>
* *Menu:* `Add property`
  * Web : **https://www.jaks.sk**
  * `Get`: **html_ver**

## reCAPTCHA
* <https://www.google.com/recaptcha/admin#list>
* *Menu:* `[+ Create]`
  * Label: **jaks.sk**
  * reCAPTCHA v2
  * Domains: **jaks.sk**
  * `Copy site key`: **site_key**
  * `Copy secret key`: **secret**

## Joining with Pimcore
**APIs**
* copy **JSON** to `/app/config/pimcore/google-api-private-key.json`
* edit `/var/config/system.yml`
  * services/google/client_id = **api_id**
  * services/google/email = **api_email**
  * services/google/simple_api_key = **s_api_key**
  * services/google/browser_api_key = **b_api_key**

**CSE**
* edit `www/app/config/parameters.yml`
  * search.google.cse_cx = **cse_id**

**reCAPTCHA**
* edit `www/app/config/local/bundles_prod.yml`
  * beelab_recaptcha2/site_key = **site_key**
  * beelab_recaptcha2/secret = **secret**

**Analytics**
* *Pimcore admin menu:* `Marketing` / `Marketing settings`
  * *Tab:* `Google Analytics`
    * Track-ID = **track_id**
    * Advanced Integration > Profile = **All website data**
  * *Tab:* `Google Search Console`
    * Verification Filename = **html_ver**
