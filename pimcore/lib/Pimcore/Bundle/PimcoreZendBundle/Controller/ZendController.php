<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Model\Document;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * @property ViewModel $view
 * @property Document|Document\PageSnippet $document
 */
abstract class ZendController extends Controller implements EventedControllerInterface
{
    /**
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
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event)
    {
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function postDispatch(FilterResponseEvent $event)
    {
    }
}
