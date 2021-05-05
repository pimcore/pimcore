<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use League\HTMLToMarkdown\HtmlConverter;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\ElementListener;
use Pimcore\Event\MailEvents;
use Pimcore\Event\Model\MailEvent;
use Pimcore\Helper\Mail as MailHelper;
use Pimcore\Mail\Mailer;
use Pimcore\Tool\DomCrawler;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\MailboxListHeader;
use Symfony\Component\Mime\Part\AbstractPart;

class Mail extends Email
{
    /**
     * @var bool
     */
    private static $forceDebugMode = false;

    /**
     * If true - emails are logged in the database and on the file-system
     *
     * @var bool
     */
    private $loggingEnable = true;

    /**
     * Contains the email document
     *
     * @var Model\Document\Email|Model\Document\Newsletter|null
     */
    private $document;

    /**
     * Contains the dynamic Params for the Twig engine
     *
     * @var array
     */
    private $params = [];

    /**
     * Options passed to html2text
     *
     * @var array
     */
    private $html2textOptions = [
        'ignore_errors' => true,
    ];

    /**
     * Prevent adding debug information
     *
     * @var bool
     */
    private $preventDebugInformationAppending = false;

    /**
     * if true - the Pimcore debug mode is ignored
     *
     * @var bool
     */
    private $ignoreDebugMode = false;

    /**
     * if $hostUrl is set - this url well be used to create absolute urls
     * otherwise it is determined automatically
     *
     * @see MailHelper::setAbsolutePaths()
     *
     * @var string|null
     */
    private $hostUrl = null;

    /**
     * if true: prevent setting the recipients from the Document - set in $this->clearRecipients()
     *
     * @var bool
     */
    private $recipientsCleared = false;

    /**
     * place to store original data before modifying message when sending in debug mode
     *
     * @var array
     */
    private $originalData;

    /**
     * @var Model\Tool\Email\Log
     */
    private $lastLogEntry;

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setHostUrl($url)
    {
        $this->hostUrl = $url;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHostUrl()
    {
        return $this->hostUrl;
    }

    /**
     * @param array|Headers|null $headers
     * @param AbstractPart|null $body
     * @param string|null $contentType
     */
    public function __construct($headers = null, $body = null, $contentType = null)
    {
        if (is_array($headers)) {
            $options = $headers;

            $headers = $options['headers'] instanceof Headers ? $options['headers'] : null;
            $body = $options['body'] instanceof AbstractPart ? $options['body'] : null;
            parent::__construct($headers, $body);

            if ($options['subject'] ?? false) {
                $this->subject($options['subject']);
            }
            if ($options['document'] ?? false) {
                $this->setDocument($options['document']);
            }
            if ($options['params'] ?? false) {
                $this->setParams($options['params']);
            }
            if ($options['hostUrl'] ?? false) {
                $this->setHostUrl($options['hostUrl']);
            }
        } else {
            parent::__construct($headers, $body);
        }

        if ($contentType) {
            $this->setContentType($contentType);
        }

        $this->init();
    }

    /**
     * Initializes the mailer with the settings form Settings -> System -> Email Settings
     *
     * @param string $type
     *
     * @internal
     */
    public function init($type = 'email')
    {
        $config = Config::getSystemConfiguration($type);

        if (!empty($config['sender']['email'])) {
            if (empty($this->getFrom())) {
                $this->from(new Address($config['sender']['email'], $config['sender']['name']));
            }
        }

        if (!empty($config['return']['email'])) {
            if (empty($this->getReplyTo())) {
                $this->replyTo(new Address($config['return']['email'], $config['return']['name']));
            }
        }
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setIgnoreDebugMode($value)
    {
        $this->ignoreDebugMode = (bool)$value;

        return $this;
    }

    /**
     * Checks if the Debug mode is ignored
     *
     * @return bool
     */
    public function getIgnoreDebugMode()
    {
        return $this->ignoreDebugMode;
    }

    /**
     * returns if redirecting to debug mail addresses should take place when sending the mail
     * @internal
     * @return bool
     */
    public function doRedirectMailsToDebugMailAddresses()
    {
        if (static::$forceDebugMode) {
            return true;
        }

        return \Pimcore::inDebugMode() && $this->ignoreDebugMode === false;
    }

    /**
     * Sets options that are passed to html2text
     *
     * @param array $options
     *
     * @return \Pimcore\Mail
     */
    public function setHtml2TextOptions(array $options = [])
    {
        $this->html2textOptions = $options;

        return $this;
    }

    /**
     * Returns options for html2text
     *
     * @return array
     */
    public function getHtml2TextOptions()
    {
        return $this->html2textOptions;
    }

    /**
     * Clears list of recipient email addresses
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function clearRecipients()
    {
        $this->recipientsCleared = true;

        $this->getHeaders()->remove('to');
        $this->getHeaders()->remove('cc');
        $this->getHeaders()->remove('bcc');
        $this->getHeaders()->remove('reply-to');

        return $this;
    }

    /**
     * Disables email logging
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function disableLogging()
    {
        $this->loggingEnable = false;

        return $this;
    }

    /**
     * Enables email logging (by default it's enabled)
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function enableLogging()
    {
        $this->loggingEnable = true;

        return $this;
    }

    /**
     * returns the logging status
     *
     * @return bool
     */
    public function loggingIsEnabled()
    {
        return $this->loggingEnable;
    }

    /**
     * Sets the parameters to the request object
     *
     * @param array $params
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function setParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->setParam($key, $value);
        }

        return $this;
    }

    /**
     * Sets a single parameter to the request object
     *
     * @param string|int $key
     * @param mixed $value
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function setParam($key, $value)
    {
        if (is_string($key) || is_int($key)) {
            $this->params[$key] = $value;
        } else {
            Logger::warn('$key has to be a string or integer - Param ignored!');
        }

        return $this;
    }

    /**
     * Returns the parameters which were set with "setParams" or "setParam"
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Returns a parameter which was set with "setParams" or "setParam"
     *
     * @param string|int $key
     *
     * @return mixed
     */
    public function getParam($key)
    {
        return $this->params[$key];
    }

    /**
     * Forces the debug mode - useful for cli-script which should not send emails to recipients
     *
     * @param bool $value
     */
    public static function setForceDebugMode($value)
    {
        self::$forceDebugMode = $value;
    }

    /**
     * Deletes parameters which were set with "setParams" or "setParam"
     *
     * @param array $params
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function unsetParams(array $params)
    {
        foreach ($params as $param) {
            $this->unsetParam($param);
        }

        return $this;
    }

    /**
     * Deletes a single parameter which was set with "setParams" or "setParam"
     *
     * @param string|int $key
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function unsetParam($key)
    {
        if (is_string($key) || is_int($key)) {
            unset($this->params[$key]);
        } else {
            Logger::warn('$key has to be a string or integer - unsetParam ignored!');
        }

        return $this;
    }

    /**
     * Sets the settings which are defined in the Document Settings (from,to,cc,bcc,replyTo)
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    private function setDocumentSettings()
    {
        $document = $this->getDocument();

        if ($document instanceof Model\Document\Email) {
            if (!$this->recipientsCleared) {
                $to = \Pimcore\Helper\Mail::parseEmailAddressField($document->getTo());
                if (!empty($to)) {
                    foreach ($to as $toEntry) {
                        $this->addTo(new Address($toEntry['email'], $toEntry['name'] ?? ''));
                    }
                }

                $cc = \Pimcore\Helper\Mail::parseEmailAddressField($document->getCc());
                if (!empty($cc)) {
                    foreach ($cc as $ccEntry) {
                        $this->addCc(new Address($ccEntry['email'], $ccEntry['name'] ?? ''));
                    }
                }

                $bcc = \Pimcore\Helper\Mail::parseEmailAddressField($document->getBcc());
                if (!empty($bcc)) {
                    foreach ($bcc as $bccEntry) {
                        $this->addBcc(new Address($bccEntry['email'], $bccEntry['name'] ?? ''));
                    }
                }

                $replyTo = \Pimcore\Helper\Mail::parseEmailAddressField($document->getReplyTo());
                if (!empty($replyTo)) {
                    foreach ($replyTo as $replyToEntry) {
                        $this->addReplyTo(new Address($replyToEntry['email'], $replyToEntry['name'] ?? ''));
                    }
                }
            }
        }

        if ($document instanceof Model\Document\Email || $document instanceof Model\Document\Newsletter) {
            //if more than one "from" email address is defined -> we set the first one
            $fromArray = \Pimcore\Helper\Mail::parseEmailAddressField($document->getFrom());
            if (!empty($fromArray)) {
                list($from) = $fromArray;
                if ($from) {
                    $this->from(new Address($from['email'], $from['name']));
                }
            }
        }

        return $this;
    }

    /**
     * Sends this email using the given transport or with the settings from "Settings" -> "System" -> "Email Settings"
     *
     * IMPORTANT: If the debug mode is enabled in "Settings" -> "System" -> "Debug" all emails will be sent to the
     * debug email addresses that are given in "Settings" -> "System" -> "Email Settings" -> "Debug email addresses"
     *
     * set DefaultTransport or the internal mail function if no
     * default transport had been set.
     *
     * @param  MailerInterface|null $mailer
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function send(MailerInterface $mailer = null)
    {
        $bodyHtmlRendered = $this->getBodyHtmlRendered();
        if ($bodyHtmlRendered) {
            $this->html($bodyHtmlRendered);
        }

        $bodyTextRendered = $this->getBodyTextRendered();
        if ($bodyTextRendered) {
            //add mime part for plain text body
            $this->text($bodyTextRendered);
        }

        $this->subject($this->getSubjectRendered());

        return $this->sendWithoutRendering($mailer);
    }

    /**
     * sends mail without (re)rendering the content.
     * see also comments of send() method
     *
     * @param MailerInterface|null $mailer
     *
     * @return \Pimcore\Mail
     *
     * @throws \Exception
     */
    public function sendWithoutRendering(MailerInterface $mailer = null)
    {
        // filter email addresses

        // ... Remove headers that would otherwise be duplicated
        // $message->getHeaders()->remove('To');
        // $message->getHeaders()->remove('Subject'); ....

        $recipients = [];

        foreach (['To', 'Cc', 'Bcc', 'ReplyTo'] as $key) {
            $recipients[$key] = null;
            $getterName = 'get' . $key;
            $addresses = $this->$getterName();

            if ($addresses) {
                $addresses = $this->filterLogAddresses($addresses);
                /** @var MailboxListHeader|null $header */
                $header = $this->getHeaders()->get(strtolower($key));
                if ($header) {
                    $header->setAddresses($addresses);
                }
            }

            $addresses = $this->$getterName();
            $recipients[$key] = $addresses;
        }

        if ($mailer == null) {
            //if no mailer given, get default mailer from container
            $mailer = \Pimcore::getContainer()->get(Mailer::class);
        }

        if (empty($this->getFrom()) && $hostname = Tool::getHostname()) {
            // set default "from" address
            $this->from('no-reply@' . $hostname);
        }

        $event = new MailEvent($this, [
            'mailer' => $mailer,
        ]);

        \Pimcore::getEventDispatcher()->dispatch($event, MailEvents::PRE_SEND);

        if ($event->hasArgument('mailer')) {
            $mailer = $event->getArgument('mailer');
            $failedRecipients = [];
            try {
                $mailer->send($this);
            } catch (TransportExceptionInterface $e) {
                if (isset($failedRecipients[0])) {
                    throw new \Exception($failedRecipients[0].' - '.$e->getMessage());
                } else {
                    throw new \Exception($e->getMessage());
                }
            }
        }

        if ($this->loggingIsEnabled()) {
            if (\Pimcore::inDebugMode() && !$this->ignoreDebugMode) {
                $recipients = $this->getDebugMailRecipients($recipients);
            }

            try {
                $this->lastLogEntry = MailHelper::logEmail($this, $recipients);
            } catch (\Exception $e) {
                Logger::emerg("Couldn't log Email");
            }
        }

        return $this;
    }

    /**
     * @param array $addresses
     * @return array
     */
    private function filterLogAddresses(array $addresses): array
    {
        foreach ($addresses as $addrKey => $address) {
            if ($address instanceof Address) {
                // remove address if blacklisted
                if (Model\Tool\Email\Blacklist::getByAddress($address->getAddress())) {
                    unset($addrKey);
                }
            } else {
                // remove address if blacklisted
                if (Model\Tool\Email\Blacklist::getByAddress($addrKey)) {
                    unset($addresses[$addrKey]);
                }
            }
        }

        return $addresses;
    }

    /**
     * @param array $recipients
     * @return array
     */
    private function getDebugMailRecipients(array $recipients): array
    {
        $headers = $this->getHeaders();

        foreach (['To', 'Cc', 'Bcc', 'ReplyTo'] as $key) {
            $recipients[$key] = null;

            $headerName = 'X-Pimcore-Debug-' . $key;
            if ($headers->has($headerName)) {
                /** @var MailboxListHeader $header */
                $header = $headers->get($headerName);
                $recipients[$key] = $header->getAddresses();

                $headers->remove($headerName);
            }

            if ($recipients[$key]) {
                $recipients[$key] = $this->filterLogAddresses($recipients[$key]);
            }
        }

        return $recipients;
    }

    /**
     * @param string $string
     * @return string
     */
    private function renderParams(string $string): string
    {
        $twig = \Pimcore::getContainer()->get('twig');
        $template = $twig->createTemplate($string);
        $rendered = $twig->render($template, $this->getParams());

        return $rendered;
    }

    /**
     * Renders the content (Twig) and returns the rendered subject
     * @internal
     * @return string
     */
    public function getSubjectRendered()
    {
        $subject = $this->getSubject();

        if (!$subject && $this->getDocument()) {
            $subject = $this->getDocument()->getSubject();
        }

        if ($subject) {
            return $this->renderParams($subject);
        }

        return '';
    }

    /**
     * Renders the content (Twig) and returns the rendered HTML
     * @internal
     * @return string|null
     */
    public function getBodyHtmlRendered()
    {
        $html = $this->getHtmlBody();

        // if the content was manually set with $obj->setBody(); this content will be used
        // and not the content of the Document!
        if (!$html) {
            // render document
            if ($this->getDocument() instanceof Model\Document) {
                $attributes = $this->getParams();
                $attributes[ElementListener::FORCE_ALLOW_PROCESSING_UNPUBLISHED_ELEMENTS] = true;

                $html = Model\Document\Service::render($this->getDocument(), $attributes);
            }
        }

        $content = null;
        if ($html) {
            $content = $this->renderParams($html);

            // modifying the content e.g set absolute urls...
            $content = MailHelper::embedAndModifyCss($content, $this->getDocument());
            $content = MailHelper::setAbsolutePaths($content, $this->getDocument(), $this->getHostUrl());
        }

        return $content;
    }

    /**
     * Renders the content (Twig) and returns
     * the rendered text if a text was set with "$mail->text()"
     * @internal
     * @return string
     */
    public function getBodyTextRendered()
    {
        $text = $this->getTextBody();

        //if the content was manually set with $obj->text(); this content will be used
        if ($text) {
            $content = $this->renderParams($text);
        } else {
            //creating text version from html email
            try {
                $htmlContent = $this->getBodyHtmlRendered();
                $html = new DomCrawler($htmlContent);

                $body = $html->filter('body')->eq(0);
                if ($body->count()) {
                    $style = $body->filter('style')->eq(0);
                    if ($style->count()) {
                        $style->clear();
                    }
                    $htmlContent = $body->html();
                }

                $html->clear();
                unset($html);

                $content = $this->html2Text($htmlContent);
            } catch (\Exception $e) {
                Logger::err($e);
                $content = '';
            }
        }

        return $content;
    }

    /**
     * @param Model\Document|int|string $document
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setDocument($document)
    {
        if (!empty($document)) {
            if (is_numeric($document)) { //id of document passed
                $document = Model\Document\Email::getById($document);
            } elseif (is_string($document)) { //path of document passed
                $document = Model\Document\Email::getByPath($document);
            }
        }

        if ($document instanceof Model\Document\Email || $document instanceof Model\Document\Newsletter) {
            $this->document = $document;
            $this->setDocumentSettings();
        } else {
            throw new \Exception("$document is not an instance of " . Model\Document\Email::class);
        }

        return $this;
    }

    /**
     * Returns the Document
     *
     * @return Model\Document\Email|Model\Document\Newsletter|null
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Prevents appending of debug information (used for resending emails)
     * @internal
     * @return \Pimcore\Mail
     */
    public function preventDebugInformationAppending()
    {
        $this->preventDebugInformationAppending = true;

        return $this;
    }

    /**
     * Returns, if debug information is not added
     * @internal
     * @return bool
     */
    public function isPreventingDebugInformationAppending()
    {
        return $this->preventDebugInformationAppending;
    }

    /**
     * @param string $htmlContent
     *
     * @return string
     */
    private function html2Text($htmlContent)
    {
        $content = '';

        if (!empty($htmlContent)) {
            try {
                $converter = new HtmlConverter();
                $converter->getConfig()->merge($this->getHtml2TextOptions());
                $content = $converter->convert($htmlContent);
            } catch (\Exception $e) {
                Logger::warning('Converting HTML to plain text failed, no plain text part will be attached to the sent email');
            }
        }

        return $content;
    }

    /**
     * @deprecated use text() instead. Will be removed in Pimcore 11
     * @param string $bodyText
     * @param string $charset
     *
     * @return $this
     */
    public function setBodyText($bodyText, string $charset = 'utf-8')
    {
        return $this->text($bodyText, $charset);
    }

    /**
     * @deprecated use html() instead. Will be removed in Pimcore 11
     * @param string $body
     * @param string $charset
     *
     * @return \Pimcore\Mail
     */
    public function setBodyHtml($body, string $charset = 'utf-8')
    {
        return $this->html($body, $charset);
    }

    /**
     * @internal
     * @return array
     */
    public function getOriginalData()
    {
        return $this->originalData;
    }

    /**
     * @internal
     * @param array $originalData
     */
    public function setOriginalData($originalData)
    {
        $this->originalData = $originalData;
    }

    /**
     * @deprecated use attach() instead. Will be removed in Pimcore 11
     * @param string $data
     * @param string|null $mimeType
     * @param string|null $filename
     *
     * @return $this
     */
    public function createAttachment($data, $mimeType = null, $filename = null)
    {
        return $this->attach($data, $filename, $mimeType);
    }

    /**
     * @return Model\Tool\Email\Log
     */
    public function getLastLogEntry()
    {
        return $this->lastLogEntry;
    }

    /**
     * Set the Content-type of this entity.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setContentType($type)
    {
        $this->getHeaders()->addTextHeader('Content-Type', $type);

        return $this;
    }

    /**
     * Set the subject of this message.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        return $this->subject($subject);
    }

    /**
     * format Address from old params(string $address, string $name)
     *
     * @param string|array $addresses
     *
     * @return array
     */
    private function formatAddress(...$addresses)
    {
        //old param style with string name as second param
        if (isset($addresses[1]) && is_string($addresses[1])) {
            return [ new Address($addresses[0], $addresses[1]) ];
        }

        return $addresses;
    }

    /**
     * {@inheritdoc}
     */
    public function addTo(...$addresses)
    {
        $addresses = $this->formatAddress(...$addresses);

        return parent::addTo(...$addresses);
    }

    /**
     * {@inheritdoc}
     */
    public function addCc(...$addresses)
    {
        $addresses = $this->formatAddress(...$addresses);

        return parent::addCc(...$addresses);
    }

    /**
     * {@inheritdoc}
     */
    public function addBcc(...$addresses)
    {
        $addresses = $this->formatAddress(...$addresses);

        return parent::addBcc(...$addresses);
    }

    /**
     * {@inheritdoc}
     */
    public function addFrom(...$addresses)
    {
        $addresses = $this->formatAddress(...$addresses);

        return parent::addFrom(...$addresses);
    }

    /**
     * {@inheritdoc}
     */
    public function addReplyTo(...$addresses)
    {
        $addresses = $this->formatAddress(...$addresses);

        return parent::addReplyTo(...$addresses);
    }
}
