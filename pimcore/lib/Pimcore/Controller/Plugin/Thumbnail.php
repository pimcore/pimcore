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

class Pimcore_Controller_Plugin_Thumbnail extends Zend_Controller_Plugin_Abstract {

    /**
     * @param Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request) {

        // this is a filter which checks for common used files (by browser, crawlers, ...) and prevent the default
        // error page, because this is more resource-intensive than exiting right here
        if(preg_match("@^/website/var/tmp/thumb_([0-9]+)__([a-zA-Z0-9_\-]+)@",$request->getPathInfo(),$matches)) {


            exit;
        }
    }
}
