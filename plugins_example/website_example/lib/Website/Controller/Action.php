<?php

class Website_Controller_Action extends Pimcore_Controller_Action_Frontend {
	
	public function init () {
		
        parent::init();
        
		try {
            $locale = Zend_Registry::get("Zend_Locale");
        } catch (Exception $e) {
            $locale = new Zend_Locale("en");
        }

        $this->view->language = $locale->getLanguage();
        $this->language = $locale->getLanguage();
	}
	
}
