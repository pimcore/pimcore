<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Controller\ViewAwareInterface;
use Pimcore\Bundle\PimcoreBundle\Service\Request\TemplateVarsResolver;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerViewModel implements EventSubscriberInterface
{
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

    /**
     * If the called controller implements ViewAwareInterface, add a view variable to the controller and populate it
     * with default template vars.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $callable = $event->getController();
        if (!is_array($callable)) {
            return;
        }

        $request    = $event->getRequest();
        $controller = $callable[0];

        if ($controller instanceof ViewAwareInterface) {
            $controller->setView($this->generateViewModel($request));
            $request->attributes->set('_view_aware_controller', $controller);
        }
    }

    /**
     * @param Request $request
     * @return ViewModel
     */
    protected function generateViewModel(Request $request)
    {
        // create view model and add default template vars (document, editmode)
        $vars = $this->varsResolver->getTemplateVars($request);
        $view = new ViewModel($vars);

        return $view;
    }

    /**
     * Handle controller returning a ViewModel or implementing ViewModelInterface
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        // controller returned a ViewModel instance -> just transform the model to array and return
        if ($result instanceof ViewModelInterface) {
            $event->setControllerResult($result->getParameters()->all());

            return;
        }

        // handle ViewAwareInterface controllers
        $controller = $event->getRequest()->attributes->get('_view_aware_controller');
        if (!$controller || !($controller instanceof ViewAwareInterface)) {
            return;
        }

        // view has no params -> nothing to do
        $view = $controller->getView();
        if ($view->getParameters()->count() === 0) {
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
            KernelEvents::CONTROLLER => 'onKernelController',

            // set a higher priority to make this run before the @Template annotation
            // handler kicks in (SensioFrameworkExtraBundle) to make sure the ViewModel
            // is processed before
            KernelEvents::VIEW => ['onKernelView', 10]
        ];
    }
}
