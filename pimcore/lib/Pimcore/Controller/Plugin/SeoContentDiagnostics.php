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

class Pimcore_Controller_Plugin_SeoContentDiagnostics extends Zend_Controller_Plugin_Abstract {

    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse()) || !$this->getRequest()->isGet()) {
            return;
        }

        // check if it is enabled
        $config = Pimcore_Report_SeoContentDiagnostics::getSiteConfig();
        if(!$config || !$config->enabled) {
            return;
        }

        $siteId = null;
        try {
            $site = Zend_Registry::get("pimcore_site");
            $siteId = $site->getId();
        }
        catch (Exception $e) {}

        $documentId = null;
        if($document = $this->getRequest()->getParam("document")) {
            $documentId = $document->getId();
        }

        try {
            $db = Pimcore_Resource::get();

            $host = $_SERVER["HTTP_HOST"];
            $uri = $this->getRequest()->getRequestUri();
            $db->insert("seo_content_diagnostics_queue", array(
                "siteId" => $siteId,
                "host" => $host,
                "uri" => $uri,
                "documentId" => $documentId,
                "requestHeaders" => Pimcore_Tool_Serialize::serialize($_SERVER),
                "responseHeaders" => Pimcore_Tool_Serialize::serialize($this->getResponse()->getHeaders()),
                "responseCode" => $this->getResponse()->getHttpResponseCode(),
                "content" => $this->getResponse()->getBody(),
                "date" => time()
            ));
        } catch (Exception $e) {
            Logger::error($e);
        }
    }
}

