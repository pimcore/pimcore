<?php

namespace Pimcore\Bundle\PimcoreBundle\View;

use Pimcore\Controller\Action\Helper\ViewRenderer;
use Pimcore\View;

class ZendViewProvider
{
    /**
     * @var ViewRenderer
     */
    protected $viewRenderer;

    /**
     * Create a basic view renderer
     *
     * @return ViewRenderer
     */
    protected function getViewRenderer()
    {
        if (null === $this->viewRenderer) {
            // mini-MVC boot
            $request = new \Zend_Controller_Request_Http();

            $frontController = \Zend_Controller_Front::getInstance();
            $frontController->setRequest($request);

            // set custom view renderer
            $viewRenderer = new ViewRenderer();

            /** @var ViewRenderer $viewRenderer */
            $this->viewRenderer = $viewRenderer;
        }

        return $this->viewRenderer;
    }

    /**
     * @param array $params
     * @return View
     */
    public function createView(array $params = [])
    {
        $view = new View();
        foreach ($params as $key => $value) {
            $view->$key = $value;
        }

        $viewRenderer = $this->getViewRenderer();
        $viewRenderer->configureView($view);

        return $view;
    }
}
