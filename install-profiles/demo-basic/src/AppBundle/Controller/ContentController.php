<?php

namespace AppBundle\Controller;

use Pimcore\Controller\Configuration\ResponseHeader;
use Pimcore\Controller\FrontendController;
use Pimcore\Model\Asset;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        // we directly use the form builder here, but it's recommended to create a dedicated
        // form type class for your forms (see forms on advanced examples)
        // see http://symfony.com/doc/current/forms.html

        /** @var Form $form */
        $form = $this->createFormBuilder()
            ->add('firstname', TextType::class, [
                'label'       => 'Firstname',
                'required'    => true
            ])
            ->add('lastname', TextType::class, [
                'label'    => 'Lastname',
                'required' => true
            ])
            ->add('email', EmailType::class, [
                'label'    => 'E-Mail',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'example@example.com'
                ]
            ])
            ->add('checkbox', CheckboxType::class, [
                'label' => 'Check me out'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit'
            ])
            ->getForm();

        $form->handleRequest($request);

        $success = false;
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $success = true;

                // we just assign the data to the view for now
                // of course you can store the data here into an object, or send a mail, ... do whatever you want or need
                $this->view->getParameters()->add($form->getData());
            }
        }

        // add success state and form view to the view
        $this->view->success = $success;
        $this->view->form    = $form->createView();
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
