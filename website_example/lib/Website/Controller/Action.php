<?php

namespace Website\Controller;

use Pimcore\Controller\Action\Frontend;

class Action extends Frontend
{
    public function init()
    {
        if (self::$isInitial) {
            // only do this once
            // eg. registering view helpers or other generic things
        }

        parent::init();

        if (\Zend_Registry::isRegistered("Zend_Locale")) {
            $locale = \Zend_Registry::get("Zend_Locale");
        } else {
            $locale = new \Zend_Locale("en");
            \Zend_Registry::set("Zend_Locale", $locale);
        }

        $this->view->language = (string) $locale;
        $this->language = (string) $locale;
    }
}
