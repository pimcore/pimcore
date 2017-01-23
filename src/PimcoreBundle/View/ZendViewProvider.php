<?php

namespace PimcoreBundle\View;

use Pimcore\Controller\Action\Helper\ViewRenderer;
use Pimcore\View;
use PimcoreBundle\HttpKernel\LegacyKernel;

class ZendViewProvider
{
    /**
     * @var LegacyKernel
     */
    protected $legacyKernel;

    /**
     * @var ViewRenderer
     */
    protected $viewRenderer;

    /**
     * @param LegacyKernel $legacyKernel
     */
    public function __construct(LegacyKernel $legacyKernel)
    {
        $this->legacyKernel = $legacyKernel;
    }

    /**
     * Get the view renderer instance from
     *
     * @return ViewRenderer
     */
    public function getViewRenderer()
    {
        if (null === $this->viewRenderer) {
            // make sure the MVC is initialized
            $this->legacyKernel->boot();

            /** @var ViewRenderer $viewRenderer */
            $this->viewRenderer = \Zend_Controller_Action_HelperBroker::getExistingHelper('ViewRenderer');
            $this->viewRenderer->init();
        }

        return $this->viewRenderer;
    }

    /**
     * @return View
     */
    public function getView()
    {
        return $this->getViewRenderer()->getView();
    }
}
