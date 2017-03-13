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

namespace PimcoreLegacyBundle\Zend\View;

use Pimcore\Controller\Action\Helper\ViewRenderer;
use Pimcore\View;

class ViewProvider
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
     * Create a new view instance
     *
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
