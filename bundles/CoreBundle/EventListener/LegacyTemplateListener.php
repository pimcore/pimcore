<?php

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\EventListener\TemplateListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Templating\EngineInterface;

/**
 * @deprecated
 * Provides backward compatibility for PHP templates
 */
class LegacyTemplateListener extends TemplateListener
{
    /**
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * @return EngineInterface
     */
    public function getTemplateEngine(): EngineInterface
    {
        return $this->templateEngine;
    }

    /**
     * @param EngineInterface $templateEngine
     */
    public function setTemplateEngine(EngineInterface $templateEngine): void
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * @inheritdoc
     */
    public function onKernelView(KernelEvent $event)
    {

        /* @var Template $template */
        $request = $event->getRequest();
        $template = $request->attributes->get('_template');

        if (!$template instanceof Template) {
            return;
        }

        if (!$event instanceof GetResponseForControllerResultEvent) {
            return;
        }

        $parameters = $event->getControllerResult();
        $owner = $template->getOwner();
        list($controller, $action) = $owner;

        // when the annotation declares no default vars and the action returns
        // null, all action method arguments are used as default vars
        if (null === $parameters) {
            $parameters = $this->resolveDefaultParameters($request, $template, $controller, $action);
        }

        // attempt to render the actual response
        $templating = $this->getTemplateEngine();

        if ($template->isStreamable()) {
            $callback = function () use ($templating, $template, $parameters) {
                return $templating->stream($template->getTemplate(), $parameters);
            };

            $event->setResponse(new StreamedResponse($callback));
        }

        // make sure the owner (controller+dependencies) is not cached or stored elsewhere
        $template->setOwner([]);

        $event->setResponse($templating->renderResponse($template->getTemplate(), $parameters));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', -128],
            KernelEvents::VIEW => 'onKernelView',
        ];
    }

    private function resolveDefaultParameters(Request $request, Template $template, $controller, $action)
    {
        $parameters = [];
        $arguments = $template->getVars();

        if (0 === \count($arguments)) {
            $r = new \ReflectionObject($controller);

            $arguments = [];
            foreach ($r->getMethod($action)->getParameters() as $param) {
                $arguments[] = $param;
            }
        }

        // fetch the arguments of @Template.vars or everything if desired
        // and assign them to the designated template
        foreach ($arguments as $argument) {
            if ($argument instanceof \ReflectionParameter) {
                $parameters[$name = $argument->getName()] = !$request->attributes->has($name) && $argument->isDefaultValueAvailable() ? $argument->getDefaultValue() : $request->attributes->get($name);
            } else {
                $parameters[$argument] = $request->attributes->get($argument);
            }
        }

        return $parameters;
    }
}
