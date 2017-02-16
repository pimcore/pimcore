<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Configuration\TemplatePhp;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IndexController extends Controller
{
    /**
     * @Route("/", name="admin_index")
     * @TemplatePhp()
     */
    public function indexAction()
    {
    }
}
