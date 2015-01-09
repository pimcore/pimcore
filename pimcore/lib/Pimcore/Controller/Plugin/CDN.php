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

namespace Pimcore\Controller\Plugin;

use Pimcore\Tool;
use Pimcore\Model\Cache as CacheManager;

class CDN extends \Zend_Controller_Plugin_Abstract {

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var
     */
    protected $hostnames;

    /**
     * @var
     */
    protected $patterns;

    /**
     * @var
     */
    protected $cachedItems;

    /**
     * @var
     */
    protected $conf;

    /**
     * @var array
     */
    protected $cdnhostnames = array();

    /**
     * @var array
     */
    protected $cdnpatterns = array();

    /**
     *
     */
    const cacheKey = "cdn_pathes";

    /**
     *
     */
    public function enable () {
        $this->enabled = true;
    }

    /**
     *
     */
    public function disable() {
        $this->enabled = false;
    }

    /**
     * @return array
     */
    protected function getHostnames () {
        if($this->hostnames === null) {
            $this->hostnames = array();
            $hosts = $this->getCdnhostnames();
            if(is_array($hosts) && count($hosts) > 0) {
                $this->hostnames = $hosts;
            }
        }
        return $this->hostnames;
    }

    /**
     * @return array
     */
    protected function getPatterns () {
        if($this->patterns === null) {
            $this->patterns = array();
            $patterns = $this->getCdnpatterns();
            if(is_array($patterns) && count($patterns) > 0) {
                $this->patterns = $patterns;
            }
        }
        return $this->patterns;
    }

    /**
     * @param $path
     * @return bool
     */
    protected function pathMatch ($path) {
        foreach ($this->getPatterns() as $pattern) {
            if(@preg_match($pattern,$path)) {
                if(strpos($path,"/") === 0) {
                    return true;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @return array|mixed
     */
    protected function getStorage () {
        if($this->cachedItems === null) {
            $this->cachedItems = array();
            if ($items = CacheManager::load(self::cacheKey)) {
                $this->cachedItems = $items; 
            }
        }
        return $this->cachedItems;
    }

    /**
     * @param $path
     * @return string
     */
    protected function rewritePath ($path) {
        $store = $this->getStorage();
        if($store[$path]) {
            return $store[$path];
        }
        
        $hosts = $this->getHostnames();
        $i = array_rand($hosts);
        
        $new = $hosts[$i].$path;
        $this->cachedItems[$path] = $new;
        
        return $new;
    }

    /**
     *
     */
    public function dispatchLoopShutdown() {
        
        if(!Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        if ($this->enabled) {
            
            include_once("simple_html_dom.php");
            
            $body = $this->getResponse()->getBody();
            
            $html = str_get_html($body);
            if($html) {
                $elements = $html->find("link[rel=stylesheet], img, script[src]");

                foreach ($elements as $element) {
                    if($element->tag == "link") {
                        if($this->pathMatch($element->href)) {
                            $element->href = $this->rewritePath($element->href);
                        }
                    }
                    else if ($element->tag == "img") {
                        if($this->pathMatch($element->src)) {
                            $element->src = $this->rewritePath($element->src);
                        }
                    }
                    else if ($element->tag == "script") {
                        if($this->pathMatch($element->src)) {
                            $element->src = $this->rewritePath($element->src);
                        }
                    }
                }

                $body = $html->save();

                $html->clear();
                unset($html);

                $this->getResponse()->setBody($body);

                // save storage
                CacheManager::save($this->cachedItems, self::cacheKey, array(), 3600);
            }
        }
    }

    /**
     * @param $cdnhostnames
     * @return $this
     */
    public function setCdnhostnames($cdnhostnames)
    {
        $this->cdnhostnames = $cdnhostnames;
        return $this;
    }

    /**
     * @return array
     */
    public function getCdnhostnames()
    {
        return $this->cdnhostnames;
    }

    /**
     * @param $cdnpatterns
     * @return $this
     */
    public function setCdnpatterns($cdnpatterns)
    {
        $this->cdnpatterns = $cdnpatterns;
        return $this;
    }

    /**
     * @return array
     */
    public function getCdnpatterns()
    {
        return $this->cdnpatterns;
    }
}

