<?php

namespace AppBundle\Controller;

use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Bundle\PimcoreBundle\Controller\DocumentAwareInterface;
use Pimcore\Bundle\PimcoreBundle\Controller\Traits\DocumentAwareTrait;
use Pimcore\Bundle\PimcoreBundle\View\ZendViewHelperBridge;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TestController extends Controller implements DocumentAwareInterface
{
    use DocumentAwareTrait;

    /**
     * @Route("/test/test")
     * @param Request $request
     * @return array
     */
    public function testAction(Request $request)
    {



        exit;
    }
}
