<?php

namespace AppBundle\Controller;

use Pimcore\Model\DataObject\Service;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IFrameController extends \Pimcore\Controller\FrontendController
{

    /**
     * @Route("/iframe/summary")
     *
     * @param Request $request
     */
    public function summaryAction(Request $request) {
        $context = json_decode($request->get("context"), true);
        $objectId = $context["objectId"];
        $language = $context["language"];

        $object = Service::getObjectFromSession($objectId);

        $response =  '<h1>Title for language "' . $language . '": '  . $object->getTitle($language) . "</h1>";

        $response .= '<h2>Context</h2>';
        $response .= array_to_html_attribute_string($context);
        return new Response($response);
    }
}
