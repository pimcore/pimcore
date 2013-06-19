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

class Pimcore_Controller_Plugin_AdminButton extends Zend_Controller_Plugin_Abstract {


    /**
     *
     */
    public function dispatchLoopShutdown() {

        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        if(isset($_COOKIE["pimcore_admin_sid"])) {

            $user = Pimcore_Tool_Authentication::authenticateSession();
            if($user instanceof User) {
                $body = $this->getResponse()->getBody();

                if($this->getRequest()->getParam("document") && !Staticroute::getCurrentRoute()) {
                    $documentId =    $this->getRequest()->getParam("document")->getId();
                }

                $config = Pimcore_Config::getSystemConfig();
                $contactEnabled = false;
                if($config->general->contactemail) {
                    $contactEnabled = true;
                }

                $code = "\n\n\n<!-- pimcore admin console -->\n";
                $code .= '<script type="text/javascript">
                    var pimcore = pimcore || {};
                    pimcore["admin"] = {documentId: ' . $documentId . ', contactEnabled: ' . Zend_Json::encode($contactEnabled) .'};
                </script>';

                $code .= '<script type="text/javascript" src="/pimcore/static/js/frontend/admin/admin.js"></script>';
                $code .= '<link rel="stylesheet" type="text/css" href="/pimcore/static/js/frontend/admin/admin.css" />' . "\n\n\n";

                // search for the end <head> tag, and insert the google analytics code before
                // this method is much faster than using simple_html_dom and uses less memory
                $bodyEndPosition = stripos($body, "</body>");
                if($bodyEndPosition !== false) {
                    $body = substr_replace($body, $code . "\n\n</body>\n", $bodyEndPosition, 7);
                }

                $this->getResponse()->setBody($body);
            }
        }
    }
}
