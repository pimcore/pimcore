# Authenticator Based Security

As Pimcore uses the Symfony Security Component for authentication/authorization of the Pimcore admin UI and also
provides the capabilities to use the same security component on frontend websites, it is important to adapt to the ongoing
changes in the Symfony security component. 

As starting with Symfony 6.2, setting the `enable_authenticator_manager` to `true` is deprecated.
Please refactor your `security.yaml` file and remove the `enable_authenticator_manager` setting.

For more information on new Authenticator Based Security, please read the
[Symfony Security Component documentation](https://symfony.com/doc/current/security.html).
