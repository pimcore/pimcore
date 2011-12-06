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

class Pimcore_Mail extends Zend_Mail {

    /**
     * Contains the debug email receiver
     *
     * @var string
     * @static
     */
    protected static $debugEmailReceiver = '';

    /**
     * Contains the debug domains from settings -> system -> Email Settings -> Debug email domains
     * @var array
     * @static
     */
    protected static $debugDomains;

    /**
     * Contains the debug email addresses from settings -> system -> Email Settings -> Debug email addresses
     * @var array
     * @static
     */
    protected static $debugEmailAddresses;


    protected $placeholderObject;
    protected $temporaryStorage = array();
    protected $loggingEnable = true;

    protected $document; //contains the email document
    protected $params = array();   //contains the dynamic Params for the Placeholders

    public function __construct(Array $options = array()){
        parent::__construct($options["charset"] ? $options["charset"] : "UTF-8");

        if($options["document"]){
            $this->setDocument($options["document"]);
        }
        if($options['params']){
            $this->setParams($options['params']);
        }
        if($options['subject']){
           $this->setSubject($options['subject']);
        }

        $this->init();
    }


    /**
     * Initializes the mailer with the settings form Settings -> System -> Email Settings
     * @return void
     */
    protected function init(){
       $systemConfig    = Pimcore_Config::getSystemConfig()->toArray();
       $emailSettings   =& $systemConfig['email'];

       if($emailSettings['sender']['email']) {
           $this->setDefaultFrom($emailSettings['sender']['email'],$emailSettings['sender']['name']);
       }

       if($emailSettings['return']['email']) {
           $this->setDefaultReplyTo($emailSettings['return']['email'],$emailSettings['return']['name']);
       }

       if($emailSettings['method']=="smtp"){

           $config = array();
           if($emailSettings['smtp']['name']){
               $config['name'] =  $emailSettings['smtp']['name'];
           }
           if($emailSettings['smtp']['ssl']){
               $config['ssl'] =  $emailSettings['smtp']['ssl'];
           }
           if($emailSettings['smtp']['port']){
               $config['port'] =  $emailSettings['smtp']['port'];
           }
           if($emailSettings['smtp']['auth']['method']){
               $config['auth'] =  $emailSettings['smtp']['auth']['method'];
               $config['username'] = $emailSettings['smtp']['auth']['username'];
               $config['password'] = $emailSettings['smtp']['auth']['password'];
           }

           $transport = new Zend_Mail_Transport_Smtp($emailSettings['smtp']['host'], $config);
           $this->setDefaultTransport($transport);
       }

       //setting email debug domains
       if(is_null(self::$debugDomains)){
            $debugDomains = array();
            if($emailSettings['debug']['emaildomains']){
                foreach(explode(',',$emailSettings['debug']['emaildomains']) as $emailDomain){
                    $debugDomains[] = $emailDomain;
                }
            }
            self::$debugDomains = $debugDomains;
       }


       //setting debug email addresses
        if(is_null(self::$debugEmailAddresses)){
            $debugEmailAddresses = array();
            if($emailSettings['debug']['emailaddresses']){
                foreach(explode(',',$emailSettings['debug']['emailaddresses']) as $emailAddress){
                    $debugEmailAddresses[] = $emailAddress;
                }
            }
            self::$debugEmailAddresses = $debugEmailAddresses;
        }

       $this->placeholderObject = new Pimcore_Placeholder();
    }






    /*** start - overwriting Zend_Mail methods - necessary for Logging ***/

    public function addTo($email,$name = ''){
        $this->addToTemporaryStorage('To',$email,$name);
        return parent::addTo($email,$name);
    }

    public function addCc($email,$name = ''){
        $this->addToTemporaryStorage('Cc',$email,$name);
        return parent::addCc($email,$name);
    }

    public function addBcc($email,$name = ''){
        $this->addToTemporaryStorage('Bcc',$email,$name);
        return parent::addCc($email,$name);
    }

    public function clearRecipients(){
        unset($this->temporaryStorage['To']);
        unset($this->temporaryStorage['Cc']);
        unset($this->temporaryStorage['Bcc']);
        return parent::clearRecipients();
    }


    protected function addToTemporaryStorage($key,$email,$name){
        if (!is_array($email)) {
            $email = array($name => $email);
        }
        foreach ($email as $n => $recipient) {
            $this->temporaryStorage[$key][] = array('email' => $recipient, 'name' => is_int($n) ? '' : $n);
        }
    }

    public function getTemporaryStorage(){
        return $this->temporaryStorage;
    }

    /*** end - overwriting Zend_Mail methods ***/



    public function disableLogging(){
        $this->loggingEnable = false;
    }

    public function enableLogging(){
        $this->loggingEnable = true;
    }

    public function setParams(Array $params){
        foreach($params as $key => $value){
            $this->setParam($key,$value);
        }
    }

    public function setParam($key,$value){
        if(is_string($key) || is_integer($key)){
            $this->params[$key] = $value;
        }else{
            Logger::warn('$key has to be a string - Param ignored!');
        }
    }

    public function getParams(){
        return $this->params;
    }

    public function getParam($key){
        return $this->params[$key];
    }

    public function unsetParams(Array $params){
        foreach($params as $param){
            $this->unsetParam($param);
        }
    }

    public function unsetParam($key){
        if(is_string($key) || is_integer($key)){
            unset($this->params[$key]);
        }else{
            Logger::warn('$key has to be a string - unsetParam ignored!');
        }
    }


    public function setDocumentSettings(){
        $document = $this->getDocument();

        $to = $document->getToAsArray();
        if(!empty($to)){
            $this->addTo($to);
        }

        $cc = $document->getCcAsArray();
        if(!empty($cc)){
            $this->addCc($cc);
        }

        $bcc = $document->getBccAsArray();
        if(!empty($bcc)){
            $this->addBcc($bcc);
        }

        list($from) = $document->getFromAsArray();
        if($from){
            $this->clearFrom();
            $this->setFrom($from);
        }
    }

    public function send($transport = null){
        if($this->getDocument()){
            $this->setDocumentSettings();
        }
        $this->setSubject($this->getSubjectRendered());
        $this->setBodyHtml($this->getBodyHtmlRendered());
        $this->checkDebugMode();
        if($this->loggingEnable){
            $this->log(); //Logging to db and file System
        }
        parent::send($transport);
    }


    /**
     * Checks if "To" is a valid debug email address and sets the mailer
     * and all further instances into debug mode
     * @return void
     */
    protected function checkDebugMode(){
        if(!self::$debugEmailReceiver){
            $receiver = '';
            $debugEmailAddresses    = self::$debugEmailAddresses;
            $debugDomains           = self::$debugDomains;

            if(is_array(self::$debugEmailAddresses)){
                array_walk($debugEmailAddresses,function(&$email){$email = 'debug-'.$email;});
            }

            foreach($this->temporaryStorage['To'] as $recipient){
                if(in_array($receiver,$debugEmailAddresses)){
                    $receiver = str_replace('debug-','',$debugEmailAddresses);
                    break;
                }elseif(is_array($debugDomains)){
                    foreach($debugDomains as $debugDomain){
                        $pattern = "/^debug-.*@{$debugDomain}$/i";
                        if(preg_match($pattern,$recipient['email'])){
                            $receiver = $recipient['email'];
                            break;
                        }
                    }
                }
            }
            self::$debugEmailReceiver = str_replace('debug-','',$receiver);
        }


        // if debug mode is enabled and no debug email address is given -> all email will be sent to the first debug email address in "Settings" -> "System"
        if(Pimcore::inDebugMode() && self::$debugEmailReceiver == ''){
            $validator = new Zend_Validate_EmailAddress();

            if(!$validator->isValid(self::$debugEmailAddresses[0])){
                throw new Exception('No valid debug email address given in "Settings" -> "Stystem" -> "Email Settings"');
            }else{
                self::$debugEmailReceiver = self::$debugEmailAddresses[0];
            }

        }

        if(self::$debugEmailReceiver){
            $this->clearRecipients();
            $this->addTo(self::$debugEmailReceiver);
        }
    }


    public function getSubjectRendered(){
        $subject = $this->getSubject();
        $this->clearSubject();

        if(!$subject && $this->getDocument()){
            $subject = $this->getDocument()->getSubject();
        }
        return $this->placeholderObject->replacePlaceholders($subject,$this->getParams(),$this->getDocument());
    }

    public function getBodyHtmlRendered(){
        $html = $this->getBodyHtml(true);
        if($html){
            $content =  $this->placeholderObject->replacePlaceholders($html,$this->getParams(),$this->getDocument());
        }elseif($this->getDocument() instanceof Document){
            $content =  $this->placeholderObject->replacePlaceholders($this->getDocument(),$this->getParams(),$this->getDocument());
        }

        $content = self::getAbsolutePaths($content);
        return $content;

    }

    public static function getAbsolutePaths($content){
          $domain = Pimcore_Tool::getHostUrl();
          foreach(array('src','href') as $key){
            $content = str_replace($key.'="/',$key.'="'.$domain.'/',$content);
            $content = str_replace("$key='/","$key='".$domain.'/',$content);
          }
          return $content;
    }



    /**
     * Sets the email document
     * @throws Exception
     * @param $document
     * @return void
     */

    public function setDocument($document){
        if($document instanceof Document){   //document passed
            $this->document = $document;
        }elseif((int)$document > 0){ //id of document passed
            $this->setDocument(Document::getById($document));
        }elseif(is_string($document) && $document != ""){ //path of document passed
            $this->setDocument(Document::getByPath($document));
        }else{
            throw new Exception('$document is not an instance of Document');
        }
    }

    /**
     * @return Document_Email | null
     */
    public function getDocument(){
        return $this->document;
    }


    protected function log(){
            $emailLog = new EmailLog();
            $document = $this->getDocument();

            if($document instanceof Document){
                $emailLog->setDocumentId($document->getId());
            }
            $emailLog->setRequestUri(htmlspecialchars($_SERVER['REQUEST_URI']));
            $emailLog->setParams($this->getParams());
            $emailLog->setFrom($this->getFrom());
            $emailLog->setBodyHtml($this->getBodyHtml(true));
            $emailLog->setBodyText($this->getBodyText(true));
            $emailLog->setSubject($this->getSubject());
            $emailLog->setSentDate(time());

            $html = $this->getBodyHtml();
            if($html instanceof Zend_Mime_Part){
                $emailLog->setBodyHtml($html->getRawContent());
            }

            $text = $this->getBodyText();
            if($text instanceof Zend_Mime_Part){
                $emailLog->setBodyText($text->getRawContent());
            }


            //adding receivers
            if(is_array($this->getTemporaryStorage())){
                foreach($this->getTemporaryStorage() as $key => $data){
                    $logString = '';
                    if(is_array($data)){
                        foreach($data as $receiver){
                            $logString .= $receiver['email'];
                            if($receiver['name']){
                                $logString .= ' ('.$receiver['name'].')';
                            }
                            $logString .= ';';
                        }
                    }
                    if(method_exists($emailLog,'set'.$key)){
                        $emailLog->{"set$key"}($logString);
                    }
                }
            }
            $emailLog->save();
        }
}