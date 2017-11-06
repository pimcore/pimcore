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

namespace Pimcore;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Pimcore\Helper\Mail as MailHelper;

class Mail extends \Swift_Message
{
    /**
     * @var bool
     */
    protected static $forceDebugMode = false;

    /**
     * Contains the debug email addresses from settings -> system -> Email Settings -> Debug email addresses
     *
     * @var array
     * @static
     */
    protected static $debugEmailAddresses = [];

    /**
     * @var Placeholder
     */
    protected $placeholderObject;

    /**
     * If true - emails are logged in the database and on the file-system
     *
     * @var bool
     */
    protected $loggingEnable = true;

    /**
     * Contains the email document
     *
     * @var Model\Document\Email
     */
    protected $document;

    /**
     * Contains the dynamic Params for the Placeholders
     *
     * @var array
     */
    protected $params = [];

    /**
     * html2text from mbayer is installed (http://www.mbayer.de/html2text/)
     *
     * @var bool
     */
    protected static $html2textInstalled = null;

    /**
     * Options passed to html2text
     *
     * @var string
     */
    protected $html2textOptions = '';

    /**
     * use html2text from mbayer if it is installed (http://www.mbayer.de/html2text/)
     *
     * @var bool
     */
    protected $html2textBinaryEnabled = null;

    /**
     * Prevent adding debug information
     *
     * @var bool
     */
    protected $preventDebugInformationAppending = false;

    /**
     * if true - the Pimcore debug mode is ignored
     *
     * @var bool
     */
    protected $ignoreDebugMode = false;

    /**
     * if true - the layout is enabled when document is rendered to a string
     *
     * @var bool
     */
    protected $enableLayoutOnPlaceholderRendering = true;

    /**
     * forces the mail class to always us the "Pimcore Mode",
     * so you don't have to set the charset every time when you create new Pimcore_Mail instance
     *
     * @var bool
     */
    public static $forcePimcoreMode = false;

    /**
     * if $hostUrl is set - this url well be used to create absolute urls
     * otherwise it is determined automatically
     *
     * @see MailHelper::setAbsolutePaths()
     *
     * @var null
     */
    protected $hostUrl = null;

    /**
     * if true: prevent setting the recipients from the Document - set in $this->clearRecipients()
     *
     * @var bool
     */
    protected $recipientsCleared = false;

    /**
     * body plain text
     *
     * @var string
     */
    protected $bodyText;

    /**
     * plain text mime part
     * this is created and attached to mail on send
     *
     * @var \Swift_MimePart
     */
    protected $bodyTextMimePart;

    /**
     * place to store original data before modifying message when sending in debug mode
     *
     * @var array
     */
    protected $originalData;

    /**
     * @var Model\Tool\Email\Log
     */
    protected $lastLogEntry;

    /**
     * @param $url
     *
     * @return $this
     */
    public function setHostUrl($url)
    {
        $this->hostUrl = $url;

        return $this;
    }

    /**
     * @return null
     */
    public function getHostUrl()
    {
        return $this->hostUrl;
    }

    /**
     * Mail constructor.
     *
     * @param null $subject
     * @param null $body
     * @param null $contentType
     * @param null $charset
     */
    public function __construct($subject = null, $body = null, $contentType = null, $charset = null)
    {
        // using $charset as param to be compatible with old Pimcore Mail
        if (is_array($subject) || self::$forcePimcoreMode) {
            $options = $subject;

            parent::__construct($options['subject'], $body, $contentType, $options['charset'] ? $options['charset'] : 'UTF-8');

            if ($options['document']) {
                $this->setDocument($options['document']);
            }
            if ($options['params']) {
                $this->setParams($options['params']);
            }
            if ($options['hostUrl']) {
                $this->setHostUrl($options['hostUrl']);
            }
        } else {
            parent::__construct($subject, $body, $contentType, ($charset !== null ? $charset : 'UTF-8'));
        }

        $this->init();
    }

    /**
     * Initializes the mailer with the settings form Settings -> System -> Email Settings
     *
     * @param string $type
     */
    public function init($type = 'email')
    {
        $systemConfig = \Pimcore\Config::getSystemConfig()->toArray();
        $emailSettings =& $systemConfig[$type];

        if ($emailSettings['sender']['email']) {
            $this->setFrom($emailSettings['sender']['email'], $emailSettings['sender']['name']);
        }

        if ($emailSettings['return']['email']) {
            $this->setReplyTo($emailSettings['return']['email'], $emailSettings['return']['name']);
        }

        $this->placeholderObject = new \Pimcore\Placeholder();
    }

    /**
     * @param $value
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
     *
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
     * @param $value
     *
     * @return $this
     */
    public function setEnableLayoutOnPlaceholderRendering($value)
    {
        $this->enableLayoutOnPlaceholderRendering = (bool)$value;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEnableLayoutOnPlaceholderRendering()
    {
        return $this->enableLayoutOnPlaceholderRendering;
    }

    /**
     * Determines if mbayer html2text is installed (more information at http://www.mbayer.de/html2text/)
     * and uses it to automatically create a text version of the html email
     *
     * @static
     *
     * @return bool
     */
    public static function determineHtml2TextIsInstalled()
    {
        return (bool) \Pimcore\Tool\Console::getExecutable('html2text');
    }

    /**
     * Sets options that are passed to html2text
     *
     * @param string $options
     *
     * @return \Pimcore\Mail
     */
    public function setHtml2TextOptions($options = '')
    {
        if (is_string($options)) {
            $this->html2textOptions = $options;
        } else {
            Logger::warn('Html2Text options ignored. You have to pass a string');
        }

        return $this;
    }

    /**
     * Returns options for html2text
     *
     * @return string
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

        $this->getHeaders()->removeAll('to');
        $this->getHeaders()->removeAll('cc');
        $this->getHeaders()->removeAll('bcc');
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
     * Sets the parameters for the email view and the Placeholders
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
     * Sets a single parameter for the email view and the Placeholders
     *
     * @param string | int $key
     * @param mixed $value
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function setParam($key, $value)
    {
        if (is_string($key) || is_integer($key)) {
            $this->params[$key] = $value;
        } else {
            Logger::warn('$key has to be a string - Param ignored!');
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
     * @param string | integer $key
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
     * @param string | integer $key
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function unsetParam($key)
    {
        if (is_string($key) || is_integer($key)) {
            unset($this->params[$key]);
        } else {
            Logger::warn('$key has to be a string - unsetParam ignored!');
        }

        return $this;
    }

    /**
     * Sets the settings which are defined in the Document Settings (from,to,cc,bcc)
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    protected function setDocumentSettings()
    {
        $document = $this->getDocument();

        if ($document instanceof Model\Document\Email) {
            if (!$this->recipientsCleared) {
                $to = $document->getToAsArray();
                if (!empty($to)) {
                    foreach ($to as $toEntry) {
                        $this->addTo($toEntry);
                    }
                }

                $cc = $document->getCcAsArray();
                if (!empty($cc)) {
                    foreach ($cc as $ccEntry) {
                        $this->addCc($ccEntry);
                    }
                }

                $bcc = $document->getBccAsArray();
                if (!empty($bcc)) {
                    foreach ($bcc as $bccEntry) {
                        $this->addBcc($bccEntry);
                    }
                }
            }
        }

        if ($document instanceof Model\Document\Email || $document instanceof Model\Document\Newsletter) {
            //if more than one "from" email address is defined -> we set the first one
            $fromArray = $document->getFromAsArray();
            if (!empty($fromArray)) {
                list($from) = $fromArray;
                if ($from) {
                    $fromAddress = $from;
                    $fromName = null;

                    if (preg_match('/(.*)<(.*)>/', $from, $matches)) {
                        $fromAddress = trim($matches[2]);
                        $fromName = trim($matches[1]);
                    }

                    $this->setFrom($fromAddress, $fromName);
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
     * @param  \Swift_Mailer $mailer
     *
     * @return \Pimcore\Mail Provides fluent interface
     */
    public function send(\Swift_Mailer $mailer = null)
    {
        $this->setSubject($this->getSubjectRendered());

        $bodyHtmlRendered = $this->getBodyHtmlRendered();
        if ($bodyHtmlRendered) {
            $this->setBody($bodyHtmlRendered, 'text/html');
        }

        if ($this->bodyTextMimePart) {
            $this->detach($this->bodyTextMimePart);
        }
        $bodyTextRendered = $this->getBodyTextRendered();
        if ($bodyTextRendered) {
            //create mime part for plain text body
            $this->bodyTextMimePart = \Swift_MimePart::newInstance($bodyTextRendered, 'text/plain');
            $this->attach($this->bodyTextMimePart);
        }

        return $this->sendWithoutRendering($mailer);
    }

    /**
     * sends mail without (re)rendering the content.
     * see also comments of send() method
     *
     * @param \Swift_Mailer $mailer
     *
     * @return \Pimcore\Mail
     */
    public function sendWithoutRendering(\Swift_Mailer $mailer = null)
    {
        // filter email addresses

        // preserve email addresses, see Swift_Transport_MailTransport::send lines 140ff :-(
        // ... Remove headers that would otherwise be duplicated
        // $message->getHeaders()->remove('To');
        // $message->getHeaders()->remove('Subject'); ....

        $recipients = [];

        foreach (['To', 'Cc', 'Bcc'] as $key) {
            $recipients[$key] = null;
            $getterName = 'get' . $key;
            $setterName = 'set' . $key;
            $addresses = $this->$getterName();

            if ($addresses) {
                $addresses = $this->filterLogAddresses($addresses);
            }

            $this->$setterName($addresses);

            $addresses = $this->$getterName();
            $recipients[$key] = $addresses;
        }

        if ($mailer == null) {
            //if no mailer given, get default mailer from container
            $mailer = \Pimcore::getContainer()->get('mailer');
        }

        $result = $mailer->send($this);

        if ($this->loggingIsEnabled()) {
            if (\Pimcore::inDebugMode()) {
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

    private function filterLogAddresses(array $addresses): array
    {
        foreach (array_keys($addresses) as $address) {
            // remove address if blacklisted
            if (Model\Tool\Email\Blacklist::getByAddress($address)) {
                unset($addresses[$address]);
            }
        }

        return $addresses;
    }

    private function getDebugMailRecipients(array $recipients): array
    {
        $headers = $this->getHeaders();

        foreach (['To', 'Cc', 'Bcc'] as $key) {
            $recipients[$key] = null;

            $headerName = 'X-Pimcore-Debug-' . $key;
            if ($headers->has($headerName)) {
                /** @var \Swift_Mime_Headers_MailboxHeader $header */
                $header = $headers->get($headerName);
                $recipients[$key] = $header->getNameAddresses();

                $headers->remove($headerName);
            }

            if ($recipients[$key]) {
                $recipients[$key] = $this->filterLogAddresses($recipients[$key]);
            }
        }

        return $recipients;
    }

    /**
     * Static helper to validate a email address
     *
     * @static
     *
     * @param $emailAddress
     *
     * @return bool
     */
    public static function isValidEmailAddress($emailAddress)
    {
        $validator = new EmailValidator();

        return $validator->isValid($emailAddress, new RFCValidation());
    }

    /**
     * Replaces the placeholders with the content and returns the rendered Subject
     *
     * @return string
     */
    public function getSubjectRendered()
    {
        $subject = $this->getSubject();

        if (!$subject && $this->getDocument()) {
            $subject = $this->getDocument()->getSubject();
        }

        return $this->placeholderObject->replacePlaceholders($subject, $this->getParams(), $this->getDocument(), $this->getEnableLayoutOnPlaceholderRendering());
    }

    /**
     * Replaces the placeholders with the content and returns the rendered Html
     *
     * @return string|null
     */
    public function getBodyHtmlRendered()
    {
        $html = $this->getBody();

        // if the content was manually set with $obj->setBody(); this content will be used
        // and not the content of the Document!
        if (!$html) {
            // render document
            if ($this->getDocument() instanceof Model\Document) {
                $attributes = $this->getParams();

                $html = Model\Document\Service::render($this->getDocument(), $attributes, $this->getEnableLayoutOnPlaceholderRendering());
            }
        }

        $content = null;
        if ($html) {
            $content = $this->placeholderObject->replacePlaceholders($html, $this->getParams(), $this->getDocument());

            // modifying the content e.g set absolute urls...
            $content = MailHelper::embedAndModifyCss($content, $this->getDocument());
            $content = MailHelper::setAbsolutePaths($content, $this->getDocument(), $this->getHostUrl());
        }

        return $content;
    }

    /**
     * Replaces the placeholders with the content and returns
     * the rendered text if a text was set with "$mail->setBodyText()"     *
     *
     * @return string
     */
    public function getBodyTextRendered()
    {
        $text = $this->getBodyText();

        //if the content was manually set with $obj->setBodyText(); this content will be used
        if ($text) {
            $content = $this->placeholderObject->replacePlaceholders($text, $this->getParams(), $this->getDocument(), $this->getEnableLayoutOnPlaceholderRendering());
        } else {
            //creating text version from html email if html2text is installed
            try {
                include_once(PIMCORE_PATH . '/lib/simple_html_dom.php');

                $htmlContent = $this->getBodyHtmlRendered();
                $html = str_get_html($htmlContent);
                if ($html) {
                    $body = $html->find('body', 0);
                    if ($body) {
                        $style = $body->find('style', 0);
                        if ($style) {
                            $style->clear();
                        }
                        $htmlContent = $body->innertext;
                    }

                    $html->clear();
                    unset($html);
                }
                $content = $this->html2Text($htmlContent);
            } catch (\Exception $e) {
                Logger::err($e);
                $content = '';
            }
        }

        return $content;
    }

    /**
     * @param $document
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setDocument($document)
    {
        if ($document instanceof Model\Document) { //document passed
            $this->document = $document;
            $this->setDocumentSettings();
        } elseif ((int)$document > 0) { //id of document passed
            $this->setDocument(Model\Document::getById($document));
        } elseif (is_string($document) && $document != '') { //path of document passed
            $this->setDocument(Model\Document::getByPath($document));
        } else {
            throw new \Exception("$document is not an instance of \\Document\\Email or at least \\Document");
        }

        return $this;
    }

    /**
     * Returns the Document
     *
     * @return Model\Document\Email | null
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Prevents appending of debug information (used for resending emails)
     *
     * @return \Pimcore\Mail
     */
    public function preventDebugInformationAppending()
    {
        $this->preventDebugInformationAppending = true;

        return $this;
    }

    /**
     * Returns, if debug information is not added
     *
     * @return bool
     */
    public function isPreventingDebugInformationAppending()
    {
        return $this->preventDebugInformationAppending;
    }

    /**
     *
     * @return bool
     */
    public function getHtml2TextBinaryEnabled()
    {
        return $this->html2textBinaryEnabled;
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function enableHtml2textBinary()
    {
        if (self::getHtml2textInstalled()) {
            $this->html2textBinaryEnabled = true;
        } else {
            throw new \Exception('trying to enable html2text binary,
            but html2text is not installed!');
        }

        return $this;
    }

    /**
     * @static
     * returns  html2text binary installation status
     *
     * @return bool || null
     */
    public static function getHtml2textInstalled()
    {
        if (is_null(self::$html2textInstalled)) {
            self::$html2textInstalled = self::determineHtml2TextIsInstalled();
        }

        return self::$html2textInstalled;
    }

    /**
     * @param $htmlContent
     *
     * @return string
     */
    protected function html2Text($htmlContent)
    {
        if ($this->getHtml2TextBinaryEnabled()) {
            $content = '';
            //html2text doesn't support unicode
            if ($this->getCharset() == 'UTF-8') {
                $htmlContent = utf8_decode($htmlContent);
            }
            //using temporary file so we don't have problems with special characters
            $tmpFileName = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . uniqid('email_', true) . '.tmp';
            if (\Pimcore\File::put($tmpFileName, $htmlContent)) {
                $content = @shell_exec("html2text $tmpFileName " . $this->getHtml2TextOptions());
                @unlink($tmpFileName);
            }

            return $content;
        }

        return '';
    }

    /**
     * @return string
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * @param $bodyText
     *
     * @return $this
     */
    public function setBodyText($bodyText)
    {
        $this->bodyText = $bodyText;

        return $this;
    }

    /**
     * @param $body
     *
     * @return \Pimcore\Mail
     */
    public function setBodyHtml($body)
    {
        return $this->setBody($body, 'text/html');
    }

    /**
     * @return \Swift_MimePart
     */
    public function getBodyTextMimePart()
    {
        return $this->bodyTextMimePart;
    }

    /**
     * @return array
     */
    public function getOriginalData()
    {
        return $this->originalData;
    }

    /**
     * @param array $originalData
     */
    public function setOriginalData($originalData)
    {
        $this->originalData = $originalData;
    }

    /**
     * @param \Swift_Mime_Attachment $attachment
     *
     * @return $this
     */
    public function addAttachment(\Swift_Mime_Attachment $attachment)
    {
        return $this->attach($attachment);
    }

    /**
     *
     *
     * @param $data
     * @param null $mimeType
     * @param null $disposition
     * @param null $filename
     *
     * @return \Swift_Mime_Attachment
     */
    public function createAttachment($data, $mimeType = null, $filename = null, $disposition = null)
    {
        $attachment = \Swift_Attachment::newInstance($data, $filename, $mimeType);
        if ($disposition) {
            $attachment->setDisposition($disposition);
        }
        $this->addAttachment($attachment);

        return $attachment;
    }

    /**
     * @inheritdoc
     */
    public function addTo($address, $name = null)
    {
        if (is_array($address)) {
            foreach ($address as $item) {
                parent::addTo($item, $name);
            }
        } else {
            parent::addTo($address, $name);
        }
    }

    /**
     * @return Model\Tool\Email\Log
     */
    public function getLastLogEntry()
    {
        return $this->lastLogEntry;
    }
}
