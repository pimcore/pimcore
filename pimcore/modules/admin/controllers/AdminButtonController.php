<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license dsf sdaf asdf asdf
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\Image\HtmlToImage;
use Pimcore\Model;

class Admin_AdminButtonController extends \Pimcore\Controller\Action\Admin {

    public function init() {
        if($this->getParam("action") != "script") {
            parent::init();
        }
    }

    public function scriptAction() {
        // this is just to ensure that the script is only embedded if the user is logged in

        // check the login manually
        $user = \Pimcore\Tool\Authentication::authenticateSession();
        if($user instanceof Model\User) {

            $personas = array();
            $list = new Model\Tool\Targeting\Persona\Listing();
            foreach($list->load() as $persona) {
                $personas[$persona->getId()] = $persona->getName();
            }

            header("Content-Type: text/javascript");

            echo 'try {
                var pimcore = pimcore || {};
                pimcore["admin"] = {documentId: ' . $this->getParam("documentId") . '};
                pimcore["personas"] = ' . \Zend_Json::encode($personas) .';
            } catch (e) {}';

            echo "\n\n\n";

            echo file_get_contents(PIMCORE_PATH . "/static/js/frontend/admin/admin.js");
        }

        exit;
    }

    public function featureRequestAction () {

        $type = "feature";
        $this->view->type = $type;

        $this->featureBug();
    }

    public function bugReportAction () {

        $type = "bug";
        $this->view->type = $type;

        $this->featureBug();
    }

    protected function featureBug() {
        $conf = \Pimcore\Config::getSystemConfig();
        $email = $conf->general->contactemail;
        $this->view->contactEmail = $email;

        if(!$this->getParam("submit")) {
            if(HtmlToImage::isSupported()) {
                $file = PIMCORE_TEMPORARY_DIRECTORY . "/screen-" . uniqid() . ".jpeg";
                HtmlToImage::convert($this->getParam("url"), $file, 1280, "jpeg");
                $this->view->image = str_replace(PIMCORE_DOCUMENT_ROOT, "", $file);
            }
        } else {
            // send the request
            $type = $this->view->type;
            $urlParts = parse_url($this->getParam("url"));
            $subject = "Feature Request for ";
            if($type == "bug") {
                $subject = "Bug Report for ";
            }

            $subject .=  $urlParts["host"];

            $mail = \Pimcore\Tool::getMail($email, $subject, "UTF-8");
            $mail->setIgnoreDebugMode(true);

            $bodyText = "URL: " . $this->getParam("url") . "\n\n";
            $bodyText .= "Description: \n\n" . $this->getParam("description");

            $image = null;
            if(HtmlToImage::isSupported()) {
                $markers = \Zend_Json::decode($this->getParam("markers"));

                $screenFile = PIMCORE_DOCUMENT_ROOT . $this->getParam("screenshot");

                list($width, $height) = getimagesize($screenFile);
                $im = imagecreatefromjpeg($screenFile);
                $font = PIMCORE_DOCUMENT_ROOT . "/pimcore/static/font/vera.ttf";
                $fontSize = 10;

                if($markers && count($markers) > 0) {
                    foreach ($markers as $marker) {
                        // set up array of points for polygon

                        $x = $marker["position"]["left"] * $width / 100;
                        $y = $marker["position"]["top"] * $height / 100;

                        $bbox = imagettfbbox($fontSize, 0, $font, $marker["text"]);

                        $textWidth = $bbox[4] + 10;

                        $values = array(
                            $x, $y,         // 1
                            $x-10, $y-10,   // 2
                            $x-10, $y-40,   // 3
                            $x+$textWidth, $y-40,  // 4
                            $x+$textWidth, $y-10,  // 5
                            $x+10, $y-10    // 6
                        );

                        $textcolor = imagecolorallocate($im, 255,255,255);
                        $bgcolor = imagecolorallocatealpha($im, 0,0,0,30);

                        // draw a polygon
                        imagefilledpolygon($im, $values, 6, $bgcolor);
                        imagettftext($im, $fontSize, 0, $x, $y-20, $textcolor, $font, $marker["text"]);
                    }
                }

                imagejpeg($im, $screenFile);
                imagedestroy($im);

                $image = file_get_contents($screenFile);
                unlink($screenFile);
            }

            if($image) {
                $bodyText .= "\n\n\nsee attached file: screen.jpg";

                $at = $mail->createAttachment($image);
                $at->type        = 'image/jpeg';
                $at->disposition = \Zend_Mime::DISPOSITION_ATTACHMENT;
                $at->encoding    = \Zend_Mime::ENCODING_BASE64;
                $at->filename    = 'screen.jpg';
            }

            if($type == "bug") {
                $bodyText .= "\n\n";
                $bodyText .= "Details: \n\n";

                foreach ($_SERVER as $key => $value) {
                    $bodyText .= $key . " => " . $value . "\n";
                }
            }

            $mail->setBodyText($bodyText);
            $mail->send();
        }

        $this->renderScript("/admin-button/feature-bug.php");
    }

    public function personaAction() {

        $list = new Model\Tool\Targeting\Persona\Listing();
        $list->setCondition("active = 1");
        $this->view->personas = $list->load();
    }
}
