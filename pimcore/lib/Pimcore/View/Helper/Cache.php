<?php

class Pimcore_View_Helper_Cache extends Zend_View_Helper_Abstract {

    public static $_caches;

    public function cache($name, $lifetime = null, $force = false) {

        if (self::$_caches[$name]) {
            return self::$_caches[$name];
        }

        $cache = new Pimcore_View_Helper_Cache_Controller($name, $lifetime, $this->view->editmode);
        self::$_caches[$name] = $cache;

        return self::$_caches[$name];
    }

}


class Pimcore_View_Helper_Cache_Controller {

    public $cache;
    public $key;
    public $editmode;
    public $captureEnabled = false;
    public $force = false;

    public function __construct($name, $lifetime, $editmode = true, $force = false) {
        
        $this->key = "pimcore_viewcache_" . $name;
        $this->editmode = $editmode;
        $this->force = $force;
        
        if (!$lifetime) {
            $lifetime = null;
        }

        $this->lifetime = $lifetime;
    }

    public function start() {
                
        if($this->editmode && !$this->force) {
            return false;
        }
        
        if ($content = Pimcore_Model_Cache::load($this->key)) {
            echo $content;
            return true;
        }
        
        $this->captureEnabled = true;
        ob_start();
        
        return false;
    }
 
    public function end() {
        
        if($this->captureEnabled) {
            
            $this->captureEnabled = false;
            
            $tags = array("in_template");
            if (!$this->lifetime) {
                $tags[] = "output";
            }
    
            $content = ob_get_clean();
            Pimcore_Model_Cache::save($content, $this->key, $tags, $this->lifetime, 996);
            echo $content;
        }
    }

    public function stop() {
        $this->end();
    }
}
