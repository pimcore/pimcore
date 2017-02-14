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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller\Action\Helper;

use Pimcore\Controller\Action\Frontend as FrontendController;
use Pimcore\Tool;
use Pimcore\View;

class ViewRenderer extends \Zend_Controller_Action_Helper_ViewRenderer
{

    /**
     * @var bool
     */
    public $isInitialized = false;

    /**
     *
     */
    public function postDispatch()
    {
        if ($this->_shouldRender()) {
            if (method_exists($this->getActionController(), "getRenderScript")) {
                if ($script = $this->getActionController()->getRenderScript()) {
                    $this->renderScript($script);
                }
            }
        }
        
        parent::postDispatch();
    }

    /**
     * @param null $path
     * @param null $prefix
     * @param array $options
     */
    public function initView($path = null, $prefix = null, array $options = [])
    {
        if (null === $this->view) {
            $view = new View();
            $this->configureView($view);

            $this->setView($view);
        }

        parent::initView($path, $prefix, $options);

        $this->setViewSuffix(View::getViewScriptSuffix());

        // this is very important, the initView could be called multiple times.
        // if we add the path on every call, we have big performance issues.
        if ($this->isInitialized) {
            return;
        }

        $this->isInitialized = true;

        $paths = $this->view->getScriptPaths();
        // script pathes for layout path
        foreach (array_reverse($paths) as $path) {
            $path = str_replace("\\", "/", $path);
            if (!in_array($path, $paths)) {
                $this->view->addScriptPath($path);
            }

            $path = str_replace("/scripts", "/layouts", $path);
            if (!in_array($path, $paths)) {
                $this->view->addScriptPath($path);
            }
        }
    }

    /**
     * Configure view request and helpers. Also called from Symfony ZendViewProvider
     *
     * @param View $view
     * @param \Zend_Controller_Request_Http|null $request
     */
    public function configureView(View $view, \Zend_Controller_Request_Http $request = null)
    {
        if (null === $request) {
            $request = $this->getRequest();
        }

        $view->setRequest($request);
        $view->addHelperPath(PIMCORE_PATH . "/lib/Pimcore/View/Helper", "\\Pimcore\\View\\Helper\\");
    }

    /**
     * @param $isInitialized
     * @return $this
     */
    public function setIsInitialized($isInitialized)
    {
        $this->isInitialized = $isInitialized;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsInitialized()
    {
        return $this->isInitialized;
    }

    /**
     * @return View|\Zend_View_Interface
     */
    public function getView()
    {
        return $this->view;
    }
}

// unfortunately we need this alias here, since ZF plugin loader isn't able to handle namespaces correctly
class_alias("Pimcore\\Controller\\Action\\Helper\\ViewRenderer", "Pimcore_Controller_Action_Helper_ViewRenderer");
