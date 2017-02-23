<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;

class BernieController extends AbstractController
{
    public function testAction(Request $request)
    {


        echo $this->get("translator")->trans("mytest");




//        $document = Document::getById(24);
//
//
//        $content = Document\Service::render($document, [], false);
//
//        echo $content;
//        p_r($content);


        exit;
    }

    public function viewAction() {

//        $translator = \Pimcore::getContainer()->get("pimcore.translator");
//
//        echo $translator->trans("add_to_cart");


    }
}
