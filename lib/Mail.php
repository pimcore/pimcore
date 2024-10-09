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

namespace Pimcore;

use Exception;
use League\HTMLToMarkdown\HtmlConverter;
use Pimcore;
use Pimcore\Event\MailEvents;
use Pimcore\Event\Model\MailEvent;
use Pimcore\Helper\Mail as MailHelper;
use Pimcore\Mail\Mailer;
use Pimcore\Tool\DomCrawler;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\MailboxListHeader;
use Symfony\Component\Mime\Part\AbstractPart;
use Twig\Sandbox\SecurityError;

class Mail extends Email
{
    private static bool $forceDebugMode = false;

    /**
     * If true - emails are logged in the database and on the file-system
     *
     */
    private bool $loggingEnable = true;

    /**
     * Contains the email document
     *
     */
    private Model\Document\Email|null $document = null;

    /**
     * Contains the email document Id
     */
    private ?int $documentId = null;

    /**
     * Contains the dynamic Params for the Twig engine
     *
     * @var mixed[]
     */
    private array $params = [];

    /**
     * Options passed to html2text
     *
     * @var array<string, mixed>
     */
    private array $html2textOptions = [
        'ignore_errors' => true,
    ];

    /**
     * Prevent adding debug information
     *
     */
    private bool $preventDebugInformationAppending = false;

    /**
     * if true - the Pimcore debug mode is ignored
     *
     */
    private bool $ignoreDebugMode = false;

    /**
     * if $hostUrl is set - this url well be used to create absolute urls
     * otherwise it is determined automatically
     *
     * @see MailHelper::setAbsolutePaths()
     *
     */
    private ?string $hostUrl = null;

    /**
     * if true: prevent setting the recipients from the Document - set in $this->clearRecipients()
     *
     */
    private bool $recipientsCleared = false;

    /**
     * place to store original data before modifying message when sending in debug mode
     *
     */
    private ?array $originalData = null;

    private Model\Tool\Email\Log $lastLogEntry;

    /**
     * @return $this
     */
    public function setHostUrl(string $url): static
    {
        $this->hostUrl = $url;

        return $this;
    }

    public function getHostUrl(): ?string
    {
        return $this->hostUrl;
    }

    /**
     * @param array|Headers|null $headers
     * @param AbstractPart|null $body
     */
    public function __construct($headers = null, $body = null, string $contentType = null)
    {
        if (is_array($headers)) {
            $options = $headers;

            $headers = ($options['headers'] ?? null) instanceof Headers ? $options['headers'] : null;
            $body = ($options['body'] ?? null) instanceof AbstractPart ? $options['body'] : null;
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
     * Initializes the mailer with the configured pimcore.email system settings
     *
     *
     * @internal
     */
    public function init(string $type = 'email', ?array $config = null): void
    {
        if (empty($config)) {
            $config = Config::getSystemConfiguration($type);
        }

        if (!empty($config['sender']['email']) && empty($this->getFrom())) {
            $this->from(new Address($config['sender']['email'], $config['sender']['name']));
        }

        if (!empty($config['return']['email']) && empty($this->getReplyTo())) {
            $this->replyTo(new Address($config['return']['email'], $config['return']['name']));
        }
    }

    /**
     * @return $this
     */
    public function setIgnoreDebugMode(bool $value): static
    {
        $this->ignoreDebugMode = $value;

        return $this;
    }

    /**
     * Checks if the Debug mode is ignored
     *
     */
    public function getIgnoreDebugMode(): bool
    {
        return $this->ignoreDebugMode;
    }

    /**
     * returns if redirecting to debug mail addresses should take place when sending the mail
     *
     * @internal
     *
     */
    public function doRedirectMailsToDebugMailAddresses(): bool
    {
        if (self::$forceDebugMode) {
            return true;
        }

        return Pimcore::inDebugMode() && $this->ignoreDebugMode === false;
    }

    /**
     * Sets options that are passed to html2text
     *
     *
     * @return $this
     */
    public function setHtml2TextOptions(array $options = []): static
    {
        $this->html2textOptions = $options;

        return $this;
    }

    /**
     * Returns options for html2text
     *
     * @return array<string, mixed>
     */
    public function getHtml2TextOptions(): array
    {
        return $this->html2textOptions;
    }

    /**
     * Clears list of recipient email addresses
     *
     * @return $this Provides fluent interface
     */
    public function clearRecipients(): static
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
     * @return $this Provides fluent interface
     */
    public function disableLogging(): static
    {
        $this->loggingEnable = false;

        return $this;
    }

    /**
     * Enables email logging (by default it's enabled)
     *
     * @return $this Provides fluent interface
     */
    public function enableLogging(): static
    {
        $this->loggingEnable = true;

        return $this;
    }

    /**
     * returns the logging status
     *
     */
    public function loggingIsEnabled(): bool
    {
        return $this->loggingEnable;
    }

    /**
     * Sets the parameters to the request object
     *
     *
     * @return $this Provides fluent interface
     */
    public function setParams(array $params): static
    {
        foreach ($params as $key => $value) {
            $this->setParam($key, $value);
        }

        return $this;
    }

    /**
     * Sets a single parameter to the request object
     *
     *
     * @return $this Provides fluent interface
     */
    public function setParam(int|string $key, mixed $value): static
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Returns the parameters which were set with "setParams" or "setParam"
     *
     * @return mixed[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Returns a parameter which was set with "setParams" or "setParam"
     *
     *
     */
    public function getParam(int|string $key): mixed
    {
        return $this->params[$key];
    }

    /**
     * Forces the debug mode - useful for cli-script which should not send emails to recipients
     *
     */
    public static function setForceDebugMode(bool $value): void
    {
        self::$forceDebugMode = $value;
    }

    /**
     * Deletes parameters which were set with "setParams" or "setParam"
     *
     *
     * @return $this Provides fluent interface
     */
    public function unsetParams(array $params): static
    {
        foreach ($params as $param) {
            $this->unsetParam($param);
        }

        return $this;
    }

    /**
     * Deletes a single parameter which was set with "setParams" or "setParam"
     *
     *
     * @return $this Provides fluent interface
     */
    public function unsetParam(int|string $key): static
    {
        unset($this->params[$key]);

        return $this;
    }

    /**
     * Sets the settings which are defined in the Document Settings (from,to,cc,bcc,replyTo)
     *
     * @return $this Provides fluent interface
     */
    private function setDocumentSettings(): static
    {
        $document = $this->getDocument();

        if ($document instanceof Model\Document\Email) {
            if (!$this->recipientsCleared) {
                $to = \Pimcore\Helper\Mail::parseEmailAddressField($document->getTo());
                foreach ($to as $toEntry) {
                    $this->addTo(new Address($toEntry['email'], $toEntry['name']));
                }

                $cc = \Pimcore\Helper\Mail::parseEmailAddressField($document->getCc());
                foreach ($cc as $ccEntry) {
                    $this->addCc(new Address($ccEntry['email'], $ccEntry['name']));
                }

                $bcc = \Pimcore\Helper\Mail::parseEmailAddressField($document->getBcc());
                foreach ($bcc as $bccEntry) {
                    $this->addBcc(new Address($bccEntry['email'], $bccEntry['name']));
                }

                $replyTo = \Pimcore\Helper\Mail::parseEmailAddressField($document->getReplyTo());
                foreach ($replyTo as $replyToEntry) {
                    $this->addReplyTo(new Address($replyToEntry['email'], $replyToEntry['name']));
                }
            }
        }

        if ($document instanceof Model\Document\Email) {
            //if more than one "from" email address is defined -> we set the first one
            $fromArray = \Pimcore\Helper\Mail::parseEmailAddressField($document->getFrom());
            if ($fromArray) {
                [$from] = $fromArray;
                $this->from(new Address($from['email'], $from['name']));
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
     *
     * @return $this Provides fluent interface
     */
    public function send(MailerInterface $mailer = null): static
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

        // Remove the document property because it is no longer needed and makes it difficult
        // to serialize the Mail object when using the Symfony Messenger component
        $document = $this->getDocument();
        if ($document instanceof Model\Document) {
            $this->setDocument(null);
            $this->setDocumentId($document->getId());
        }

        return $this->sendWithoutRendering($mailer);
    }

    /**
     * sends mail without (re)rendering the content.
     * see also comments of send() method
     *
     *
     * @return $this
     *
     * @throws Exception
     */
    public function sendWithoutRendering(MailerInterface $mailer = null): static
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

        $sendingFailedException = null;
        if ($mailer === null) {
            try {
                //if no mailer given, get default mailer from container
                $mailer = Pimcore::getContainer()->get(Mailer::class);
            } catch (Exception $e) {
                $sendingFailedException = $e;
            }
        }

        if (empty($this->getFrom()) && $hostname = Tool::getHostname()) {
            // set default "from" address
            $this->from('no-reply@' . $hostname);
        }

        $event = new MailEvent($this, [
            'mailer' => $mailer,
        ]);

        Pimcore::getEventDispatcher()->dispatch($event, MailEvents::PRE_SEND);

        if ($event->hasArgument('mailer') && !$sendingFailedException) {
            $mailer = $event->getArgument('mailer');

            try {
                $mailer->send($this);
            } catch (Exception $e) {
                $sendingFailedException = new Exception($e->getMessage(), 0, $e);
            }
        }

        if ($this->loggingIsEnabled()) {
            if (Pimcore::inDebugMode() && !$this->ignoreDebugMode) {
                $recipients = $this->getDebugMailRecipients($recipients);
            }

            Pimcore::getEventDispatcher()->dispatch($event, MailEvents::PRE_LOG);

            try {
                $this->lastLogEntry = MailHelper::logEmail($this, $recipients, $sendingFailedException === null ? null : $sendingFailedException->getMessage());
            } catch (Exception $e) {
                Logger::emerg("Couldn't log Email");
            }
        }

        if ($sendingFailedException instanceof Exception) {
            throw $sendingFailedException;
        }

        return $this;
    }

    /**
     * @param array<Address|string> $addresses
     *
     * @return array<Address|string>
     */
    private function filterLogAddresses(array $addresses): array
    {
        foreach ($addresses as $addrKey => $address) {
            if ($address instanceof Address) {
                // remove address if blocklisted
                if (Model\Tool\Email\Blocklist::getByAddress($address->getAddress())) {
                    unset($addrKey);
                }
            } else {
                // remove address if blocklisted
                if (Model\Tool\Email\Blocklist::getByAddress($addrKey)) {
                    unset($addresses[$addrKey]);
                }
            }
        }

        return $addresses;
    }

    /**
     * @param array<Address|string> $recipients
     *
     * @return array<Address|string>
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

    private function renderParams(string $string, string $context): string
    {
        $templatingEngine = Pimcore::getContainer()->get('pimcore.templating.engine.delegating');

        try {
            $twig = $templatingEngine->getTwigEnvironment(true);
            $template = $twig->createTemplate($string);

            return $template->render($this->getParams());
        } catch (SecurityError $e) {
            Logger::err((string) $e);

            throw new Exception(sprintf('Failed rendering the %s: %s. Please check your twig sandbox security policy or contact the administrator.',
                $context, substr($e->getMessage(), 0, strpos($e->getMessage(), ' in "__string'))));
        } finally {
            $templatingEngine->disableSandboxExtensionFromTwigEnvironment();
        }
    }

    /**
     * Renders the content (Twig) and returns the rendered subject
     *
     * @internal
     *
     */
    public function getSubjectRendered(): string
    {
        $subject = $this->getSubject();

        if (!$subject && $this->getDocument()) {
            $subject = $this->getDocument()->getSubject();
        }

        if ($subject) {
            return $this->renderParams($subject, 'subject');
        }

        return '';
    }

    /**
     * Renders the content (Twig) and returns the rendered HTML
     *
     * @internal
     *
     */
    public function getBodyHtmlRendered(): ?string
    {
        $html = $this->getHtmlBody();

        // if the content was manually set with $obj->setBody(); this content will be used
        // and not the content of the Document!
        if (!$html) {
            // render document
            if ($this->getDocument() instanceof Model\Document) {
                $attributes = $this->getParams();

                $html = Model\Document\Service::render($this->getDocument(), $attributes);
            }
        }

        $content = null;
        if ($html) {
            $content = $this->renderParams($html, 'body');

            // modifying the content e.g set absolute urls...
            $content = MailHelper::embedAndModifyCss($content, $this->getDocument());
            $content = MailHelper::setAbsolutePaths($content, $this->getDocument(), $this->getHostUrl());
        }

        return $content;
    }

    /**
     * Renders the content (Twig) and returns
     * the rendered text if a text was set with "$mail->text()"
     *
     * @internal
     *
     */
    public function getBodyTextRendered(): string
    {
        $text = $this->getTextBody();

        //if the content was manually set with $obj->text(); this content will be used
        if ($text) {
            $content = $this->renderParams($text, 'body');
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
            } catch (Exception $e) {
                Logger::err((string) $e);
                $content = '';
            }
        }

        return $content;
    }

    /**
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setDocument(int|Model\Document|string|null $document): static
    {
        if (!empty($document)) {
            if (is_numeric($document)) { //id of document passed
                $document = Model\Document\Email::getById($document);
            } elseif (is_string($document)) { //path of document passed
                $document = Model\Document\Email::getByPath($document);
            }
        }

        if ($document instanceof Model\Document\Email || $document === null) {
            $this->document = $document;
            $this->setDocumentId($document instanceof Model\Document ? $document->getId() : null);
            $this->setDocumentSettings();
        } else {
            throw new Exception("$document is not an instance of " . Model\Document\Email::class);
        }

        return $this;
    }

    /**
     * Returns the Document
     *
     */
    public function getDocument(): Model\Document\Email|null
    {
        return $this->document;
    }

    public function getDocumentId(): ?int
    {
        return $this->documentId;
    }

    public function setDocumentId(?int $documentId): void
    {
        $this->documentId = $documentId;
    }

    /**
     * Prevents appending of debug information (used for resending emails)
     *
     * @internal
     *
     * @return $this
     */
    public function preventDebugInformationAppending(): static
    {
        $this->preventDebugInformationAppending = true;

        return $this;
    }

    /**
     * Returns, if debug information is not added
     *
     * @internal
     *
     */
    public function isPreventingDebugInformationAppending(): bool
    {
        return $this->preventDebugInformationAppending;
    }

    private function html2Text(string $htmlContent): string
    {
        $content = '';

        if (!empty($htmlContent)) {
            try {
                $converter = new HtmlConverter();
                $converter->getConfig()->merge($this->getHtml2TextOptions());
                $content = $converter->convert($htmlContent);
            } catch (Exception $e) {
                Logger::warning('Converting HTML to plain text failed, no plain text part will be attached to the sent email');
            }
        }

        return $content;
    }

    /**
     * @internal
     *
     */
    public function getOriginalData(): ?array
    {
        return $this->originalData;
    }

    /**
     *
     * @internal
     */
    public function setOriginalData(?array $originalData): void
    {
        $this->originalData = $originalData;
    }

    public function getLastLogEntry(): Model\Tool\Email\Log
    {
        return $this->lastLogEntry;
    }

    /**
     * Set the Content-type of this entity.
     *
     *
     * @return $this
     */
    public function setContentType(string $type): static
    {
        $this->getHeaders()->addTextHeader('Content-Type', $type);

        return $this;
    }

    /**
     * @param Address|string|string[] ...$addresses
     *
     * @return array<Address|string>
     */
    private function formatAddress(Address|string|array ...$addresses): array
    {
        //old param style with string name as second param
        if (isset($addresses[1]) && is_string($addresses[1])) {
            return [new Address($addresses[0], $addresses[1])];
        }

        return $addresses;
    }

    /**
     *
     *
     * @return $this
     */
    public function addTo(Address|string ...$addresses): static
    {
        $addresses = $this->formatAddress(...$addresses);

        return parent::addTo(...$addresses);
    }

    /**
     *
     *
     * @return $this
     */
    public function addCc(Address|string ...$addresses): static
    {
        $addresses = $this->formatAddress(...$addresses);

        return parent::addCc(...$addresses);
    }

    /**
     *
     *
     * @return $this
     */
    public function addBcc(Address|string ...$addresses): static
    {
        $addresses = $this->formatAddress(...$addresses);

        return parent::addBcc(...$addresses);
    }

    /**
     *
     *
     * @return $this
     */
    public function addFrom(Address|string ...$addresses): static
    {
        $addresses = $this->formatAddress(...$addresses);

        return parent::addFrom(...$addresses);
    }

    /**
     *
     *
     * @return $this
     */
    public function addReplyTo(Address|string ...$addresses): static
    {
        $addresses = $this->formatAddress(...$addresses);

        return parent::addReplyTo(...$addresses);
    }
}
