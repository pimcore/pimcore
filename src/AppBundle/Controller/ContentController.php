<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ContentController extends Controller
{
    /**
     * @Template("AppBundle:Content:php.html.php", engine="php")
     *
     * @param Request $request
     * @return array
     */
    public function phpAction(Request $request)
    {
        return $this->resolveContent($request);
    }

    /**
     * @Template("AppBundle:Content:twig.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function twigAction(Request $request)
    {
        return $this->resolveContent($request);
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function resolveContent(Request $request)
    {
        $document = $request->get('contentDocument');

        if ($request->get('debugDocument')) {
            dump($document);
        }

        return [
            'document' => $document
        ];
    }
}
