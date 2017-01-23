<?php

namespace PimcoreBundle\View;

use Pimcore\Controller\Action\Helper\ViewRenderer;
use Pimcore\View;

class ZendViewProvider
{
    /**
     * @return View
     */
    public function createView()
    {
        $view = new View();
        $view->addHelperPath(PIMCORE_PATH . '/lib/Pimcore/View/Helper', '\\Pimcore\\View\\Helper\\');

        $renderer = new ViewRenderer($view);
        $renderer->initView();

        return $view;
    }
}
