<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Model\Document;

class FashController extends AbstractController
{
    public function testAction()
    {


        $this->translate("mytest");


//        $document = Document::getById(24);
//
//
//        $content = Document\Service::render($document, [], false);
//
//        echo $content;
//        p_r($content);




        die("done");
    }

    public function viewAction() {

//        $translator = \Pimcore::getContainer()->get("pimcore.translator");
//
//        echo $translator->trans("add_to_cart");


    }
}
