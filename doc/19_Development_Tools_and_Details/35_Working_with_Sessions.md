# Working With Sessions

If you need sessions, please use the native session handling provided by Symfony (configured through the `framework.session` config). 
For details see [sessions docs](https://symfony.com/doc/current/components/http_foundation.html#session). 

In case  you need to add a custom session bag for your bundle or application, then implement an EventListener to register the session bag before the session started.

#### Register a Session Bag through EventListener
```php
<?php
 
namespace TestBundle\EventListener;

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

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        
        if ($event->getRequest()->attributes->get('_stateless', false)) {
            return;
        }

        $session = $event->getRequest()->getSession();
        
        //do not register bags, if session is already started
        if ($session->isStarted()) {
            return;
        }

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
