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
use Pimcore\Tool\Authentication;
use Pimcore\Model;

class AdminButton extends \Zend_Controller_Plugin_Abstract {


    /**
     *
     */
    public function dispatchLoopShutdown() {

        if(!Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        if(!Tool::useFrontendOutputFilters($this->getRequest()) && !$this->getRequest()->getParam("pimcore_preview")) {
            return;
        }

        if(isset($_COOKIE["pimcore_admin_sid"])) {
            try {
                // we should not start a session here as this can break the functionality of the site if
                // the website itself uses sessions, so we include the code, and check asynchronously if the user is logged in
                // this is done by the embedded script
                $body = $this->getResponse()->getBody();

                $document = $this->getRequest()->getParam("document");
                if($document instanceof Model\Document && !Model\Staticroute::getCurrentRoute()) {
                    $documentId = $document->getId();
                }

                if(!isset($documentId) || !$documentId) {
                    $documentId = "null";
                }

                $code = '<script type="text/javascript" src="/admin/admin-button/script?documentId=' . $documentId . '"></script>';

                // search for the end <head> tag, and insert the google analytics code before
                // this method is much faster than using simple_html_dom and uses less memory
                $bodyEndPosition = stripos($body, "</body>");
                if($bodyEndPosition !== false) {
                    $body = substr_replace($body, $code . "\n\n</body>\n", $bodyEndPosition, 7);
                }

                $this->getResponse()->setBody($body);

            } catch (\Exception $e) {
                \Logger::error($e);
            }
        }
    }
}
