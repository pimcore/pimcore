<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Http\Request\Resolver\ViewModelResolver;
use Pimcore\Templating\Model\ViewModelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @deprecated
 */
class ControllerViewModelListener implements EventSubscriberInterface
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
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            // set a higher priority to make this run before the @Template annotation
            // handler kicks in (SensioFrameworkExtraBundle) to make sure the ViewModel
            // is processed before template is rendered
            KernelEvents::VIEW => ['onKernelView', 10],
        ];
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
}
