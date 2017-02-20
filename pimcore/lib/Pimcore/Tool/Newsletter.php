<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Pimcore\Document\Newsletter\SendingParamContainer;
use Pimcore\Mail;
use Pimcore\Tool;
use Pimcore\Model\Object;
use Pimcore\Model\Document;
use Pimcore\Model;
use Pimcore\Logger;

class Newsletter
{
    const SENDING_MODE_BATCH = "batch";
    const SENDING_MODE_SINGLE = "single";

    /**
     * @var Object\ClassDefinition
     */
    protected $class;

    /**
     * @param Document\Newsletter $newsletterDocument
     * @param SendingParamContainer|null $sendingContainer
     * @param string|null $hostUrl
     * @return Mail
     */
    public static function prepareMail(Document\Newsletter $newsletterDocument, SendingParamContainer $sendingContainer = null, $hostUrl = null)
    {
        $mail = new Mail();
        $mail->setIgnoreDebugMode(true);

        if (\Pimcore\Config::getSystemConfig()->newsletter->usespecific) {
            $mail->init("newsletter");
        }

        if (!Tool::getHostUrl() && $hostUrl) {
            $mail->setHostUrl($hostUrl);
        }

        $mail->setDocument($newsletterDocument);

        if ($sendingContainer && $sendingContainer->getParams()) {
            $mail->setParams($sendingContainer->getParams());
        }

        $contentHTML = $mail->getBodyHtmlRendered();
        $contentText = $mail->getBodyTextRendered();

        // render the document and rewrite the links (if analytics is enabled)
        if ($newsletterDocument->getEnableTrackingParameters()) {
            if ($contentHTML) {
                include_once("simple_html_dom.php");

                $html = str_get_html($contentHTML);
                if ($html) {
                    $links = $html->find("a");
                    foreach ($links as $link) {
                        if (preg_match("/^(mailto)/", trim(strtolower($link->href)))) {
                            continue;
                        }

                        $glue = "?";
                        if (strpos($link->href, "?")) {
                            $glue = "&";
                        }
                        $link->href = $link->href . $glue .
                            "utm_source=" . $newsletterDocument->getTrackingParameterSource() .
                            "&utm_medium=" . $newsletterDocument->getTrackingParameterMedium() .
                            "&utm_campaign=" . $newsletterDocument->getTrackingParameterName();
                    }

                    $contentHTML = $html->save();

                    $html->clear();
                    unset($html);
                }

                $mail->setBodyHtml($contentHTML);
            }
        }

        $mail->setBodyHtml($contentHTML);
        $mail->setBodyText($contentText);
        $mail->setSubject($mail->getSubjectRendered());

        return $mail;
    }

    /**
     * @param Mail $mail
     * @param SendingParamContainer $sendingContainer
     */
    public static function sendNewsletterDocumentBasedMail(Mail $mail, SendingParamContainer $sendingContainer)
    {
        $mailAddress = $sendingContainer->getEmail();
        if (!empty($mailAddress)) {
            $mail->setTo($mailAddress);
            $mail->sendWithoutRendering();

            Logger::info("Sent newsletter to: " . self::obfuscateEmail($mailAddress) . " [" . $mail->getDocument()->getId() . "]");
        } else {
            Logger::warn("No E-Mail Address given - cannot send mail. [" . $mail->getDocument()->getId() . "]");
        }
    }

    /**
     * @param $email
     * @return mixed
     */
    protected static function obfuscateEmail($email)
    {
        $email = substr_replace($email, ".xxx", strrpos($email, "."));

        return $email;
    }

    /**
     * @param Model\Tool\Newsletter\Config $newsletter
     * @param Object\Concrete $object
     * @param null $emailAddress
     * @param null $hostUrl
     */
    public static function sendMail($newsletter, $object, $emailAddress = null, $hostUrl = null)
    {
        $params = [
            "gender" => $object->getGender(),
            'firstname' => $object->getFirstname(),
            'lastname' => $object->getLastname(),
            "email" => $object->getEmail(),
            'token' => $object->getProperty("token"),
            "object" => $object
        ];

        $mail = new Mail();
        $mail->setIgnoreDebugMode(true);

        if (\Pimcore\Config::getSystemConfig()->newsletter->usespecific) {
            $mail->init("newsletter");
        }

        if (!Tool::getHostUrl() && $hostUrl) {
            $mail->setHostUrl($hostUrl);
        }

        if ($emailAddress) {
            $mail->addTo($emailAddress);
        } else {
            $mail->addTo($object->getEmail());
        }
        $mail->setDocument(Document::getById($newsletter->getDocument()));
        $mail->setParams($params);

        // render the document and rewrite the links (if analytics is enabled)
        if ($newsletter->getGoogleAnalytics()) {
            if ($content = $mail->getBodyHtmlRendered()) {
                include_once("simple_html_dom.php");

                $html = str_get_html($content);
                if ($html) {
                    $links = $html->find("a");
                    foreach ($links as $link) {
                        if (preg_match("/^(mailto)/", trim(strtolower($link->href)))) {
                            continue;
                        }

                        $glue = "?";
                        if (strpos($link->href, "?")) {
                            $glue = "&";
                        }
                        $link->href = $link->href . $glue . "utm_source=Newsletter&utm_medium=Email&utm_campaign=" . $newsletter->getName();
                    }
                    $content = $html->save();

                    $html->clear();
                    unset($html);
                }

                $mail->setBodyHtml($content);
            }
        }

        $mail->send();
    }

    /**
     * @param null $classId
     * @throws \Exception
     */
    public function __construct($classId = null)
    {
        $class = null;
        if (is_string($classId)) {
            $class = Object\ClassDefinition::getByName($classId);
        } elseif (is_int($classId)) {
            $class = Object\ClassDefinition::getById($classId);
        } elseif ($classId !== null) {
            throw new \Exception("No valid class identifier given (class name or ID)");
        }

        if ($class instanceof Object\ClassDefinition) {
            $this->setClass($class);
        }
    }

    /**
     * @return string
     */
    protected function getClassName()
    {
        return "\\Pimcore\\Model\\Object\\" . ucfirst($this->getClass()->getName());
    }

    /**
     * @param array $params
     * @return bool
     */
    public function checkParams($params)
    {
        if (!array_key_exists("email", $params)) {
            return false;
        }

        if (strlen($params["email"]) < 6 || !strpos($params["email"], "@") || !strpos($params["email"], ".")) {
            return false;
        }

        return true;
    }

    /**
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function subscribe($params)
    {
        $onlyCreateVersion = false;
        $className = $this->getClassName();
        $object = new $className;

        // check for existing e-mail
        $existingObject = $className::getByEmail($params["email"], 1);
        if ($existingObject) {
            // if there's an existing user with this email address, do not overwrite the contents, but create a new
            // version which will be published as soon as the contact gets verified (token/email)
            $object = $existingObject;
            $onlyCreateVersion = true;
            //throw new \Exception("email address '" . $params["email"] . "' already exists");
        }

        if (!array_key_exists("email", $params)) {
            throw new \Exception("key 'email' is a mandatory parameter");
        }

        $object->setValues($params);

        if (!$object->getParentId()) {
            $object->setParentId(1);
        }

        $object->setNewsletterActive(true);
        $object->setCreationDate(time());
        $object->setModificationDate(time());
        $object->setUserModification(0);
        $object->setUserOwner(0);
        $object->setPublished(true);
        $object->setKey(\Pimcore\File::getValidFilename($object->getEmail() . "~" . substr(uniqid(), -3)));

        if (!$onlyCreateVersion) {
            $object->save();
        }

        // generate token
        $token = base64_encode(\Zend_Json::encode([
            "salt" => md5(microtime()),
            "email" => $object->getEmail(),
            "id" => $object->getId()
        ]));
        $token = str_replace("=", "~", $token); // base64 can contain = which isn't safe in URL's
        $object->setProperty("token", "text", $token);

        if (!$onlyCreateVersion) {
            $object->save();
        } else {
            $object->saveVersion(true, true);
        }

        $this->addNoteOnObject($object, "subscribe");

        return $object;
    }

    /**
     * @param $object
     * @param $mailDocument
     * @param array $params
     * @throws \Exception
     */
    public function sendConfirmationMail($object, $mailDocument, $params = [])
    {
        $defaultParameters = [
            "gender" => $object->getGender(),
            'firstname' => $object->getFirstname(),
            'lastname' => $object->getLastname(),
            "email" => $object->getEmail(),
            'token' => $object->getProperty("token"),
            "object" => $object
        ];

        $params = array_merge($defaultParameters, $params);

        $mail = new Mail();
        $mail->addTo($object->getEmail());
        $mail->setDocument($mailDocument);
        $mail->setParams($params);
        $mail->send();
    }

    /**
     * @param $token
     * @return bool
     * @throws \Zend_Json_Exception
     */
    public function getObjectByToken($token)
    {
        $originalToken = $token;
        $token = str_replace("~", "=", $token); // base64 can contain = which isn't safe in URL's

        $data = \Zend_Json::decode(base64_decode($token));
        if ($data) {
            if ($object = Object::getById($data["id"])) {
                if ($version = $object->getLatestVersion()) {
                    $object = $version->getData();
                }

                if ($object->getProperty("token") == $originalToken) {
                    if ($object->getEmail() == $data["email"]) {
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
    public function confirm($token)
    {
        $object = $this->getObjectByToken($token);
        if ($object) {
            if ($version = $object->getLatestVersion()) {
                $object = $version->getData();
                $object->setPublished(true);
            }

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
    public function unsubscribeByToken($token)
    {
        $object = $this->getObjectByToken($token);
        if ($object) {
            return $this->unsubscribe($object);
        }

        return false;
    }

    /**
     * @param string $email
     * @return bool
     */
    public function unsubscribeByEmail($email)
    {
        $className = $this->getClassName();
        $objects = $className::getByEmail($email);
        if (count($objects)) {
            foreach ($objects as $object) {
                $this->unsubscribe($object);
            }

            return true;
        }

        return false;
    }


    /**
     * @param $object
     * @return bool
     */
    public function unsubscribe($object)
    {
        if ($object) {
            $object->setNewsletterActive(false);
            $object->save();

            $this->addNoteOnObject($object, "unsubscribe");

            return true;
        }

        return false;
    }

    /**
     * @param $object
     * @param $title
     */
    public function addNoteOnObject($object, $title)
    {
        $note = new Model\Element\Note();
        $note->setElement($object);
        $note->setDate(time());
        $note->setType("newsletter");
        $note->setTitle($title);
        $note->setUser(0);
        $note->setData([
            "ip" => [
                "type" => "text",
                "data" => Tool::getClientIp()
            ]
        ]);
        $note->save();
    }

    /**
     * Checks if e-mail address already
     * exists in the database.
     *
     * @param array $params
     * @return bool
     */
    public function isEmailExists($params)
    {
        $className = $this->getClassName();
        $existingObject = $className::getByEmail($params["email"], 1);
        if ($existingObject) {
            return true;
        }

        return false;
    }

    /**
     * @param Object\ClassDefinition $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return Object\ClassDefinition
     */
    public function getClass()
    {
        return $this->class;
    }
}
