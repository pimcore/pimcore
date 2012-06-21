<?php

class Website_Controller_Action extends Pimcore_Controller_Action_Frontend {
	
	public function init () {
		
        parent::init();
        
		if(Zend_Registry::isRegistered("Zend_Locale")) {
            $locale = Zend_Registry::get("Zend_Locale");
        } else {
            $locale = new Zend_Locale("en");
        }

        $this->view->language = $locale->getLanguage();
        $this->language = $locale->getLanguage();
	}
	
}
