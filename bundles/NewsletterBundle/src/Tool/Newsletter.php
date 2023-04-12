<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\NewsletterBundle\Tool;

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Bundle\NewsletterBundle\Document\Newsletter\SendingParamContainer;
use Pimcore\Bundle\NewsletterBundle\Event\DocumentEvents;
use Pimcore\Bundle\NewsletterBundle\Model\Document\Newsletter as NewsletterDocument;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Mail;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document\Email;
use Pimcore\Tool;
use Pimcore\Tool\DomCrawler;
use Symfony\Component\EventDispatcher\GenericEvent;

class Newsletter
{
    public const SENDING_MODE_BATCH = 'batch';

    public const SENDING_MODE_SINGLE = 'single';

    protected DataObject\ClassDefinition $class;

    /**
     * @param string $classId
     *
     * @throws Exception
     */
    public function __construct(string $classId)
    {
        if (is_numeric($classId)) {
            $class = DataObject\ClassDefinition::getById($classId);
        } else {
            $class = DataObject\ClassDefinition::getByName($classId);
        }

        if (!$class) {
            throw new InvalidArgumentException('No valid class identifier given (class name or ID)');
        }

        if ($class instanceof DataObject\ClassDefinition) {
            $this->setClass($class);
        }
    }

    /**
     * @param NewsletterDocument $newsletterDocument
     * @param SendingParamContainer|null $sendingContainer
     * @param string|null $hostUrl
     *
     * @return Mail
     *
     * @throws Exception
     */
    public static function prepareMail(
        NewsletterDocument $newsletterDocument,
        SendingParamContainer $sendingContainer = null,
        string $hostUrl = null
    ): Mail {
        $mail = new Mail();
        $mail->setIgnoreDebugMode(true);
        $config = Pimcore::getContainer()->getParameter('pimcore_newsletter');

        if ($config['use_specific']) {
            $mail->init('newsletter', $config);
        }

        if ($hostUrl) {
            $mail->setHostUrl($hostUrl);
        }

        $mail->setDocument($newsletterDocument);

        if ($sendingContainer && $sendingContainer->getParams()) {
            $mail->setParams($sendingContainer->getParams());
        }

        if (trim($newsletterDocument->getPlaintext()) !== '') {
            $mail->text(trim($newsletterDocument->getPlaintext()));
        }

        $contentHTML = $mail->getBodyHtmlRendered();
        $contentText = $mail->getBodyTextRendered();

        // render the document and rewrite the links (if analytics is enabled)
        if ($contentHTML && $newsletterDocument->getEnableTrackingParameters()) {
            $html = new DomCrawler($contentHTML);
            $links = $html->filter('a');
            /** @var \DOMElement $link */
            foreach ($links as $link) {
                if (preg_match('/^(mailto|#)/i', trim($link->getAttribute('href')))) {
                    // No tracking for mailto and hash only links
                    continue;
                }

                $urlParts = parse_url($link->getAttribute('href'));
                $glue = '?';
                $params = sprintf(
                    'utm_source=%s&utm_medium=%s&utm_campaign=%s',
                    $newsletterDocument->getTrackingParameterSource(),
                    $newsletterDocument->getTrackingParameterMedium(),
                    $newsletterDocument->getTrackingParameterName()
                );

                if (isset($urlParts['query'])) {
                    $glue = '&';
                }

                $href = preg_replace('/[#].+$/', '', $link->getAttribute('href')).$glue.$params;

                if (isset($urlParts['fragment'])) {
                    $href .= '#'.$urlParts['fragment'];
                }

                $link->setAttribute('href', $href);
            }
            $contentHTML = $html->html();

            $html->clear();
            unset($html);

            $mail->html($contentHTML);
        }

        $mail->html($contentHTML);
        // Adds the plain text part to the message, that it becomes a multipart email
        $mail->text($contentText);
        $mail->subject($mail->getSubjectRendered());

        return $mail;
    }

    /**
     * @param Mail $mail
     * @param SendingParamContainer $sendingContainer
     *
     * @throws Exception
     */
    public static function sendNewsletterDocumentBasedMail(Mail $mail, SendingParamContainer $sendingContainer): void
    {
        $mailAddress = $sendingContainer->getEmail();
        $config = Pimcore::getContainer()->getParameter('pimcore_newsletter');

        if (!self::to_domain_exists($mailAddress)) {
            Logger::err('E-Mail address invalid: ' . self::obfuscateEmail($mailAddress));
            $mailAddress = null;
        }

        if (!empty($mailAddress)) {
            $mail->to($mailAddress);

            $mailer = null;
            // check if newsletter specific mailer is needed
            if ($config['use_specific']) {
                $mail->getHeaders()->addTextHeader('X-Transport', 'pimcore_newsletter');
            }

            $event = new GenericEvent($mail, [
                'mail' => $mail,
                'document' => $mail->getDocument(),
                'sendingContainer' => $sendingContainer,
                'mailer' => $mailer,
            ]);

            Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::NEWSLETTER_PRE_SEND);
            $mail->sendWithoutRendering($mailer);
            Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::NEWSLETTER_POST_SEND);

            Logger::info(
                sprintf(
                    'Sent newsletter to: %s [%s]',
                    self::obfuscateEmail($mailAddress),
                    $mail->getDocument() ? $mail->getDocument()->getId() : null
                )
            );
        } else {
            Logger::warn(
                sprintf(
                    'No E-Mail Address given - cannot send mail. [%s]',
                    $mail->getDocument() ? $mail->getDocument()->getId() : null
                )
            );
        }
    }

    protected static function obfuscateEmail(string $email): string
    {
        return substr_replace($email, '.xxx', strrpos($email, '.'));
    }

    protected function getClassName(): string
    {
        return '\\Pimcore\\Model\\DataObject\\' . ucfirst($this->getClass()->getName());
    }

    public function checkParams(array $params): bool
    {
        if (!array_key_exists('email', $params)) {
            return false;
        }

        if (strlen($params['email']) < 6 ||
            !strpos($params['email'], '@') ||
            !strpos($params['email'], '.')) {
            return false;
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return DataObject\Concrete
     *
     * @throws Exception
     */
    public function subscribe(array $params): DataObject\Concrete
    {
        $onlyCreateVersion = false;
        $className = $this->getClassName();
        /** @var DataObject\Concrete $object */
        $object = new $className;

        // check for existing e-mail
        $existingObject = $className::getByEmail($params['email'], 1);
        if ($existingObject) {
            // if there's an existing user with this email address, do not overwrite the contents, but create a new
            // version which will be published as soon as the contact gets verified (token/email)
            $object = $existingObject;
            $onlyCreateVersion = true;
        }

        if (!array_key_exists('email', $params)) {
            throw new InvalidArgumentException("key 'email' is a mandatory parameter");
        }

        $object->setValues($params);

        if (!$object->getParentId()) {
            $object->setParentId(1);
        }

        if (method_exists($object, 'setNewsletterActive')) {
            $object->setNewsletterActive(true);
        }
        $object->setModificationDate(time());
        $object->setUserModification(0);
        $object->setUserOwner(0);
        $object->setPublished(true);
        $object->setKey(File::getValidFilename(uniqid($object->getEmail(), true)));

        if (!$onlyCreateVersion) {
            $object->setCreationDate(time());
            $object->save();
        }

        // generate token
        $token = base64_encode(json_encode([
            'salt' => md5(microtime()),
            'email' => $object->getEmail(),
            'id' => $object->getId(),
        ]));
        $token = str_replace('=', '~', $token); // base64 can contain = which isn't safe in URL's
        $object->setProperty('token', 'text', $token);

        if (!$onlyCreateVersion) {
            $object->save();
        } else {
            $object->saveVersion();
        }

        $this->addNoteOnObject($object, 'subscribe');

        return $object;
    }

    /**
     * @param DataObject\Concrete $object
     * @param Email $mailDocument
     * @param array $params
     *
     * @throws Exception
     */
    public function sendConfirmationMail(DataObject\Concrete $object, Email $mailDocument, array $params = []): void
    {
        $defaultParameters = [
            'gender' => null,
            'firstname' => null,
            'lastname' => null,
            'email' => null,
            'token' => $object->getProperty('token'),
            'object' => $object,
        ];

        if (method_exists($object, 'getGender')) {
            $defaultParameters['gender'] = $object->getGender();
        }

        if (method_exists($object, 'getFirstname')) {
            $defaultParameters['firstname'] = $object->getFirstname();
        }

        if (method_exists($object, 'getLastname')) {
            $defaultParameters['lastname'] = $object->getLastname();
        }

        if (method_exists($object, 'getEmail')) {
            $defaultParameters['email'] = $object->getEmail();
        }

        $params = array_merge($defaultParameters, $params);

        $mail = new Mail();
        $mail->addTo($params['email']);
        $mail->setDocument($mailDocument);
        $mail->setParams($params);
        $mail->send();
    }

    public function getObjectByToken(string $token): ?DataObject\Concrete
    {
        $originalToken = $token;
        $token = str_replace('~', '=', $token); // base64 can contain = which isn't safe in URL's

        $data = json_decode(base64_decode($token), true);

        if ($data && $object = DataObject\Concrete::getById($data['id'])) {
            if ($version = $object->getLatestVersion()) {
                $object = $version->getData();
            }

            if ($object->getProperty('token') === $originalToken && $object->getEmail() === $data['email']) {
                return $object;
            }
        }

        return null;
    }

    /**
     * @param string $token
     *
     * @return bool
     *
     * @throws Exception
     */
    public function confirm(string $token): bool
    {
        $object = $this->getObjectByToken($token);
        if ($object) {
            if ($version = $object->getLatestVersion()) {
                $object = $version->getData();
                $object->setPublished(true);
            }

            if (method_exists($object, 'setNewsletterConfirmed')) {
                $object->setNewsletterConfirmed(true);
            }
            $object->save();

            $this->addNoteOnObject($object, 'confirm');

            return true;
        }

        return false;
    }

    /**
     * @param string $token
     *
     * @return bool
     *
     * @throws Exception
     */
    public function unsubscribeByToken(string $token): bool
    {
        $object = $this->getObjectByToken($token);

        if ($object) {
            return $this->unsubscribe($object);
        }

        return false;
    }

    /**
     * @param string $email
     *
     * @return bool
     *
     * @throws Exception
     */
    public function unsubscribeByEmail(string $email): bool
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
     * @param DataObject\Concrete $object
     *
     * @return bool
     *
     * @throws Exception
     */
    public function unsubscribe(DataObject\Concrete $object): bool
    {
        if (method_exists($object, 'setNewsletterActive')) {
            $object->setNewsletterActive(false);
        }
        $object->save();

        $this->addNoteOnObject($object, 'unsubscribe');

        return true;
    }

    public function addNoteOnObject(DataObject\Concrete $object, string $title): void
    {
        $note = new Model\Element\Note();
        $note->setElement($object);
        $note->setDate(time());
        $note->setType('newsletter');
        $note->setTitle($title);
        $note->setUser(0);
        $note->setData([
            'ip' => [
                'type' => 'text',
                'data' => Tool::getClientIp(),
            ],
        ]);
        $note->save();
    }

    /**
     * Checks if e-mail address already
     * exists in the database.
     *
     * @param array $params
     *
     * @return bool
     */
    public function isEmailExists(array $params): bool
    {
        $className = $this->getClassName();
        $existingObject = $className::getByEmail($params['email'], 1);
        if ($existingObject) {
            return true;
        }

        return false;
    }

    public function setClass(DataObject\ClassDefinition $class): void
    {
        $this->class = $class;
    }

    public function getClass(): DataObject\ClassDefinition
    {
        return $this->class;
    }

    /**
     * Checks if domain of email has a MX record
     *
     * @param string $email
     *
     * @return bool
     */
    public static function to_domain_exists(string $email): bool
    {
        [, $domain] = explode('@', $email);

        return checkdnsrr($domain, 'MX');
    }
}
