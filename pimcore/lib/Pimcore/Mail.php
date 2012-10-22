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

class Pimcore_Mail extends Zend_Mail
{

    /**
     * Contains the debug email addresses from settings -> system -> Email Settings -> Debug email addresses
     *
     * @var array
     * @static
     */
    protected static $debugEmailAddresses = array();

    /**
     * @var object Pimcore_Placeholder
     */
    protected $placeholderObject;

    /**
     * Contains data that has to be stored temporary e.g. email receivers for logging
     *
     * @var array
     */
    protected $temporaryStorage = array();

    /**
     * If true - emails are logged in the database and on the file-system
     *
     * @var bool
     */
    protected $loggingEnable = true;

    /**
     * Contains the email document
     *
     * @var object Document_Email
     */
    protected $document;

    /**
     * Contains the dynamic Params for the Placeholders
     *
     * @var array
     */
    protected $params = array();

    /**
     * Prevent adding debug information
     *
     * @var bool
     */
    protected $preventDebugInformationAppending = false;

    /**
     * if true - the Pimcore debug mode is ignored
     * @var bool
     */
    protected $ignoreDebugMode = false;

    /**
     * if true - the layout is enabled when document is rendered to a string
     * @var bool
     */
    protected $enableLayoutOnPlaceholderRendering = true;

    /**
     * forces the mail class to always us the "Pimcore Mode",
     * so you don't have to set the charset every time when you create new Pimcore_Mail instance
     * @var bool
     */
    public static $forcePimcoreMode = false;

    /**
     * if $hostUrl is set - this url well be used to create absolute urls
     * otherwise it is determined automatically
     * @see Pimcore_Helper_Mail::setAbsolutePaths()
     *
     * @var null
     */
    protected $hostUrl = null;

    public function setHostUrl($url){
        $this->hostUrl = $url;
    }

    public function getHostUrl(){
        return $this->hostUrl;
    }

    /**
     * Creates a new Pimcore_Mail object (extends Zend_Mail)
     *
     * @param array $options
     */
    public function __construct($charset = null)
    {
        // using $charset as param to be compatible with Zend_Mail
        if(is_array($charset) || self::$forcePimcoreMode) {
            $options = $charset;
            parent::__construct($options["charset"] ? $options["charset"] : "UTF-8");

            if ($options["document"]) {
                $this->setDocument($options["document"]);
            }
            if ($options['params']) {
                $this->setParams($options['params']);
            }
            if ($options['subject']) {
                $this->setSubject($options['subject']);
            }
            if ($options['hostUrl']) {
                $this->setHostUrl($options['hostUrl']);
            }
        } else {
            if($charset === null) {
                $charset = "UTF-8";
            }
            parent::__construct($charset);
        }

        $this->init();
    }


    /**
     * Initializes the mailer with the settings form Settings -> System -> Email Settings
     *
     * @return void
     */
    protected function init()
    {
        $systemConfig = Pimcore_Config::getSystemConfig()->toArray();
        $emailSettings =& $systemConfig['email'];

        if ($emailSettings['sender']['email']) {
            $this->setDefaultFrom($emailSettings['sender']['email'], $emailSettings['sender']['name']);
        }

        if ($emailSettings['return']['email']) {
            $this->setDefaultReplyTo($emailSettings['return']['email'], $emailSettings['return']['name']);
        }

        if ($emailSettings['method'] == "smtp") {

            $config = array();
            if ($emailSettings['smtp']['name']) {
                $config['name'] = $emailSettings['smtp']['name'];
            }
            if ($emailSettings['smtp']['ssl']) {
                $config['ssl'] = $emailSettings['smtp']['ssl'];
            }
            if ($emailSettings['smtp']['port']) {
                $config['port'] = $emailSettings['smtp']['port'];
            }
            if ($emailSettings['smtp']['auth']['method']) {
                $config['auth'] = $emailSettings['smtp']['auth']['method'];
                $config['username'] = $emailSettings['smtp']['auth']['username'];
                $config['password'] = $emailSettings['smtp']['auth']['password'];
            }

            $transport = new Zend_Mail_Transport_Smtp($emailSettings['smtp']['host'], $config);
            $this->setDefaultTransport($transport);
        }

        //setting debug email addresses
        if (empty(self::$debugEmailAddresses)) {
            if ($emailSettings['debug']['emailaddresses']) {
                foreach (explode(',', $emailSettings['debug']['emailaddresses']) as $emailAddress) {
                    self::$debugEmailAddresses[] = $emailAddress;
                }
            }
        }

        $this->placeholderObject = new Pimcore_Placeholder();
    }

    /**
     * To ignore the Pimcore debug mode
     *
     * @param bool $value
     */
    public function setIgnoreDebugMode($value){
        $this->ignoreDebugMode = (bool)$value;
    }

    /**
     * Checks if the Debug mode is ignored
     *
     * @return bool
     */
    public function getIgnoreDebugMode(){
        return $this->ignoreDebugMode;
    }


    /**
     * activate / deactivate the layout when the document is rendered
     * to a string when the placeholders are replaced
     *
     * @param $value bool
     */
    public function setEnableLayoutOnPlaceholderRendering($value){
        $this->enableLayoutOnPlaceholderRendering = (bool)$value;
    }

    /**
     * @return bool
     */
    public function getEnableLayoutOnPlaceholderRendering(){
        return $this->enableLayoutOnPlaceholderRendering;
    }

    // overwriting Zend_Mail methods - necessary for logging... - start

    /**
     * Adds To-header and recipient, $email can be an array, or a single string address
     * Additionally adds recipients to temporary storage
     *
     * @param  string|array $email
     * @param  string $name
     * @return Pimcore_Mail Provides fluent interface
     */
    public function addTo($email, $name = '')
    {
        $this->addToTemporaryStorage('To', $email, $name);
        return parent::addTo($email, $name);
    }

    /**
     * Adds Cc-header and recipient, $email can be an array, or a single string address
     * Additionally adds recipients to temporary storage
     *
     * @param  string|array    $email
     * @param  string    $name
     * @return Pimcore_Mail Provides fluent interface
     */
    public function addCc($email, $name = '')
    {
        $this->addToTemporaryStorage('Cc', $email, $name);
        return parent::addCc($email, $name);
    }

    /**
     * Adds Bcc recipient, $email can be an array, or a single string address
     * Additionally adds recipients to temporary storage
     *
     * @param  string|array    $email
     * @return Pimcore_Mail Provides fluent interface
     */
    public function addBcc($email)
    {
        $this->addToTemporaryStorage('Bcc', $email, '');
        return parent::addBcc($email);
    }

    /**
     * Clears list of recipient email addresses
     * and resets the temporary storage
     *
     * @return Pimcore_Mail Provides fluent interface
     */
    public function clearRecipients()
    {
        unset($this->temporaryStorage['To']);
        unset($this->temporaryStorage['Cc']);
        unset($this->temporaryStorage['Bcc']);
        return parent::clearRecipients();
    }

    // overwriting Zend_Mail methods - end

    /**
     * Helper to add receivers to the temporary storage
     *
     * @param string $key
     * @param string | array $email
     * @param string $name
     */
    protected function addToTemporaryStorage($key, $email, $name)
    {
        if (!is_array($email)) {
            $email = array($name => $email);
        }
        foreach ($email as $n => $recipient) {
            $this->temporaryStorage[$key][] = array('email' => $recipient, 'name' => is_int($n) ? '' : $n);
        }
    }

    /**
     * Returns the temporary storage
     *
     * @return array
     */
    public function getTemporaryStorage()
    {
        return $this->temporaryStorage;
    }

    /**
     * Disables email logging
     *
     * @return Pimcore_Mail Provides fluent interface
     */
    public function disableLogging()
    {
        $this->loggingEnable = false;
        return $this;
    }

    /**
     * Enables email logging (by default it's enabled)
     *
     * @return Pimcore_Mail Provides fluent interface
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
     * @return Pimcore_Mail Provides fluent interface
     */
    public function setParams(Array $params)
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
     * @return Pimcore_Mail Provides fluent interface
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
     * @return mixed
     */
    public function getParam($key)
    {
        return $this->params[$key];
    }

    /**
     * Deletes parameters which were set with "setParams" or "setParam"
     *
     * @param array $params
     * @return Pimcore_Mail Provides fluent interface
     */
    public function unsetParams(Array $params)
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
     * @return Pimcore_Mail Provides fluent interface
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
     * @return Pimcore_Mail Provides fluent interface
     */
    protected function setDocumentSettings()
    {
        $document = $this->getDocument();

        if ($document instanceof Document_Email) {

            $to = $document->getToAsArray();
            if (!empty($to)) {
                $this->addTo($to);
            }

            $cc = $document->getCcAsArray();
            if (!empty($cc)) {
                $this->addCc($cc);
            }

            $bcc = $document->getBccAsArray();
            if (!empty($bcc)) {
                $this->addBcc($bcc);
            }

            //if more than one "from" email address is defined -> we set the first one
            list($from) = $document->getFromAsArray();
            if ($from) {
                $this->clearFrom();
                $this->setFrom($from);
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
     * @param  Zend_Mail_Transport_Abstract $transport
     * @return Pimcore_Mail Provides fluent interface
     */
    public function send($transport = null)
    {
        if ($this->getDocument()) {
            $this->setDocumentSettings();
        }

        $this->setSubject($this->getSubjectRendered());

        if($this->getBodyHtmlRendered()){
            $this->setBodyHtml($this->getBodyHtmlRendered());
        }

        if($this->getBodyTextRendered()){
            $this->setBodyText($this->getBodyTextRendered());
        }

        if($this->ignoreDebugMode == false){
            $this->checkDebugMode();
        }

        $result = parent::send($transport);

        if ($this->loggingIsEnabled() && $this->getDocument()) {
            try {
                Pimcore_Helper_Mail::logEmail($this);
            } catch (Exception $e) {
                Logger::emerg("Couldn't log Email");
            }
        }

        return $result;
    }


    /**
     * Checks if the debug mode is enabled in "Settings" -> "System" -> "Debug"
     * If the debug mode is enabled, all emails will be sent to the debug email addresses given the system settings
     * and the debug information is appended
     *
     * @return void
     */
    protected function checkDebugMode()
    {
        if (Pimcore::inDebugMode()) {
            if (empty(self::$debugEmailAddresses)) {
                throw new Exception('No valid debug email address given in "Settings" -> "System" -> "Email Settings"');
            }

            if($this->preventDebugInformationAppending != true){
                //adding the debug information to the html email
                $html = $this->getBodyHtml();
                if ($html instanceof Zend_Mime_Part) {
                        $rawHtml = $html->getRawContent();

                        $debugInformation = Pimcore_Helper_Mail::getDebugInformation('html', $this);
                        $debugInformationStyling = Pimcore_Helper_Mail::getDebugInformationCssStyle();

                        $rawHtml = preg_replace("!(</\s*body\s*>)!is", "$debugInformation\\1", $rawHtml);
                        $rawHtml = preg_replace("!(<\s*head\s*>)!is", "\\1$debugInformationStyling", $rawHtml);


                        $this->setBodyHtml($rawHtml);
                }

                $text = $this->getBodyText();

                if($text instanceof Zend_Mime_Part){
                        $rawText = $text->getRawContent();
                        $debugInformation = Pimcore_Helper_Mail::getDebugInformation('text',$this);
                        $rawText .= $debugInformation;
                        $this->setBodyText($rawText);
                }

                //setting debug subject
                $subject = $this->getSubject();
                $this->clearSubject();
                $this->setSubject('Debug email: ' . $subject);
            }
            $this->clearRecipients();
            $this->addTo(self::$debugEmailAddresses);
        }
    }

    /**
     * Static helper to validate a email address
     *
     * @static
     * @param $emailAddress
     * @return bool
     */
    public static function isValidEmailAddress($emailAddress)
    {
        $validator = new Zend_Validate_EmailAddress();
        return $validator->isValid($emailAddress);
    }


    /**
     * Replaces the placeholders with the content and returns the rendered Subject
     *
     * @return string
     */
    public function getSubjectRendered()
    {
        $subject = $this->getSubject();
        $this->clearSubject();

        if (!$subject && $this->getDocument()) {
            $subject = $this->getDocument()->getSubject();
        }
        return $this->placeholderObject->replacePlaceholders($subject, $this->getParams(), $this->getDocument(),$this->getEnableLayoutOnPlaceholderRendering());
    }


    /**
     * Replaces the placeholders with the content and returns the rendered Html
     *
     * @return string|null
     */
    public function getBodyHtmlRendered()
    {
        $html = $this->getBodyHtml();

        //if the content was manually set with $obj->setBodyHtml(); this content will be used
        //and not the content of the Document!
        if ($html instanceof Zend_Mime_Part) {
            $rawHtml = $html->getRawContent();
            $content = $this->placeholderObject->replacePlaceholders($rawHtml, $this->getParams(), $this->getDocument(),$this->getEnableLayoutOnPlaceholderRendering());
        } elseif ($this->getDocument() instanceof Document) {
            $content = $this->placeholderObject->replacePlaceholders($this->getDocument(), $this->getParams(), $this->getDocument(),$this->getEnableLayoutOnPlaceholderRendering());
        } else {
            $content = null;
        }

        //modifying the content e.g set absolute urls...
        if ($content) {
            $content = Pimcore_Helper_Mail::embedAndModifyCss($content, $this->getDocument());
            $content = Pimcore_Helper_Mail::setAbsolutePaths($content, $this->getDocument(), $this->getHostUrl());
        }

        return $content;
    }

    /**
     * Replaces the placeholders with the content and returns
     * the rendered text if a text was set with "$mail->setBodyText()"     *
     * @return string
     */
    public function getBodyTextRendered()
    {
        $text = $this->getBodyText();

        //if the content was manually set with $obj->setBodyText(); this content will be used
        if ($text instanceof Zend_Mime_Part) {
            $rawText = $text->getRawContent();
            $content = $this->placeholderObject->replacePlaceholders($rawText, $this->getParams(), $this->getDocument(),$this->getEnableLayoutOnPlaceholderRendering());
        } else {
            //creating text version from html email if html2text is installed
            try {
                include_once("simple_html_dom.php");
                include_once("html2text.php");

                $htmlContent = $this->getBodyHtmlRendered();
                $html = str_get_html($htmlContent);
                if($html) {
                    $body = $html->find("body",0);
                    if($body) {
                        $htmlContent = $body->innertext;
                    }
                }

                $content = @html2text($htmlContent);
            } catch (Exception $e) {
                Logger::err($e);
                $content = "";
            }
        }

        return $content;
    }


    /**
     * Sets the email document
     *
     * @param Document_Email $document
     * @throws Exception
     */
    public function setDocument($document)
    {
        if ($document instanceof Document) { //document passed
            $this->document = $document;
        } elseif ((int)$document > 0) { //id of document passed
            $this->setDocument(Document::getById($document));
        } elseif (is_string($document) && $document != "") { //path of document passed
            $this->setDocument(Document::getByPath($document));
        } else {
            throw new Exception('$document is not an instance of Document_Email or at least Document');
        }
        return $this;
    }

    /**
     * Returns the Document
     *
     * @return Document_Email | null
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Prevents appending of debug information (used for resending emails)
     *
     * @return Pimcore_Mail
     */
    public function preventDebugInformationAppending(){
        $this->preventDebugInformationAppending = true;
        return $this;
    }
}