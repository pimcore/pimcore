<?php

namespace AppBundle\Controller;

use AppBundle\Form\Newsletter\SubscriptionFormType;
use AppBundle\Form\Newsletter\UnsubscriptionFormType;
use Pimcore\Controller\FrontendController;
use Pimcore\Model;
use Pimcore\Tool\Newsletter;
use Symfony\Component\HttpFoundation\Request;

class NewsletterController extends FrontendController
{
    public function subscribeAction(Request $request)
    {
        // replace "person" with the class name you have used for your class above (mailing list)
        $newsletter = new Newsletter('person');
        $success    = false;

        // initialize form and handle request data
        $form = $this->createForm(SubscriptionFormType::class);
        $form->handleRequest($request);

        $this->view->submitted = $form->isSubmitted();

        if ($form->isSubmitted() && $form->isValid()) {
            $params = $form->getData();

            if ($newsletter->checkParams($params)) {
                try {
                    $params['parentId'] = 1; // default folder (home) where we want to save our subscribers
                    $newsletterFolder   = Model\DataObject\AbstractObject::getByPath('/crm/newsletter');
                    if ($newsletterFolder) {
                        $params['parentId'] = $newsletterFolder->getId();
                    }

                    $user = $newsletter->subscribe($params);

                    // user and email document
                    // parameters available in the email: gender, firstname, lastname, email, token, object
                    // ==> see mailing framework
                    $newsletter->sendConfirmationMail($user, Model\Document::getByPath('/en/advanced-examples/newsletter/confirmation-email'), ['additional' => 'parameters']);

                    // do some other stuff with the new user
                    $user->setDateRegister(new \DateTime());
                    $user->save();

                    $success = true;
                } catch (\Exception $e) {
                    $this->view->error = $e->getMessage();
                }
            }
        }

        $this->view->success = $success;

        // add the form view
        $this->view->form = $form->createView();
    }

    public function confirmAction(Request $request)
    {
        $this->view->success = false;

        // replace "person" with the class name you have used for your class above (mailing list)
        $newsletter = new Newsletter('person');

        if ($newsletter->confirm($request->get('token'))) {
            $this->view->success = true;
        }
    }

    public function unsubscribeAction(Request $request)
    {
        // replace "person" with the class name you have used for your class above (mailing list)
        $newsletter = new Newsletter('person');

        // initialize form and handle request data
        $form = $this->createForm(UnsubscriptionFormType::class);
        $form->handleRequest($request);

        $formData = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        $unsubscribeMethod = null;
        $success = false;

        // read email and token from request with fallback to form data
        $email = $request->get('email', isset($formData['email']) ? $formData['email'] : null);
        $token = $request->get('token', isset($formData['token']) ? $formData['token'] : null);

        // we get the params here directly from the request as the action does not need
        // to be called through the form (e.g. link in email)
        if (null !== $email) {
            $unsubscribeMethod = 'email';
            $success = $newsletter->unsubscribeByEmail($email);
        } elseif (null !== $token) {
            $unsubscribeMethod = 'token';
            $success = $newsletter->unsubscribeByToken($token);
        }

        $this->view->success = $success;
        $this->view->unsubscribeMethod = $unsubscribeMethod;

        // add the form view
        $this->view->form = $form->createView();
    }

    public function standardMailAction()
    {
    }
}
