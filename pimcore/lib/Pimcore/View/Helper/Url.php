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

namespace Pimcore\View\Helper;

use Pimcore\Config; 
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;

class Url extends \Zend_View_Helper_Url {

    /**
     * @param array $urlOptions
     * @param null $name
     * @param bool $reset
     * @param bool $encode
     * @return string|void
     * @throws \Exception
     */
    public function url(array $urlOptions = array(), $name = null, $reset = false, $encode = true)
    {
        if(!$urlOptions) {
            $urlOptions = array();
        }

        if(!$name) {
            if(Staticroute::getCurrentRoute() instanceof Staticroute) {
                $name = Staticroute::getCurrentRoute()->getName();
            }
        }

        $siteId = null;
        if(Site::isSiteRequest()) {
            $siteId = Site::getCurrentSite()->getId();
        }

        // check for a site in the options, if valid remove it from the options
        $hostname = null;
        if(isset($urlOptions["site"])) {
            $config = Config::getSystemConfig();
            $site = $urlOptions["site"];
            if(!empty($site)) {
                try {
                    $site = Site::getBy($site);
                    unset($urlOptions["site"]);
                    $hostname = $site->getMainDomain();
                    $siteId = $site->getId();
                } catch (\Exception $e) {
                    \Logger::warn("passed site doesn't exists");
                    \Logger::warn($e);
                }
            } else if ($config->general->domain) {
                $hostname = $config->general->domain;
            }
        }

        if($name && $route = Staticroute::getByName($name, $siteId)) {

            // assemble the route / url in Staticroute::assemble()
            $url = $route->assemble($urlOptions, $reset, $encode);

            // if there's a site, prepend the host to the generated URL
            if($hostname && !preg_match("/^http/i", $url)) {
                $url = "//" . $hostname . $url;
            }

            if(Config::getSystemConfig()->documents->allowcapitals == 'no'){
                $urlParts = parse_url($url);
                $url = str_replace($urlParts["path"], strtolower($urlParts["path"]), $url);
            }
            return $url;
        }


        // this is to add support for arrays as values for the default \Zend_View_Helper_Url
        $unset = array(); 
        foreach ($urlOptions as $optionName => $optionValues) {
            if (is_array($optionValues)) {
                foreach ($optionValues as $key => $value) {
                    $urlOptions[$optionName . "[" . $key . "]"] = $value;
                }
                $unset[] = $optionName;
            }
        }
        foreach ($unset as $optionName) {
            unset($urlOptions[$optionName]);
        }

        
        try {
            return parent::url($urlOptions, $name, $reset, $encode);
        } catch (\Exception $e) {
            throw new \Exception("Route '".$name."' for building the URL not found");
        }
    }    
}
