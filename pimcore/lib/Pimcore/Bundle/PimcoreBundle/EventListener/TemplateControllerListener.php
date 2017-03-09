<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Controller\TemplateControllerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles the attributes set by TemplateControllerInterface and injects them into the Template annotation which is
 * then processed by SensioFrameworkExtraBundle. This allows us to add view auto-rendering without depending on annotations.
 */
class TemplateControllerListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $defaultEngine;

    /**
     * @param ContainerInterface $container
     * @param string $defaultEngine
     */
    public function __construct(ContainerInterface $container, $defaultEngine = 'twig')
    {
        $this->container     = $container;
        $this->defaultEngine = $defaultEngine;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        // the view event needs to run before the SensioFrameworkExtraBundle TemplateListener
        // handles the Template annotation
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::VIEW       => ['onKernelView', 32]
        ];
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request  = $event->getRequest();
        $callable = $event->getController();

        // if controller implements TemplateControllerInterface, register it as attribute as we need it later in onKernelView
        $templateController = false;
        if (is_object($callable) && $callable instanceof TemplateControllerInterface) {
            $templateController = true;
        } else if (is_array($callable) && is_object($callable[0]) && $callable[0] instanceof TemplateControllerInterface) {
            $templateController = true;
        }

        if ($templateController) {
            $request->attributes->set(TemplateControllerInterface::ATTRIBUTE_TEMPLATE_CONTROLLER, $callable);
        }
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();

        // don't do anything if there's already a Template annotation in place
        if ($request->attributes->has('_template')) {
            return;
        }

        if (!$request->attributes->has(TemplateControllerInterface::ATTRIBUTE_TEMPLATE_CONTROLLER)) {
            return;
        }

        if ($request->attributes->has(TemplateControllerInterface::ATTRIBUTE_AUTO_RENDER)) {
            $controller = $request->attributes->get(TemplateControllerInterface::ATTRIBUTE_TEMPLATE_CONTROLLER);

            $engine = $this->defaultEngine;
            if ($request->attributes->has(TemplateControllerInterface::ATTRIBUTE_AUTO_RENDER_ENGINE)) {
                $engine = $request->attributes->get(TemplateControllerInterface::ATTRIBUTE_AUTO_RENDER_ENGINE);
            }

            $guesser = $this->container->get('sensio_framework_extra.view.guesser');

            $template = new Template([]);
            $template->setOwner($controller);
            $template->setEngine($engine);
            $templateReference = $guesser->guessTemplateName($controller, $request, $engine);
            $templateReference->set("bundle", "");
            $template->setTemplate($templateReference);

            // inject Template annotation into the request - will be used by SensioFrameworkExtraBundle
            $request->attributes->set('_template', $template);
        }
    }
}
