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

        $foo = \Zend_Locale::getTranslation("en", "language");
        dump($foo); exit;

        $content = "Foo | Bar";

        return new Response($content);
    }
}
