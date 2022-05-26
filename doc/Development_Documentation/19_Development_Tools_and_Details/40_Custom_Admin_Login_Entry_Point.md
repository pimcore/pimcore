# Custom Admin Login Entry Point

Pimcore `/admin` login entry point can be restricted/changed by using pimcore configuration.

Add custom admin identifier in your `config/config.yaml`:
```yaml
pimcore_admin: 
    custom_admin_path_identifier: min20CharCustomToken
``` 
> Please note:   
> custom_admin_path_identifier should be at least 20 characters long
> and must not start with `/admin` if Pimcore version <= 6.0.5 ! 

Add custom entry for `PimcoreCoreBundle:PublicServices:customAdminEntryPoint` in your routing.yml:  
```yaml
my_custom_admin_entry_point:
    path: /my-custom-login-page
    controller: Pimcore\Bundle\CoreBundle\Controller\PublicServicesController::customAdminEntryPointAction
``` 

> As soon as custom admin route (in this e.g. `/my-custom-login-page`) is called, admin cookie will be set in your browser which is validated for all /admin calls. 

> If you don't want to name your route `my_custom_admin_entry_point` you can set the route name in the pimcore_admin configuration like this:
> ```yaml
> pimcore_admin:
>     custom_admin_route_name: myCustomAdminRoute
> ```
> This is needed for the login as user link which can be generated in the user administration
