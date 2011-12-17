<?php 

class Pimcore_Controller_Action_Helper_ViewRenderer extends Zend_Controller_Action_Helper_ViewRenderer {


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

        // script pathes for layout path
        foreach (array_reverse($this->view->getScriptPaths()) as $path) {
            $path = str_replace("\\","/",$path);
            $this->view->addScriptPath($path);
            $this->view->addScriptPath(str_replace("/scripts", "/layouts", $path));
        }

        $this->setViewSuffix(Pimcore_View::getViewScriptSuffix());
    }
}
