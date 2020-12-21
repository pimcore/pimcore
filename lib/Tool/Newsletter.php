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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Config;
use Pimcore\Document\Newsletter\SendingParamContainer;
use Pimcore\Event\DocumentEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Mail;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\GenericEvent;

class Newsletter
{
    public const SENDING_MODE_BATCH = 'batch';
    public const SENDING_MODE_SINGLE = 'single';

    /**
     * @var DataObject\ClassDefinition
     */
    protected $class;

    /**
     * @param Document\Newsletter $newsletterDocument
     * @param SendingParamContainer|null $sendingContainer
     * @param string|null $hostUrl
     *
     * @return Mail
     *
     * @throws Exception
     */
    public static function prepareMail(
        Document\Newsletter $newsletterDocument,
        SendingParamContainer $sendingContainer = null,
        $hostUrl = null
    ): Mail {
        $mail = new Mail();
        $mail->setIgnoreDebugMode(true);
        $config = Config::getSystemConfiguration('newsletter');

        if ($config['use_specific']) {
            $mail->init('newsletter');
        }

        if ($hostUrl) {
            $mail->setHostUrl($hostUrl);
        }

        $mail->setDocument($newsletterDocument);

        if ($sendingContainer && $sendingContainer->getParams()) {
            $mail->setParams($sendingContainer->getParams());
        }

        if (strlen(trim($newsletterDocument->getPlaintext())) > 0) {
            $mail->setBodyText(trim($newsletterDocument->getPlaintext()));
        }

        $contentHTML = $mail->getBodyHtmlRendered();
        $contentText = $mail->getBodyTextRendered();

        // render the document and rewrite the links (if analytics is enabled)
        if ($contentHTML && $newsletterDocument->getEnableTrackingParameters()) {
            $html = str_get_html($contentHTML);
            if ($html) {
                $links = $html->find('a');
                foreach ($links as $link) {
                    if (preg_match('/^(mailto|#)/i', trim($link->href))) {
                        // No tracking for mailto and hash only links
                        continue;
                    }

                    $urlParts = parse_url($link->href);
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

                    $link->href = preg_replace('/[#].+$/', '', $link->href).$glue.$params;

                    if (isset($urlParts['fragment'])) {
                        $link->href .= '#'.$urlParts['fragment'];
                    }
                }

                $contentHTML = $html->save();

                $html->clear();
                unset($html);
            }

            $mail->setBodyHtml($contentHTML);
        }

        $mail->setBodyHtml($contentHTML);
        $mail->setBodyText($contentText);
        // Adds the plain text part to the message, that it becomes a multipart email
        $mail->addPart($contentText, 'text/plain');
        $mail->setSubject($mail->getSubjectRendered());

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
        $config = Config::getSystemConfiguration('newsletter');

        if (!self::to_domain_exists($mailAddress)) {
            Logger::err('E-Mail address invalid: ' . self::obfuscateEmail($mailAddress));
            $mailAddress = null;
        }

        if (!empty($mailAddress)) {
            $mail->setTo($mailAddress);

            $mailer = null;
            // check if newsletter specific mailer is needed
            if ($config['use_specific']) {
                $mailer = Pimcore::getContainer()->get('swiftmailer.mailer.newsletter_mailer');
            }

            $event = new GenericEvent($mail, [
                'mail' => $mail,
                'document' => $mail->getDocument(),
                'sendingContainer' => $sendingContainer,
                'mailer' => $mailer,
            ]);

            Pimcore::getEventDispatcher()->dispatch(DocumentEvents::NEWSLETTER_PRE_SEND, $event);
            $mail->sendWithoutRendering($mailer);
            Pimcore::getEventDispatcher()->dispatch(DocumentEvents::NEWSLETTER_POST_SEND, $event);

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

    /**
     * @param string $email
     *
     * @return string
     */
    protected static function obfuscateEmail($email)
    {
        $email = substr_replace($email, '.xxx', strrpos($email, '.'));

        return $email;
    }

    /**
     * @param Model\Document\Newsletter $newsletter
     * @param DataObject\Concrete $object
     * @param string|null $emailAddress
     * @param string|null $hostUrl
     *
     * @throws Exception
     *
     * @deprecated Pimcore\Tool\Newsletter::sendMail is deprecated and will be removed with 7.0,
     * please use the internal:newsletter-document-send command instead.
     */
    public static function sendMail($newsletter, $object, $emailAddress = null, $hostUrl = null): void
    {
        trigger_error(
            sprintf(
                '%s::%s is deprecated and will be removed with 7.0, please use the %s command instead.',
                static::class,
                __METHOD__,
                'internal:newsletter-document-send'
            ),
            E_USER_DEPRECATED
        );

        $config = Config::getSystemConfiguration('newsletter');

        $params = [
            'gender' => $object->getGender(),
            'firstname' => $object->getFirstname(),
            'lastname' => $object->getLastname(),
            'email' => $object->getEmail(),
            'token' => $object->getProperty('token'),
            'object' => $object,
        ];

        $mail = new Mail();
        $mail->setIgnoreDebugMode(true);

        if ($config['use_specific']) {
            $mail->init('newsletter');
        }

        if ($hostUrl) {
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
        if ($newsletter->getGoogleAnalytics() && $content = $mail->getBodyHtmlRendered()) {
            $html = str_get_html($content);
            if ($html) {
                $links = $html->find('a');
                foreach ($links as $link) {
                    if (preg_match('/^(mailto)/i', trim($link->href))) {
                        continue;
                    }

                    $glue = strpos($link->href, '?') ? '&' : '?';

                    $link->href .= sprintf(
                        '%sutm_source=Newsletter&utm_medium=Email&utm_campaign=%s',
                        $glue,
                        $newsletter->getName()
                    );
                }
                $content = $html->save();

                $html->clear();
                unset($html);
            }

            $mail->setBodyHtml($content);
        }

        $mail->send();
    }

    /**
     * @param string $classId
     *
     * @throws Exception
     */
    public function __construct($classId)
    {
        $class = null;
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
     * @return string
     */
    protected function getClassName(): string
    {
        return '\\Pimcore\\Model\\DataObject\\' . ucfirst($this->getClass()->getName());
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function checkParams($params): bool
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
    public function subscribe($params)
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

        $object->setNewsletterActive(true);
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
     * @param Document $mailDocument
     * @param array $params
     *
     * @throws Exception
     */
    public function sendConfirmationMail($object, $mailDocument, $params = []): void
    {
        $defaultParameters = [
            'gender' => $object->getGender(),
            'firstname' => $object->getFirstname(),
            'lastname' => $object->getLastname(),
            'email' => $object->getEmail(),
            'token' => $object->getProperty('token'),
            'object' => $object,
        ];

        $params = array_merge($defaultParameters, $params);

        $mail = new Mail();
        $mail->addTo($object->getEmail());
        $mail->setDocument($mailDocument);
        $mail->setParams($params);
        $mail->send();
    }

    /**
     * @param string $token
     *
     * @return DataObject\Concrete|null
     */
    public function getObjectByToken($token): ?DataObject\Concrete
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
    public function confirm($token): bool
    {
        $object = $this->getObjectByToken($token);
        if ($object) {
            if ($version = $object->getLatestVersion()) {
                $object = $version->getData();
                $object->setPublished(true);
            }

            $object->setNewsletterConfirmed(true);
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
    public function unsubscribeByToken($token): bool
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
    public function unsubscribeByEmail($email): bool
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
     * @param DataObject\AbstractObject $object
     *
     * @return bool
     *
     * @throws Exception
     */
    public function unsubscribe(DataObject\AbstractObject $object): bool
    {
        if ($object) {
            $object->setNewsletterActive(false);
            $object->save();

            $this->addNoteOnObject($object, 'unsubscribe');

            return true;
        }

        return false;
    }

    /**
     * @param DataObject\Concrete $object
     * @param string $title
     */
    public function addNoteOnObject($object, $title): void
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
    public function isEmailExists($params): bool
    {
        $className = $this->getClassName();
        $existingObject = $className::getByEmail($params['email'], 1);
        if ($existingObject) {
            return true;
        }

        return false;
    }

    /**
     * @param DataObject\ClassDefinition $class
     */
    public function setClass($class): void
    {
        $this->class = $class;
    }

    /**
     * @return DataObject\ClassDefinition
     */
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
    public static function to_domain_exists($email): bool
    {
        [, $domain] = explode('@', $email);

        return checkdnsrr($domain, 'MX');
    }
}
