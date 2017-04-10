# Working with Sessions

If you need sessions, please use the native session handling provided by Symfony (configured through the `framework.session config`). 
For details see [sessions docs](https://symfony.com/doc/current/components/http_foundation/sessions.html). 

Pimcore adds the possibility to configure sessions before they are started through `SessionConfiguratorInterface` registered 
as service with the `pimcore.session.configurator tag`. This is useful when you need a custom session bag for your bundle
or application. 

#### Sample Session Configurator
```php
<?php
 
namespace TestBundle\Session\Configurator;
 
use Pimcore\Session\SessionConfiguratorInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
 
class SessionCartConfigurator implements SessionConfiguratorInterface
{
    /**
     * @inheritDoc
     */
    public function configure(SessionInterface $session)
    {
        $bag = new NamespacedAttributeBag('_session_cart');
        $bag->setName('session_cart');
 
        $session->registerBag($bag);
    }
}

```

#### Session Configurator Service Definition
```yml 
services:
    test.session.configurator.session_cart:
        class: TestBundle\Session\Configurator\SessionCartConfigurator
        tags:
            - { name: pimcore.session.configurator }
```

#### Usage of Configured Session, e.g. in Controller
```php
<?php
$session = $request->getSession();
 
/** @var NamespacedAttributeBag $bag */
$bag = $session->getBag('session_cart');
$bag->set('foo', 1);
```

Symfony framework session is configured by default, so you don't need to configure the session in your `config.yml`.




Admin sessions `Pimcore\Tool\Session::get/getReadonly()` returns an `AttributeBagInterface` . 
