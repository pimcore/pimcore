<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Service\Request\TemplateVarsResolver;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TemplatingGlobals implements EventSubscriberInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var TemplateVarsResolver
     */
    protected $varsResolver;

    /**
     * @param TemplateVarsResolver $varsResolver
     */
    public function __construct(TemplateVarsResolver $varsResolver)
    {
        $this->varsResolver = $varsResolver;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $engine = null;
        if ($this->container->has('templating.engine.php')) {
            $engine = $this->container->get('templating.engine.php');
        } else if ($this->container->has('debug.templating.engine.php')) {
            $engine = $this->container->get('debug.templating.engine.php');
        }

        if (null === $engine) {
            return;
        }

        $vars = $this->varsResolver->getTemplateVars($event->getRequest());

        foreach ($vars as $key => $value) {
            $engine->addGlobal($key, $value);
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }
}
