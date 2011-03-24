<?php 

class Pimcore_Controller_Action_Helper_ViewRenderer extends Zend_Controller_Action_Helper_ViewRenderer {


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
}
