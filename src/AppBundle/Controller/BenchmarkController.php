<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Website\Benchmark\Article;

/**
 * @Route("/benchmark")
 * @Method("GET")
 */
class BenchmarkController
{
    /**
     * @Route("/symfony-json")
     */
    public function jsonAction()
    {
        return $this->prepareResponse([
            'hello'       => 'world',
            'symfonyMode' => 'nativeSymfony'
        ]);
    }

    /**
     * @Route("/symfony-json-object")
     */
    public function jsonObjectAction()
    {
        return $this->prepareResponse(Article::getBenchmarkData(40));
    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    protected function prepareResponse(array $data)
    {
        $data['stack'] = 'symfony';

        return new JsonResponse($data);
    }
}
