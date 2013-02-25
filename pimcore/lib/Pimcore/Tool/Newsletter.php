<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Tool_Newsletter {

    /**
     * @var Object_Class
     */
    protected $class;

    /**
     * @param int|string $class
     */
    public function __construct($classId = null) {

        $class = null;
        if(is_string($classId)) {
            $class = Object_Class::getByName($classId);
        } else if (is_int($classId)) {
            $class = Object_Class::getById($classId);
        } else if ($classId !== null) {
            throw new \Exception("No valid class identifier given (class name or ID)");
        }

        if($class instanceof Object_Class) {
            $this->setClass($class);
        }
    }

    /**
     * @return string
     */
    protected function getClassName() {
        return "Object_" . ucfirst($this->getClass()->getName());
    }

    /**
     * @param array $params
     * @return bool
     */
    public function checkParams ($params) {

        if(!array_key_exists("email", $params)) {
            return false;
        }

        if(strlen($params["email"]) < 6 || !strpos($params["email"], "@") || !strpos($params["email"], ".")) {
            return false;
        }

        return true;
    }

    /**
     * @param $params
     * @return null|Object_Concrete
     * @throws Exception
     */
    public function subscribe ($params) {

        $className = $this->getClassName();
        $object = new $className;

        // check for existing e-mail
        $existingObject = $className::getByEmail($params["email"], 1);
        if($existingObject) {
            throw new \Exception("email address '" . $params["email"] . "' already exists");
        }

        if(!array_key_exists("email", $params)) {
            throw new \Exception("key 'email' is a mandatory parameter");
        }

        $object->setValues($params);

        if(!$object->getParentId()) {
            $object->setParentId(1);
        }

        $object->setNewsletterActive(true);
        $object->setCreationDate(time());
        $object->setModificationDate(time());
        $object->setUserModification(0);
        $object->setUserOwner(0);
        $object->setPublished(true);
        $object->setKey(str_replace("@", "~", $object->getEmail()));
        $object->save();

        // generate token
        $token = base64_encode(serialize(array(
            "salt" => md5(microtime()),
            "email" => $object->getEmail(),
            "id" => $object->getId()
        )));
        $object->setProperty("token", "text", $token);
        $object->save();

        $this->addNoteOnObject($object, "subscribe");

        return $object;
    }

    /**
     * @param Object_Concrete $object
     * @param Document_Email $mailDocument
     */
    public function sendConfirmationMail($object, $mailDocument) {

        $params = array(
            "gender" => $object->getGender(),
            'firstname' => $object->getFirstname(),
            'lastname' => $object->getLastname(),
            "email" => $object->getEmail(),
            'token' => $object->getProperty("token"),
            "object" => $object
        );

        $mail = new Pimcore_Mail();
        $mail->addTo($object->getEmail());
        $mail->setDocument($mailDocument);
        $mail->setParams($params);
        $mail->send();
    }

    /**
     * @param string $token
     * @return Object_Contrete
     */
    public function getObjectByToken($token) {
        $data = unserialize(base64_decode($token));
        if($data) {
            if($object = Object_Abstract::getById($data["id"])) {
                if($object->getProperty("token") == $token) {
                    if($object->getEmail() == $data["email"]) {
                        return $object;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param string $token
     * @return bool
     */
    public function confirm($token) {

        $object = $this->getObjectByToken($token);
        if($object) {
            $object->setNewsletterConfirmed(true);
            $object->save();

            $this->addNoteOnObject($object, "confirm");

            return true;
        }

        return false;
    }

    /**
     * @param string $token
     * @return bool
     */
    public function unsubscribeByToken ($token) {

        $object = $this->getObjectByToken($token);
        if($object) {
            return $this->unsubscribe($object);
        }

        return false;
    }

    /**
     * @param string $email
     * @return bool
     */
    public function unsubscribeByEmail($email) {

        $className = $this->getClassName();
        $objects = $className::getByEmail($email);
        if($objects) {
            foreach($objects as $object) {
                $this->unsubscribe($object);
            }
            return true;
        }

        return false;
    }


    /**
     * @param Object_Concrete $object
     * @return bool
     */
    public function unsubscribe($object) {
        if($object) {
            $object->setNewsletterActive(false);
            $object->save();

            $this->addNoteOnObject($object, "unsubscribe");

            return true;
        }
        return false;
    }

    /**
     * @param Object_Concrete $object
     * @param string $title
     */
    protected function addNoteOnObject($object, $title) {
        $note = new Element_Note();
        $note->setElement($object);
        $note->setDate(time());
        $note->setType("newsletter");
        $note->setTitle($title);
        $note->setUser(0);
        $note->setData(array(
            "ip" => array(
                "type" => "text",
                "data" => Pimcore_Tool::getClientIp()
            )
        ));
        $note->save();
    }

    /**
     * @param \Object_Class $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return \Object_Class
     */
    public function getClass()
    {
        return $this->class;
    }


}