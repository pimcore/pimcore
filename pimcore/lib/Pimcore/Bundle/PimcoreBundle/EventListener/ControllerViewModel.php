<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Service\Request\ViewModelResolver;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerViewModel implements EventSubscriberInterface
{
    /**
     * @var ViewModelResolver
     */
    protected $viewModelResolver;

    /**
     * @param ViewModelResolver $viewModelResolver
     */
    public function __construct(ViewModelResolver $viewModelResolver)
    {
        $this->viewModelResolver = $viewModelResolver;
    }

    /**
     * When action uses the Template annotation, add ViewModel variables to the controller result
     * before proceeding to render the template.
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();

        // only alter requests with a @Template annotation
        $template = $request->attributes->get('_template');
        if (null === $template) {
            return;
        }

        $result = $event->getControllerResult();

        // controller returned a ViewModel instance -> transform the model to array and return
        if ($result instanceof ViewModelInterface) {
            $event->setControllerResult($result->getParameters()->all());
            return;
        }

        // view model is empty -> nothing to do
        $view = $this->viewModelResolver->getViewModel($event->getRequest());
        if (null === $view || $view->count() === 0) {
            return;
        }

        if (null === $result) {
            // empty result -> add view model params
            $event->setControllerResult($view->getParameters()->all());
        } else {
            // add missing view model params to result
            if (is_array($result)) {
                $result = array_replace($view->getParameters()->all(), $result);

                $event->setControllerResult($result);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            // set a higher priority to make this run before the @Template annotation
            // handler kicks in (SensioFrameworkExtraBundle) to make sure the ViewModel
            // is processed before template is rendered
            KernelEvents::VIEW => ['onKernelView', 10]
        ];
    }
}
