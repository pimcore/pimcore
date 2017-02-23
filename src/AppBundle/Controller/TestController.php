<?php

namespace AppBundle\Controller;

use Pimcore\Model\Asset;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller
{
    /**
     * @Route("/test/test", name="test_test")
     */
    public function testAction(Request $request)
    {

        $foo = new \Pimcore\Translate("en");

        $content = $request->getLocale() . " | ";

        $content .= $this->get("translator")->trans("categories");
        $content .= " | ";
        $content .= $this->get("translator")->trans("categories", [], "admin");
        $content .= " | ";
        $content .= $this->get("translator")->trans("archive");
        $content .= " | ";
        $content .= $this->get("translator")->trans("check me out");
        $content .= " | ";
        $content .= $this->get("translator")->trans("content page");
        $content .= " | ";
        $content .= $this->get("translator")->trans("Meow");


        return new Response($content);
    }
}
