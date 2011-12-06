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
 * @package    Property
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class EmailLog extends Pimcore_Model_Abstract {

    public $id;
    public $documentId;
    public $params;
    public $modificationDate;
    public $requestUri;
    public $from;
    public $to;
    public $cc;
    public $bcc;
    public $emailLogExistsHtml;
    public $emailLogExistsText;
    public $sentDate;
    public $bodyHtml;
    public $bodyText;
    public $subject;

    public function setDocumentId($id){
        $this->documentId = $id;
    }


    public function setRequestUri($requestUri){
        $this->requestUri = $requestUri;
    }

    public function getRequestUri(){
        return $this->requestUri;
    }

    /**
     * @return integer
     */
    public function getId() {
        return (int) $this->id;
    }

    public function setId($id) {
        $this->id = (int) $id;
    }

    public function setSubject($subject){
        $this->subject = $subject;
    }

    public function getSubject(){
        return $this->subject;
    }

    public static function getById($id) {
        $id = intval($id);
        if ($id < 1) {
            return null;
        }

        $emailLog = new EmailLog();
        $emailLog->getResource()->getById($id);
        $emailLog->setEmailLogExistsHtml();
        $emailLog->setEmailLogExistsText();
        return $emailLog;
    }

    public function getDocumentId(){
        return $this->documentId;
    }

    public function setParams($params){
        $this->params = $params;
    }

    public function getParams(){
        return $this->params;
    }

     /**
     * @param integer $modificationDate
     * @return void
     */
    public function setModificationDate($modificationDate) {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return integer
     */
    public function getModificationDate() {
        return $this->modificationDate;
    }

     /**
     * @param integer $modificationDate
     * @return void
     */
    public function setSentDate($sentDate) {
        $this->sentDate = $sentDate;
    }

    /**
     * @return integer
     */
    public function getSentDate() {
        return $this->sentDate;
    }

    public function setEmailLogExistsHtml(){
        $file = PIMCORE_LOG_MAIL_TEMP.'/email-'.$this->getId().'-html.log';
        $this->emailLogExistsHtml =  (is_file($file) && is_readable($file)) ? 1 : 0;
    }
    public function getEmailLogExistsHtml() {
        return $this->emailLogExistsHtml;
    }

    public function setEmailLogExistsText(){
        $file = PIMCORE_LOG_MAIL_TEMP.'/email-'.$this->getId().'-text.log';
        $this->emailLogExistsText = (is_file($file) && is_readable($file)) ? 1 : 0;
    }

    public function getEmailLogExistsText() {
        return $this->emailLogExistsText;
    }

    public function getHtmlLog(){
        if($this->getEmailLogExistsHtml()){
            return file_get_contents(PIMCORE_LOG_MAIL_TEMP.'/email-'.$this->getId().'-html.log');
        }
    }

    public function getTextLog(){
        if($this->getEmailLogExistsText()){
            return file_get_contents(PIMCORE_LOG_MAIL_TEMP.'/email-'.$this->getId().'-text.log');
        }
    }

    public function delete(){
        @unlink(PIMCORE_LOG_MAIL_TEMP.'/email-'.$this->getId().'-html.log');
        @unlink(PIMCORE_LOG_MAIL_TEMP.'/email-'.$this->getId().'-text.log');
        $this->getResource()->delete();
    }



     /**
     * @param integer $creationDate
     * @return void
     */
    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }

    /**
     * @return integer
     */
    public function getCreationDate() {
        return $this->creationDate;
    }


    public function save(){
        // set date
        if(!(int)$this->getId()){
            $this->getResource()->create();
        }
        $this->update();

    }


    protected function update() {
        $this->getResource()->update();
        if(!is_dir(PIMCORE_LOG_MAIL_TEMP)){
            mkdir(PIMCORE_LOG_MAIL_TEMP,0755,true);
        }

        if($html = $this->getBodyHtml()){
            if(file_put_contents(PIMCORE_LOG_MAIL_TEMP.'/email-'.$this->getId().'-html.log',$html) === false){
                Logger::warn('Could not write html email log file. LogId: '.$this->getId());
            }
        }

        if($text = $this->getBodyText()){
            if(file_put_contents(PIMCORE_LOG_MAIL_TEMP.'/email-'.$this->getId().'-text.log',$text) === false){
                Logger::warn('Could not write text email log file. LogId: '.$this->getId());
            }
        }
    }



    public function setTo($to){
        $this->to = $to;
    }

    public function getTo(){
        return $this->to;
    }

    public function setCc($cc){
        $this->cc = $cc;
    }

    public function getCc(){
        return $this->cc;
    }

    public function setBcc($bcc){
        $this->bcc = $bcc;
    }

    public function getBcc(){
        return $this->bcc;
    }

    public function setFrom($from){
        $this->from = $from;
    }

    public function getFrom(){
        return $this->from;
    }

    public function setBodyHtml($html){
        $this->bodyHtml = $html;
    }

    public function getBodyHtml(){

        return $this->bodyHtml;
    }

    public function setBodyText($text){
        $this->bodyText = $text;
    }

    public function getBodyText(){
        return $this->bodyText;
    }
}
