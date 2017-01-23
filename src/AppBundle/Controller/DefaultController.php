<?php

namespace AppBundle\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zend\Diactoros\Response\JsonResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/foobar", name="test")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function foobarAction(Request $request)
    {
        return new Response('foobar');
    }

    /**
     * @Route("/psr7", name="PSR-7")
     *
     * @param ServerRequestInterface $request
     *
     * @return JsonResponse
     */
    public function psr7Action(ServerRequestInterface $request)
    {
        return new JsonResponse([
            'psr-7' => true
        ]);
    }
}
