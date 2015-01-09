<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Controller\Action\Helper;

use Pimcore\Controller\Action\Frontend as FrontendController;
use Pimcore\Tool;
use Pimcore\View;

class ViewRenderer extends \Zend_Controller_Action_Helper_ViewRenderer {

    /**
     * @var bool
     */
    public $isInitialized = false;

    /**
     *
     */
    public function postDispatch() {

        if ($this->_shouldRender()) {
            if (method_exists($this->getActionController(), "getRenderScript")) {
                if ($script = $this->getActionController()->getRenderScript()) {
                    $this->renderScript($script);
                }
            }
        }
        
        parent::postDispatch();

        // append custom styles to response body
        if($this->getActionController() instanceof FrontendController) {
            $doc = $this->getActionController()->getDocument();
            if(Tool::isHtmlResponse($this->getResponse())
                && $doc && method_exists($doc, "getCss") && $doc->getCss()
                && !$this->getRequest()->getParam("pimcore_editmode")) {

                $code = '<style type="text/css" id="pimcore_styles_' . $doc->getId() . '">';
                $code .= "\n\n" . $doc->getCss() . "\n\n";
                $code .= '</style>';

                $name = $this->getResponseSegment();
                $this->getResponse()->appendBody(
                    $code,
                    $name
                );
            }
        }
    }

    /**
     * @param null $path
     * @param null $prefix
     * @param array $options
     */
    public function initView($path = null, $prefix = null, array $options = array())
    {
        if (null === $this->view) {
            $view = new View();
            $view->setRequest($this->getRequest());
            $view->addHelperPath(PIMCORE_PATH . "/lib/Pimcore/View/Helper", "\\Pimcore\\View\\Helper\\");

            $this->setView($view);
        }

        parent::initView($path, $prefix, $options);


        $this->setViewSuffix(View::getViewScriptSuffix());

        // this is very important, the initView could be called multiple times.
        // if we add the path on every call, we have big performance issues.
        if($this->isInitialized) {
            return;
        }

        $this->isInitialized = true;

        $paths = $this->view->getScriptPaths();
        // script pathes for layout path
        foreach (array_reverse($paths) as $path) {
            $path = str_replace("\\","/",$path);
            if(!in_array($path, $paths)) {
                $this->view->addScriptPath($path);
            }

            $path = str_replace("/scripts", "/layouts", $path);
            if(!in_array($path, $paths)) {
                $this->view->addScriptPath($path);
            }
        }

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
}

// unfortunately we need this alias here, since ZF plugin loader isn't able to handle namespaces correctly
class_alias("Pimcore\\Controller\\Action\\Helper\\ViewRenderer", "Pimcore_Controller_Action_Helper_ViewRenderer");