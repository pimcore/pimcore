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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Controller_Plugin_ImageLoader extends Zend_Controller_Plugin_Abstract {

    protected static $done = false;

    public function dispatchLoopShutdown() {

        if(self::$done) {
            return;
        }

        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        

        // analytics
        $body = $this->getResponse()->getBody();

        $body = preg_replace_callback("/<img ([^>]+)?src=\"([^\"]+)\"([^>]+)?>/i", function ($matches) {
            $img = $matches[0];
            $original = $img;
            $src = $matches[2];

            $srcPath = PIMCORE_DOCUMENT_ROOT . $src;

            if(file_exists($srcPath)) {
                $img = str_replace('src="'. $src .'"', 'src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="', $img);
                $img = preg_replace("@([ ]+)?/?>@", "", $img);
                $img .= ' data-src="' . $src . '"';

                if(!stripos($img, "width") || !stripos($img, "height")) {
                    list($width, $height) = getimagesize($srcPath);
                    $img .= ' width="' . $width . '" height="' . $height . '"';
                }

                $img .= ">";
            }

            return $img;
        }, $body);

        $this->getResponse()->setBody($body);

        self::$done = true;
    }
}
