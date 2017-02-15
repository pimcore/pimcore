<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Service\Request\TemplateResolver;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerTemplate implements EventSubscriberInterface
{
    /**
     * @var TemplateResolver
     */
    protected $templateResolver;

    /**
     * @param TemplateResolver $templateResolver
     */
    public function __construct(TemplateResolver $templateResolver)
    {
        $this->templateResolver = $templateResolver;
    }

    /**
     * If there's a contentTemplate attribute set on the request, it was read from the document template setting from
     * the router or from the sub-action renderer and takes precedence over the auto-resolved and manually configured
     * template.
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();

        $resolvedTemplate = $this->templateResolver->getTemplate($request);
        if (null === $resolvedTemplate) {
            // no contentTemplate on the request -> nothing to do
            return;
        }

        $template = $request->attributes->get('_template');

        // no @Template present -> nothing to do
        if (null === $template || !($template instanceof Template)) {
            return;
        }

        $template->setTemplate($resolvedTemplate);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            // this must run after the TemplateControllerListener set a potential template and before the TemplateListener
            // renders the view
            KernelEvents::VIEW => ['onKernelView', 16]
        ];
    }
}
