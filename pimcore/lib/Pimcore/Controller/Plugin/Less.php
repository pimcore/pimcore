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

class Less extends \Zend_Controller_Plugin_Abstract {

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var
     */
    protected $conf;

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return bool|void
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request) {

        $this->conf = \Pimcore\Config::getSystemConfig();

        if($request->getParam('disable_less_compiler') || $_COOKIE["disable_less_compiler"]){
            return $this->disable();
        }

        if (!$this->conf->outputfilters) {
            return $this->disable();
        }

        if (!$this->conf->outputfilters->less) {
            return $this->disable();
        }

    }

    /**
     * @return bool
     */
    public function disable() {
        $this->enabled = false;
        return true;
    }

    /**
     *
     */
    public function dispatchLoopShutdown() {

        if(!\Pimcore\Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        if ($this->enabled) {

            include_once("simple_html_dom.php");

            if($this->getRequest()->getParam("pimcore_editmode")) {
                $this->editmode();
            } else {
                $this->frontend();
            }
        }
    }

    /**
     *
     */
    protected function frontend () {

        $body = $this->getResponse()->getBody();

        $body = \Pimcore\Tool\Less::processHtml($body);

        $this->getResponse()->setBody($body);
    }

    /**
     *
     */
    protected function editmode () {
        $body = $this->getResponse()->getBody();

        $html = str_get_html($body);

        if($html) {
            $head = $html->find("head",0);
            if($head) {
                $head->innertext = $head->innertext . "\n" . '<script type="text/javascript">var less = {"env": "development"};</script><script type="text/javascript" src="/pimcore/static/js/lib/less.js"></script>'."\n";

                $body = $html->save();
                $this->getResponse()->setBody($body);
            }

            $html->clear();
            unset($html);
        }
    }
}

