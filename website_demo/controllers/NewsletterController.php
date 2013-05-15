<?php

class AdvancedController extends Website_Controller_Action
{
    public function indexAction() {
        $this->enableLayout();

        $list = new Document_List();
        $list->setCondition("parentId = ?", array($this->document->getId()));
        $list->load();

        $this->view->documents = $list;
    }


    public function objectFormAction() {
        $this->enableLayout();

        $success = false;

        // getting parameters is very easy ... just call $this->getParam("yorParamKey"); regardless if's POST or GET
        if($this->getParam("firstname") && $this->getParam("lastname") && $this->getParam("email") && $this->getParam("terms")) {
            $success = true;

            // for this example the class "person" and "inquiry" is used
            // first we create a person, then we create an inquiry object and link them together

            // check for an existing person with this name
            $person = Object_Person::getByEmail($this->getParam("email"),1);

            if(!$person) {
                // if there isn't an existing, ... create one
                $filename = Pimcore_File::getValidFilename($this->getParam("email"));

                // first we need to create a new object, and fill some system-related information
                $person = new Object_Person();
                $person->setParent(Object_Abstract::getByPath("/crm")); // we store all objects in /crm
                $person->setKey($filename); // the filename of the object
                $person->setPublished(true); // yep, it should be published :)

                // of course this needs some validation here in production...
                $person->setGender($this->getParam("gender"));
                $person->setFirstname($this->getParam("firstname"));
                $person->setLastname($this->getParam("lastname"));
                $person->setEmail($this->getParam("email"));
                $person->setDateRegister(Zend_Date::now());
                $person->save();
            }

            // now we create the inquiry object and link the person in it
            $inquiryFilename = Pimcore_File::getValidFilename(Zend_Date::now()->get(Zend_Date::DATETIME_MEDIUM) . "~" . $person->getEmail());
            $inquiry = new Object_Inquiry();
            $inquiry->setParent(Object_Abstract::getByPath("/inquiries")); // we store all objects in /inquiries
            $inquiry->setKey($inquiryFilename); // the filename of the object
            $inquiry->setPublished(true); // yep, it should be published :)

            // now we fill in the data
            $inquiry->setMessage($this->getParam("message"));
            $inquiry->setPerson($person);
            $inquiry->setDate(Zend_Date::now());
            $inquiry->setTerms((bool) $this->getParam("terms"));
            $inquiry->save();
        } else if ($this->getRequest()->isPost()) {
            $this->view->error = true;
        }

        // do some validation & assign the parameters to the view
        foreach (array("firstname", "lastname", "email", "message", "terms") as $key) {
            if($this->getParam($key)) {
                $this->view->$key = htmlentities(strip_tags($this->getParam($key)));
            }
        }

        // assign the status to the view
        $this->view->success = $success;
    }
}
