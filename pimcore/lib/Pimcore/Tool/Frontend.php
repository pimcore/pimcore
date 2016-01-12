<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Tool;

use Pimcore\Model\Document;
use Pimcore\Model\Site;

class Frontend {

    /**
     * Returns the Website-Config
     * @return \Zend_Config
     * @depricated
     */
    public static function getWebsiteConfig () {
        return \Pimcore\Config::getWebsiteConfig();
    }

    /**
     * @param Site $site
     * @return string
     * @throws \Exception
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

        if ($site && $site->getRootDocument() instanceof Document\Page) {
            if(!preg_match("@^" . $site->getRootDocument()->getRealFullPath() . "/@", $document->getRealFullPath())) {
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

        $cacheKey = "sites_full_list";
        if(\Zend_Registry::isRegistered($cacheKey)) {
            $sites = \Zend_Registry::get($cacheKey);
        } else {
            $sites = new Site\Listing();
            $sites = $sites->load();
            \Zend_Registry::set($cacheKey, $sites);
        }

        foreach ($sites as $site) {
            if(preg_match("@^" . $site->getRootPath() . "/@", $document->getRealFullPath()) || $site->getRootDocument()->getId() == $document->getId()) {
                return $site;
            }
        }

        return;
    }

    /**
     * @return array|bool
     */
    public static function isOutputCacheEnabled() {
        $front = \Zend_Controller_Front::getInstance();
        $cachePlugin = $front->getPlugin("Pimcore\\Controller\\Plugin\\Cache");
        if($cachePlugin && $cachePlugin->isEnabled()) {
            return array(
                "enabled" => true,
                "lifetime" => $cachePlugin->getLifetime()
            );
        }
        return false;
    }

    /**
     * @var null|string
     */
    protected static $currentRequestUrlCrc32;

    /**
     * @return string
     */
    public static function getCurrentRequestUrlCrc32() {
        if(!self::$currentRequestUrlCrc32) {
            if(php_sapi_name() != "cli") {
                $front = \Zend_Controller_Front::getInstance();
                $request = $front->getRequest();
                self::$currentRequestUrlCrc32 = crc32($request->getHttpHost() . $request->getRequestUri());
            }
        }

        return self::$currentRequestUrlCrc32;
    }

    /**
     * @param $content
     * @param $id
     * @return string
     */
    public static function addComponentIdToHtml($content, $id) {

        if(\Pimcore\View::addComponentIds()) {
            // generate a crc of the current URL and cache it
            $crc = self::getCurrentRequestUrlCrc32();
            if ($crc) {
                $id = "uri:" . $crc . "." . $id;
            }

            // well the regex here is not the perfect solution, but it should work for most cases and is much faster than
            // using a HTML/XML parser or simple_dom_html, as this is not a critical information, the regex is fine here
            $content = preg_replace("@<([a-z]+)([^>]*)(?<!\/)>@", '<$1$2 data-component-id="' . $id . '">', $content, 1);
        }

        return $content;
    }
}
