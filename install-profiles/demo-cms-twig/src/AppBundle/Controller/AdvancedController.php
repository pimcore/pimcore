<?php

namespace AppBundle\Controller;

use AppBundle\Form\ContactFormType;
use AppBundle\Form\PersonInquiryType;
use Pimcore\File;
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

        // initialize form and handle request data
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        // handle social login and pre-fill form
        if (!$form->isSubmitted()) {
            if ($request->get('provider')) {
                /** @var \Hybrid_Provider_Adapter|\Hybrid_Provider_Model $adapter */
                $adapter = Tool\HybridAuth::authenticate($request->get('provider'));
                if ($adapter) {
                    $userData = $adapter->getUserProfile();
                    if ($userData) {
                        $form->setData([
                            'gender'    => $userData->gender,
                            'firstname' => $userData->firstName,
                            'lastname'  => $userData->lastName,
                            'email'     => $userData->email
                        ]);
                    }
                }
            }
        } else {
            if ($form->isValid()) {
                $success = true;

                $data = $form->getData();

                $mail = new Mail();
                $mail->setIgnoreDebugMode(true);

                // To is used from the email document, but can also be set manually here (same for subject, CC, BCC, ...)
                //$mail->addTo("info@pimcore.org");

                $emailDocument = $this->document->getProperty('email');
                if (!$emailDocument) {
                    $emailDocument = Document::getById(38);
                }

                $mail->setDocument($emailDocument);
                $mail->setParams($data);
                $mail->send();

                // add form data as view parameters
                $this->view->getParameters()->add($data);
            }
        }

        $this->view->success = $success;

        // add the form view
        $this->view->form = $form->createView();
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
        // we created a dedicated PersonInquiryType form class which defines
        // the form fields and validation rules used by our form
        // have a look at the template to check how this form type is rendered
        $form = $this->createForm(PersonInquiryType::class);
        $form->handleRequest($request);

        $this->view->success   = false;
        $this->view->error     = false;
        $this->view->submitted = $form->isSubmitted();

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->view->success = true;

                // validated form data
                $data = $form->getData();

                // for this example the class "person" and "inquiry" is used
                // first we create a person, then we create an inquiry object and link them together

                // check for an existing person with this name
                $person = Object\Person::getByEmail($data['email'], 1);

                if (!$person) {
                    // if there isn't an existing, ... create one
                    $filename = File::getValidFilename($data['email']);

                    // first we need to create a new object, and fill some system-related information
                    $person = new Object\Person();
                    $person->setParent(Object\AbstractObject::getByPath('/crm/inquiries')); // we store all objects in /crm
                    $person->setKey($filename); // the filename of the object
                    $person->setPublished(true); // yep, it should be published :)

                    // of course this needs some validation here in production...
                    $person->setGender($data['gender']);
                    $person->setFirstname($data['firstname']);
                    $person->setLastname($data['lastname']);
                    $person->setEmail($data['email']);
                    $person->setDateRegister(new \DateTime());
                    $person->save();
                }

                // now we create the inquiry object and link the person
                $inquiryFilename = File::getValidFilename(date('Y-m-d') . '~' . $person->getEmail());

                $inquiry = new Object\Inquiry();
                $inquiry->setParent(Object\AbstractObject::getByPath('/inquiries')); // we store all objects in /inquiries
                $inquiry->setKey($inquiryFilename); // the filename of the object
                $inquiry->setPublished(true); // yep, it should be published :)

                // now we fill in the data
                $inquiry->setMessage($data['message']);
                $inquiry->setPerson($person);
                $inquiry->setDate(new \DateTime());
                $inquiry->setTerms((bool)$data['terms']);
                $inquiry->save();

                // add form data as view parameters
                $this->view->getParameters()->add($data);
            } else {
                $this->view->error = true;
            }
        }

        // add the form view
        $this->view->form = $form->createView();
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
