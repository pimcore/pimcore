<?php

namespace Pimcore\Bundle\PimcoreBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Controller\Traits\TemplateControllerTrait;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Model\Document;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * @property ViewModel $view
 * @property Document|Document\PageSnippet $document
 * @property bool $editmode
 */
abstract class FrontendController extends Controller implements EventedControllerInterface, TemplateControllerInterface
{
    use TemplateControllerTrait;

    /**
     * Expose view, document and editmode as properties and proxy them to request attributes through
     * their resolvers.
     *
     * @inheritDoc
     */
    public function __get($name)
    {
        if ('view' === $name) {
            return $this->get('pimcore.service.request.view_model_resolver')->getViewModel();
        }

        if ('document' === $name) {
            return $this->get('pimcore.service.request.document_resolver')->getDocument();
        }

        if ('editmode' === $name) {
            return $this->get('pimcore.service.request.editmode_resolver')->isEditmode();
        }

        throw new \RuntimeException(sprintf('Trying to read undefined property "%s"', $name));
    }

    /**
     * @inheritDoc
     */
    public function __set($name, $value)
    {
        $requestAttributes = ['view', 'document', 'editmode'];
        if (in_array($name, $requestAttributes)) {
            throw new \RuntimeException(sprintf(
                'Property "%s" is a request attribute and can\'t be set on the controller instance',
                $name
            ));
        }

        throw new \RuntimeException(sprintf('Trying to set unknown property "%s"', $name));
    }

    /**
     * @inheritDoc
     */
    public function onKernelController(FilterControllerEvent $event)
    {
    }

    /**
     * @inheritDoc
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
    }

    /**
     * Enable view autorendering for the current request
     *
     * @param Request $request
     * @param string $engine
     */
    protected function enableViewAutoRender(Request $request = null, $engine = 'php')
    {
        if (null === $request) {
            $request = $this->get('request_stack')->getCurrentRequest();
        }

        $this->setViewAutoRender($request, true, $engine);
    }

    /**
     * Disable view autorendering for the current request
     *
     * @param Request $request
     */
    protected function disableViewAutoRender(Request $request = null)
    {
        if (null === $request) {
            $request = $this->get('request_stack')->getCurrentRequest();
        }

        $this->setViewAutoRender($request, false);
    }

    /**
     * @inheritDoc
     */
    protected function getTemplateGuesser()
    {
        return $this->get('sensio_framework_extra.view.guesser');
    }
}
