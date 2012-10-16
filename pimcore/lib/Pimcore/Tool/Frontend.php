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

class Pimcore_Tool_Frontend {
    
    /**
     * Returns the Website-Config
     * @return Zend_Config
     * @depricated
     */
    public static function getWebsiteConfig () {
        return Pimcore_Config::getWebsiteConfig();
    }

    /**
     * @static
     * @param null|Site $site
     * @return string
     */
    public static function getSiteKey (Site $site = null) {
        // check for site
        if(!$site) {
            if(Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
            } else {
                $site = false;
            }
        }


        if($site) {
            $siteKey = "site_" . $site->getId();
        }
        else {
            $siteKey = "default";
        }

        return $siteKey;
    }

    /**
     * @param Site $site
     * @param Document $document
     * @return bool
     */
    public static function isDocumentInSite ($site, $document) {
        $inSite = true;

        if ($site && $site->getRootDocument() instanceof Document_Page) {
            if(!preg_match("@^" . $site->getRootDocument()->getRealFullPath() . "@", $document->getRealFullPath())) {
                $inSite = false;
            }
        }

        return $inSite;
    }

    /**
     * @param Document $document
     * @return bool
     */
    public static function isDocumentInCurrentSite($document) {

        if(Site::isSiteRequest()) {
            $site = Site::getCurrentSite();
            if($site instanceof Site) {
                return self::isDocumentInSite($site, $document);
            }
        }

        return true;
    }

    /**
     * @param Document $document
     */
    public static function getSiteForDocument($document) {
        $sites = new Site_List();
        $sites = $sites->load();

        foreach ($sites as $site) {
            if(preg_match("@^" . $site->getRootPath() . "/@", $document->getRealFullPath()) || $site->getRootDocument()->getId() == $document->getId()) {
                return $site;
            }
        }

        return;
    }
}
