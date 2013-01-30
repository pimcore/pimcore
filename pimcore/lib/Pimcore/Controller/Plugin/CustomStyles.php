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

class Pimcore_Controller_Plugin_CustomStyles extends Zend_Controller_Plugin_Abstract {


    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse()) || $this->getRequest()->getParam("pimcore_editmode")) {
            return;
        }

        // append custom page styles,  if it is a document-request
        $doc = $this->getRequest()->getParam("document");
        if(!Staticroute::getCurrentRoute() && method_exists($doc, "getCss") && $doc->getCss()) {
            $body = $this->getResponse()->getBody();

            $code = '<style type="text/css" id="pimcore_styles">';
            $code .= "\n\n" . $doc->getCss() . "\n\n";
            $code .= '</style>';

            $headEndPosition = stripos($body, "</head>");
            if($headEndPosition !== false) {
                $body = substr_replace($body, $code."\n\n</head>", $headEndPosition, 7);
            }

            $this->getResponse()->setBody($body);
        }
    }
}

