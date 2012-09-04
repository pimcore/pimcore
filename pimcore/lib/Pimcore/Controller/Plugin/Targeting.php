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

class Pimcore_Controller_Plugin_Targeting extends Zend_Controller_Plugin_Abstract {

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var Document
     */
    protected $document;

    public function routeShutdown(Zend_Controller_Request_Abstract $request) {
        if(!Pimcore_Tool::useFrontendOutputFilters($request)) {
            return $this->disable();
        }
        
        if($request->getParam("document") instanceof Document_Page) {
            $this->document = $request->getParam("document");
        } else {
            $this->disable();
        }
    }

    public function disable() {
        $this->enabled = false;
        return true;
    }

    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        if ($this->enabled && $this->document) {

            $targets = array();
            $list = new Document_Page_Targeting_List();
            $list->setCondition("documentId = ?", $this->document->getId());

            foreach($list->load() as $target) {

                $redirectUrl = $target->getActions()->getRedirectUrl();
                if(is_numeric($redirectUrl)) {
                    $doc = Document::getById($redirectUrl);
                    if($doc instanceof Document) {
                        $target->getActions()->redirectUrl = $doc->getFullPath();
                    }
                }

                $targets[] = $target;
            }

            $controlCode = file_get_contents(__DIR__ . "/Targeting/targeting.js");
            $controlCode = JSMinPlus::minify($controlCode);

            $code = '<script type="text/javascript">var _ptd = ' . Zend_Json::encode($targets) . '</script>';
            $code .= '<script type="text/javascript">' . $controlCode . '</script>' . "\n";
            // analytics
            $body = $this->getResponse()->getBody();

            // search for the end <head> tag, and insert the google analytics code before
            // this method is much faster than using simple_html_dom and uses less memory
            $headEndPosition = strpos($body, "</head>");
            if($headEndPosition !== false) {
                $body = substr_replace($body, $code."</head>", $headEndPosition, 7);
            }

            $this->getResponse()->setBody($body);
        }
    }
}
