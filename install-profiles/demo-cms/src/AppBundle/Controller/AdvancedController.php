<?php

namespace AppBundle\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Mail;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;
use Zend\Paginator\Paginator;

class AdvancedController extends FrontendController
{
    public function indexAction()
    {
        $list = new Document\Listing();
        $list->setCondition("parentId = ? AND type IN ('link','page')", [$this->document->getId()]);
        $list->load();

        $this->view->documents = $list;
    }

    public function contactFormAction(Request $request)
    {
        $success = false;

        if ($request->get('provider')) {
            $adapter = Tool\HybridAuth::authenticate($request->get('provider'));
            if ($adapter) {
                $user_data = $adapter->getUserProfile();
                if ($user_data) {
                    $request->request->set('firstname', $user_data->firstName);
                    $request->request->set('lastname', $user_data->lastName);
                    $request->request->set('email', $user_data->email);
                    $request->request->set('gender', $user_data->gender);
                }
            }
        }

        // getting parameters is very easy ... just call $request->get("yorParamKey"); regardless if's POST or GET
        if ($request->get('firstname') && $request->get('lastname') && $request->get('email') && $request->get('message')) {
            $success = true;

            $mail = new Mail();
            $mail->setIgnoreDebugMode(true);

            // To is used from the email document, but can also be set manually here (same for subject, CC, BCC, ...)
            //$mail->addTo("info@pimcore.org");

            $emailDocument = $this->document->getProperty('email');
            if (!$emailDocument) {
                $emailDocument = Document::getById(38);
            }

            $mail->setDocument($emailDocument);
            $mail->setParams($request->request->all());
            $mail->send();
        }

        // do some validation & assign the parameters to the view
        foreach (['firstname', 'lastname', 'email', 'message', 'gender'] as $key) {
            if ($request->get($key)) {
                $this->view->$key = htmlentities(strip_tags($request->get($key)));
            }
        }

        // assign the status to the view
        $this->view->success = $success;
    }

    public function searchAction(Request $request)
    {
        if ($request->get('q')) {
            try {
                $page = $request->get('page');
                if (empty($page)) {
                    $page = 1;
                }
                $perPage = 10;

                $result = \Pimcore\Google\Cse::search($request->get('q'), (($page - 1) * $perPage), null, [
                    'cx' => '002859715628130885299:baocppu9mii'
                ], $request->get('facet'));

                $paginator = new Paginator($result);
                $paginator->setCurrentPageNumber($page);
                $paginator->setItemCountPerPage($perPage);
                $this->view->paginator = $paginator;
                $this->view->result = $result;
            } catch (\Exception $e) {
                // something went wrong: eg. limit exceeded, wrong configuration, ...
                \Pimcore\Logger::err($e);
                echo $e->getMessage();
                exit;
            }
        }
    }

    public function objectFormAction(Request $request)
    {
        $success = false;

        // getting parameters is very easy ... just call $request->get("yorParamKey"); regardless if's POST or GET
        if ($request->get('firstname') && $request->get('lastname') && $request->get('email') && $request->get('terms')) {
            $success = true;

            // for this example the class "person" and "inquiry" is used
            // first we create a person, then we create an inquiry object and link them together

            // check for an existing person with this name
            $person = Object\Person::getByEmail($request->get('email'), 1);

            if (!$person) {
                // if there isn't an existing, ... create one
                $filename = \Pimcore\File::getValidFilename($request->get('email'));

                // first we need to create a new object, and fill some system-related information
                $person = new Object\Person();
                $person->setParent(Object\AbstractObject::getByPath('/crm/inquiries')); // we store all objects in /crm
                $person->setKey($filename); // the filename of the object
                $person->setPublished(true); // yep, it should be published :)

                // of course this needs some validation here in production...
                $person->setGender($request->get('gender'));
                $person->setFirstname($request->get('firstname'));
                $person->setLastname($request->get('lastname'));
                $person->setEmail($request->get('email'));
                $person->setDateRegister(new \DateTime());
                $person->save();
            }

            // now we create the inquiry object and link the person in it
            $inquiryFilename = \Pimcore\File::getValidFilename(date('Y-m-d') . '~' . $person->getEmail());
            $inquiry = new Object\Inquiry();
            $inquiry->setParent(Object\AbstractObject::getByPath('/inquiries')); // we store all objects in /inquiries
            $inquiry->setKey($inquiryFilename); // the filename of the object
            $inquiry->setPublished(true); // yep, it should be published :)

            // now we fill in the data
            $inquiry->setMessage($request->get('message'));
            $inquiry->setPerson($person);
            $inquiry->setDate(new \DateTime());
            $inquiry->setTerms((bool) $request->get('terms'));
            $inquiry->save();
        } elseif ($request->isMethod('POST')) {
            $this->view->error = true;
        }

        // do some validation & assign the parameters to the view
        foreach (['firstname', 'lastname', 'email', 'message', 'terms'] as $key) {
            if ($request->get($key)) {
                $this->view->$key = htmlentities(strip_tags($request->get($key)));
            }
        }

        // assign the status to the view
        $this->view->success = $success;
    }

    public function sitemapAction(Request $request)
    {
        $this->view->doc = $this->document->getProperty('mainNavStartNode');
    }

    public function sitemapPartialAction(Request $request)
    {
        set_time_limit(900);

        $this->view->initial = false;

        if ($request->get('doc')) {
            $this->view->doc = $request->get('doc');
        }

        \Pimcore::collectGarbage();
    }

    public function assetThumbnailListAction()
    {

        // try to get the tag where the parent folder is specified
        $parentFolder = $this->document->getElement('parentFolder');
        if ($parentFolder) {
            $parentFolder = $parentFolder->getElement();
        }

        if (!$parentFolder) {
            // default is the home folder
            $parentFolder = Asset::getById(1);
        }

        // get all children of the parent
        $list = new Asset\Listing();
        $list->setCondition('path like ?', $parentFolder->getFullpath() . '%');

        $this->view->list = $list;
    }
}
