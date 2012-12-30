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

class Pimcore_Controller_Plugin_ContentLog extends Zend_Controller_Plugin_Abstract {

    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        try {
            $req = $this->getRequest();
            $db = Pimcore_Resource::get();
            $content = $this->getResponse()->getBody();
            $url = $req->getScheme() . '://' . $req->getHttpHost() . $req->getRequestUri();
            $id = md5($url);

            $site = null;
            if(Site::isSiteRequest()) {
                $site = Site::getCurrentSite()->getId();
            }

            $type = null;
            $typeReference = null;
            if(Staticroute::getCurrentRoute() instanceof Staticroute) {
                $type = "route";
                $typeReference = Staticroute::getCurrentRoute()->getId();
            } else if ($req->getParam("document") instanceof Document) {
                $type = "document";
                $typeReference = $req->getParam("document")->getId();
            }

            $data = array(
                "id" => $id,
                "site" => $site,
                "url" => $url,
                "content" => $content,
                "type" => $type,
                "typeReference" => $typeReference,
                "lastUpdate" => time()
            );

            $existing = $db->fetchRow("SELECT id,lastUpdate from content_index WHERE id = ?", array($id));
            if($existing) {
                if(($existing["lastUpdate"] < (time()-86400))) {
                    $db->update("content_index", $data, "id = '" . $id . "'");
                }
            } else {
                $db->insert("content_index", $data);
            }

        } catch (Exception $e) {
            Logger::error($e);
        }
    }
}
