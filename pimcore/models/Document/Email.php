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
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Email extends Document_PageSnippet {

    protected static $validator;
    /**
     * Static type of the document
     *
     * @var string
     */
    public $type = "email";

    /**
     * Contains the email subject
     *
     * @var string
     */
    public $subject = "";

     /**
     * Contains the form email address
     *
     * @var string
     */
    public $from = "";

    /**
     * Contains the recipient email address
     *
     * @var string
     */
    public $to = "";

    /**
     * Contains the carbon copy recipients
     *
     * @var string
     */
    public $cc = "";

    /**
     * Contains the blind carbon copy recipients
     *
     * @var string
     */
    public $bcc = "";

    /**
     * @param string $subject
     * @return void
     */
    public function setSubject($subject){
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getSubject(){
        return $this->subject;
    }


    /**
     * @param string $to
     * @return void
     */
    public function setTo($to){
        $this->to = $to;
    }

    /**
     * @return string
     */
    public function getTo(){
        return $this->to;
    }

    public function getToAsArray(){
        return $this->getAsArray('To');
    }

    protected function getAsArray($key){
        $emailAddresses = explode(',',$this->{'get'.ucfirst($key)}());
        foreach($emailAddresses as $key => $emailAddress){
            if($validAddress = self::validateEmailAddress($emailAddress)){
                $emailAddress[$key] = $validAddress;
            }else{
                unset($emailAddresses[$key]);
            }
        }
        return $emailAddresses;
    }

    public static function validateEmailAddress($emailAddress){
        if(is_null(self::$validator)){
            self::$validator = new Zend_Validate_EmailAddress();
        }
        $emailAddress = trim($emailAddress);
        if(self::$validator->isValid($emailAddress)){
            return $emailAddress;
        }
    }

    /**
     * @param string $from
     * @return void
     */
    public function setFrom($from){
        $this->from = $from;
    }

    /**
     * @return string
     */
    public function getFrom(){
        return $this->from;
    }

    public function getFromAsArray(){
       return $this->getAsArray('From');
    }

    /**
     * @param string $cc
     * @return void
     */
    public function setCc($cc){
        $this->cc = $cc;
    }

    /**
     * @return string
     */
    public function getCc(){
        return $this->cc;
    }

    public function getCcAsArray(){
        return $this->getAsArray('Cc');
    }

    /**
     * @param string $bcc
     * @return void
     */
    public function setBcc($bcc){
        $this->bcc = $bcc;
    }

    /**
     * @return string
     */
    public function getBcc(){
        return $this->bcc;
    }

    public function getBccAsArray(){
       return $this->getAsArray('Bcc');
    }

}
