# Working with Sessions

If you need sessions, please use the native session handling provided by Symfony (configured through the `framework.session` config). 
For details see [sessions docs](https://symfony.com/doc/5.2/components/http_foundation/sessions.html). 

In case  you need to add a custom session bag for your bundle or application, then implement an EventListener to register the session bag before the session started.

#### Register a Session Bag through EventListener
```php
<?php
 
namespace TestBundle\EventListener;
 
use Pimcore\Session\SessionConfiguratorInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
 
class SessionBagListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            //run after Symfony\Component\HttpKernel\EventListener\SessionListener
            KernelEvents::REQUEST => ['onKernelRequest', 127],
        ];
    }
    
    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $session = $event->getRequest()->getSession();
        $bag = new AttributeBag('_session_cart');
        $bag->setName('session_cart');
 
        $session->registerBag($bag);
    }
}
```

#### Usage of Configured Session, e.g. in Controller
```php
<?php
if ($request->hasSession()) {
    $session = $request->getSession();
     
    /** @var AttributeBag $bag */
    $bag = $session->getBag('session_cart');
    $bag->set('foo', 1);
}
```

Symfony framework session is configured by default, so you don't need to configure the session in your `config.yaml`.


Admin sessions `Pimcore\Tool\Session::getReadonly()` returns an `AttributeBagInterface`. 
