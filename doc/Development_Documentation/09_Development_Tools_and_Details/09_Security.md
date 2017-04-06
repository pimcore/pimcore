# Security

You can make full use of the [Symfony Security Component](http://symfony.com/doc/current/security.html) to handle complex
authentication/authorization scenarios. However, as the Pimcore administration interface and the REST API already
use the security component for its puropses, a couple of prerequisites and differences to a standard Symfony application need to be considered. As starting point, please have a look at the [security.yml](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/CoreBundle/Resources/config/pimcore/security.yml)
defined in the `CoreBundle` to get an idea what Pimcore already defines.

## Merged security configurations

A standard Symfony application requires the security configuration to be defined in one single file. In contrast to that, Pimcore allows to merge security configurations together from multiple locations. This allows bundles (e.g. a bundle defining its own routes), to define custom security configurations for its routes which are then merged into the 
global security configuration.
This setup was mainly choosen to make sure the Pimcore admin security configuration is always loaded and can be extended by the application specific configuration which is defined by bundles and your application logic. Security configurations will always be loaded in the following order (this
also applies to firewalls and `access_control` to make sure the admin interface is always matched first):

* admin
* any security configuration which was auto-loaded from bundle configs (see [auto loading config files](../10_Extending_Pimcore/13_Bundle_Developers_Guide/03_Auto_Loading_Config_And_Routing_Definitions.md))
* `app/config/security.yml` if imported from your main `app/config/config.yml`

Those configurations will be merged together, i.e. if a bundle defines a firewall or an `access_control` entry, this entry
will always be loaded and matched **after** the admin configuration. To get an idea of the merged security configuration
you can use the `debug:config security` CLI command:

```
$ bin/console debug:config security

Current configuration for extension with alias "security"
=========================================================

security:
    providers:
        pimcore_admin:
            id: pimcore_admin.security.user_provider
        demo_cms_provider:
            memory:
                users:
                    john:
                        password: doe
                        roles:
                            - ROLE_USER
                    jane:
                        password: doe
                        roles:
                            - ROLE_ADMIN
    firewalls:
        ...
```

As result of this merging logic, please consider the following caveats:

* always specify the `provider` entry for your firewall as otherwise the `pimcore_admin` provider will be used which is
  probably not what you want
* you can use a pattern of `^/` for both firewall and `access_control` but keep in mind that the admin firewall and the
  access_control entries defined by the admin security will match first
  
## Login example

The Demo CMS profile provides a simple login example using an in-memory user provider and a `form_login` authenticatior 
which allows anonymous users on the site with additional secured areas:
 
* [security.yml](https://github.com/pimcore/pimcore/blob/master/install-profiles/demo-cms/src/AppBundle/Resources/config/pimcore/security.yml)
* [SecureController](https://github.com/pimcore/pimcore/blob/master/install-profiles/demo-cms/src/AppBundle/Controller/SecureController.php)

For more complex examples, custom user providers and a full configuration reference please read the
[Symfony Security Component documentation](http://symfony.com/doc/current/security.html).
