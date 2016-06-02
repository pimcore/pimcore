<?php

use Website\Controller\Action;
use Pimcore\Tool\Newsletter;
use Pimcore\Model;

class NewsletterController extends Action
{
    public function subscribeAction()
    {
        $this->enableLayout();

        $newsletter = new Newsletter("person"); // replace "crm" with the class name you have used for your class above (mailing list)
        $params = $this->getAllParams();

        $this->view->success = false;

        if ($newsletter->checkParams($params)) {
            try {
                $params["parentId"] = 1; // default folder (home) where we want to save our subscribers
                $newsletterFolder = Model\Object::getByPath("/crm/newsletter");
                if ($newsletterFolder) {
                    $params["parentId"] = $newsletterFolder->getId();
                }

                $user = $newsletter->subscribe($params);

                // user and email document
                // parameters available in the email: gender, firstname, lastname, email, token, object
                // ==> see mailing framework
                $newsletter->sendConfirmationMail($user, Model\Document::getByPath("/en/advanced-examples/newsletter/confirmation-email"), ["additional" => "parameters"]);

                // do some other stuff with the new user
                $user->setDateRegister(new \DateTime());
                $user->save();

                $this->view->success = true;
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    public function confirmAction()
    {
        $this->enableLayout();

        $this->view->success = false;

        $newsletter = new Newsletter("person"); // replace "crm" with the class name you have used for your class above (mailing list)

        if ($newsletter->confirm($this->getParam("token"))) {
            $this->view->success = true;
        }
    }

    public function unsubscribeAction()
    {
        $this->enableLayout();

        $newsletter = new Newsletter("person"); // replace "crm" with the class name you have used for your class above (mailing list)

        $unsubscribeMethod = null;
        $success = false;

        if ($this->getParam("email")) {
            $unsubscribeMethod = "email";
            $success = $newsletter->unsubscribeByEmail($this->getParam("email"));
        }

        if ($this->getParam("token")) {
            $unsubscribeMethod = "token";
            $success = $newsletter->unsubscribeByToken($this->getParam("token"));
        }

        $this->view->success = $success;
        $this->view->unsubscribeMethod = $unsubscribeMethod;
    }

    public function standardMailAction()
    {
    }
}
