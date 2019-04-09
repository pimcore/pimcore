# Custom Admin Login Entry Point

Pimcore `/admin` login entry point can be restricted/changed by using pimcore configuration.

Add custom admin identifier in your `app/config/config.yml`:
```yml

pimcore_admin: 
  custom_admin_path_identifier: min20CharCustomToken
  
``` 
> Please note: custom_admin_path_identifier should be atleast 20 characters long

Add custom entry for `PimcoreCoreBundle:PublicServices:customAdminEntryPoint` in your routing.yml:  
```yml
my_custom_admin_entry_point:
    path: /my-custom-login-page
        defaults: { _controller: PimcoreCoreBundle:PublicServices:customAdminEntryPoint }
``` 

> As soon as custom admin route (in this e.g. `/my-custom-login-page`) is called, admin cookie will be set in your browser which is validated for all /admin calls. 
