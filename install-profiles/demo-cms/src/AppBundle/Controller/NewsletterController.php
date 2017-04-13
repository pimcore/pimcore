<?php

namespace AppBundle\Controller;

use Pimcore\Model;
use Pimcore\Tool\Newsletter;
use Symfony\Component\HttpFoundation\Request;

class NewsletterController extends AbstractController
{
    public function subscribeAction(Request $request)
    {
        $newsletter = new Newsletter('person'); // replace "crm" with the class name you have used for your class above (mailing list)
        $params = $request->request->all();

        $this->view->success = false;

        if ($newsletter->checkParams($params)) {
            try {
                $params['parentId'] = 1; // default folder (home) where we want to save our subscribers
                $newsletterFolder = Model\Object\AbstractObject::getByPath('/crm/newsletter');
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

                $this->view->success = true;
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    public function confirmAction(Request $request)
    {
        $this->view->success = false;

        $newsletter = new Newsletter('person'); // replace "crm" with the class name you have used for your class above (mailing list)

        if ($newsletter->confirm($request->get('token'))) {
            $this->view->success = true;
        }
    }

    public function unsubscribeAction(Request $request)
    {
        $newsletter = new Newsletter('person'); // replace "crm" with the class name you have used for your class above (mailing list)

        $unsubscribeMethod = null;
        $success = false;

        if ($request->get('email')) {
            $unsubscribeMethod = 'email';
            $success = $newsletter->unsubscribeByEmail($request->get('email'));
        }

        if ($request->get('token')) {
            $unsubscribeMethod = 'token';
            $success = $newsletter->unsubscribeByToken($request->get('token'));
        }

        $this->view->success = $success;
        $this->view->unsubscribeMethod = $unsubscribeMethod;
    }

    public function standardMailAction()
    {
    }
}
