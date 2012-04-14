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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Controller_Action_Helper_ViewRenderer extends Zend_Controller_Action_Helper_ViewRenderer {

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
    }

    /**
     * @param null $path
     * @param null $prefix
     * @param array $options
     */
    public function initView($path = null, $prefix = null, array $options = array())
    {
        if (null === $this->view) {
            $view = new Pimcore_View();
            $view->setRequest($this->getRequest());
            $view->addHelperPath(PIMCORE_PATH . "/lib/Pimcore/View/Helper", "Pimcore_View_Helper_");

            $this->setView($view);
        }

        parent::initView($path, $prefix, $options);


        $this->setViewSuffix(Pimcore_View::getViewScriptSuffix());

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
     * @param boolean $isInitialized
     */
    public function setIsInitialized($isInitialized)
    {
        $this->isInitialized = $isInitialized;
    }

    /**
     * @return boolean
     */
    public function getIsInitialized()
    {
        return $this->isInitialized;
    }
}
