<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Configuration\PhpTemplate;
use Pimcore\Bundle\PimcoreZendBundle\Controller\ZendController;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @PhpTemplate()
 */
class ContentController extends ZendController
{
    public function defaultAction()
    {
    }

    public function portalAction()
    {
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
     * TODO find out why the @PhpTemplate annotation is not properly working here! If not set, it will render with
     * the content/default template, if set it will sometimes complain because it found multiple annotations. Is there
     * an issue with sub-requests?
     *
     * @param Request $request
     * @return Response
     */
    public function galleryRenderletAction(Request $request)
    {
        if ($request->get('id') && $request->get('type') === 'asset') {
            $this->view->asset = Asset::getById($request->get('id'));
        }

        return $this->render('WebsiteDemoBundle:Content:galleryRenderlet.html.php', $this->view->getAllParameters());
    }
}
