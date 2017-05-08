<?php

namespace AppBundle\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pimcore\Controller\Configuration\ResponseHeader;

class ContentController extends FrontendController
{
    public function defaultAction()
    {
    }

    /**
     * The annotations below demonstrate the ResponseHeader annotation which can be
     * used to set custom response headers on the auto-rendered response. At this point, the headers
     * are not really set as we don't have a response yet, but they will be added to the final response
     * by the ResponseHeaderListener.
     *
     * @ResponseHeader("X-Custom-Header", values={"Foo", "Bar"})
     * @ResponseHeader("X-Custom-Header2", values="Bazinga", replace=true)
     */
    public function portalAction()
    {
        // you can also set the header via code
        $this->addResponseHeader('X-Custom-Header3', ['foo', 'bar']);

        $this->view->isPortal = true;
    }

    public function thumbnailsAction()
    {
    }

    public function websiteTranslationsAction()
    {
    }

    public function editableRoundupAction()
    {
    }

    public function simpleFormAction(Request $request)
    {
        $success = false;

        // getting parameters is very easy ... just call $request->get("yourParamKey"); regardless if's POST or GET
        if ($request->get('firstname') && $request->get('lastname') && $request->get('email')) {
            $success = true;

            // of course you can store the data here into an object, or send a mail, ... do whatever you want or need
            // ...
            // ...
        }

        // do some validation & assign the parameters to the view
        foreach (['firstname', 'lastname', 'email'] as $key) {
            if ($request->get($key)) {
                $this->view->$key = htmlentities(strip_tags($request->get($key)));
            }
        }

        // assign the status to the view
        $this->view->success = $success;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function galleryRenderletAction(Request $request)
    {
        if ($request->get('id') && $request->get('type') === 'asset') {
            $this->view->asset = Asset::getById($request->get('id'));
        }
    }
}
