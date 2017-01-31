<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ZendViewController extends Controller
{
    /**
     * @Route("/zend-view", name="zend-view")
     */
    public function zendViewAction()
    {
        return $this->render('AppBundle:ZendView:test.phtml', [
            '_layout' => 'AppBundle:ZendView:layout.phtml',
            'foo' => 'bar'
        ]);
    }
}
