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

class Document_Email_Log extends Pimcore_Model_Abstract
{

    /**
     * EmailLog Id
     *
     * @var integer
     */
    public $id;

    /**
     * Id of the email document or null if no document was given
     *
     * @var integer | null
     */
    public $documentId;

    /**
     * Parameters passed for replacement
     *
     * @var array
     */
    public $params;

    /**
     * Modification date as timestamp
     *
     * @var integer
     */
    public $modificationDate;

    /**
     * The request URI from were the email was sent
     *
     * @var string
     */
    public $requestUri;

    /**
     * The "from" email address
     *
     * @var string
     */
    public $from;

    /**
     * The "to" recipients (multiple recipients are separated by a ",")
     *
     * @var string
     */
    public $to;

    /**
     * The carbon copy recipients (multiple recipients are separated by a ",")
     *
     * @var string
     */
    public $cc;

    /**
     * The blind carbon copy recipients (multiple recipients are separated by a ",")
     *
     * @var string
     */
    public $bcc;

    /**
     * Contains 1 if a html logfile exists and 0 if no html logfile exists
     *
     * @var integer
     */
    public $emailLogExistsHtml;

    /**
     * Contains 1 if a text logfile exists and 0 if no text logfile exists
     *
     * @var integer
     */
    public $emailLogExistsText;

    /**
     * Contains the timestamp when the email was sent
     *
     * @var integer
     */
    public $sentDate;

    /**
     * Contains the rendered html content of the email
     *
     * @var string
     */
    public $bodyHtml;

    /**
     * Contains the rendered text content of the email
     *
     * @var string
     */
    public $bodyText;

    /**
     * Contains the rendered subject of the email
     *
     * @var string
     */
    public $subject;

    /**
     * sets the email document id
     *
     * @param integer | null $id
     */
    public function setDocumentId($id)
    {
        $this->documentId = $id;
    }

    /**
     * Sets the request URI
     *
     * @param string $requestUri
     */
    public function setRequestUri($requestUri)
    {
        $this->requestUri = $requestUri;
    }

    /**
     * Returns the request uri
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * Returns the email log id
     *
     * @return integer
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * Sets the email log id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }

    /**
     * Sets the subject
     *
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Returns the subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Returns the EmailLog entry by the given id
     *
     * @static
     * @param integer $id
     * @return EmailLog|null
     */
    public static function getById($id)
    {
        $id = intval($id);
        if ($id < 1) {
            return null;
        }

        $emailLog = new Document_Email_Log();
        $emailLog->getResource()->getById($id);
        $emailLog->setEmailLogExistsHtml();
        $emailLog->setEmailLogExistsText();
        return $emailLog;
    }

    /**
     * Returns the email document id
     *
     * @return int|null
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * Sets the dynamic parameters (will be converted to a JSON string)
     *
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Returns the dynamic parameter
     *
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Sets the modification date
     *
     * @param integer $modificationDate
     * @return void
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * Returns the modification date
     *
     * @return integer - Timestamp
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Sets the sent date and time
     *
     * @param integer $sentDate - Timestamp
     * @return void
     */
    public function setSentDate($sentDate)
    {
        $this->sentDate = $sentDate;
    }

    /**
     * Returns the sent date and time as unix timestamp
     *
     * @return integer
     */
    public function getSentDate()
    {
        return $this->sentDate;
    }

    /**
     *  Checks if a html log file exits and sets $this->emailLogExistsHtml to 0 or 1
     */
    public function setEmailLogExistsHtml()
    {
        $file = PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-html.log';
        $this->emailLogExistsHtml = (is_file($file) && is_readable($file)) ? 1 : 0;
    }

    /**
     * Returns 1 if a html email log file exists and 0 if no html log file exists
     *
     * @return integer - 0 or 1
     */
    public function getEmailLogExistsHtml()
    {
        return $this->emailLogExistsHtml;
    }

    /**
     * Checks if a text log file exits and sets $this->emailLogExistsText to 0 or 1
     */
    public function setEmailLogExistsText()
    {
        $file = PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-text.log';
        $this->emailLogExistsText = (is_file($file) && is_readable($file)) ? 1 : 0;
    }

    /**
     * Returns 1 if a text email log file exists and 0 if no text log file exists
     *
     * @return integer - 0 or 1
     */
    public function getEmailLogExistsText()
    {
        return $this->emailLogExistsText;
    }

    /**
     * Returns the content of the html log file
     *
     * @return string | false
     */
    public function getHtmlLog()
    {
        if ($this->getEmailLogExistsHtml()) {
            return file_get_contents(PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-html.log');
        }
    }

    /**
     * Returns the content of the text log file
     *
     * @return string | false
     */
    public function getTextLog()
    {
        if ($this->getEmailLogExistsText()) {
            return file_get_contents(PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-text.log');
        }
    }

    /**
     * Removes the log file entry from the db and removes the log files on the system
     */
    public function delete()
    {
        @unlink(PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-html.log');
        @unlink(PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-text.log');
        $this->getResource()->delete();
    }


    /**
     * Sets the creation date (unix timestamp)
     *
     * @param integer $creationDate
     * @return void
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * Returns the creation date as unix timestamp
     *
     * @return integer
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Saves the email log entry (forwards to $this->update())
     */
    public function save()
    {
        // set date
        if (!(int)$this->getId()) {
            $this->getResource()->create();
        }
        $this->update();
    }

    /**
     * Updates and save the email log entry to the db and the file-system
     */
    protected function update()
    {
        $this->getResource()->update();
        if (!is_dir(PIMCORE_LOG_MAIL_PERMANENT)) {
            mkdir(PIMCORE_LOG_MAIL_PERMANENT, 0755, true);
        }

        if ($html = $this->getBodyHtml()) {
            if (file_put_contents(PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-html.log', $html) === false) {
                Logger::warn('Could not write html email log file. LogId: ' . $this->getId());
            }
        }

        if ($text = $this->getBodyText()) {
            if (file_put_contents(PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-text.log', $text) === false) {
                Logger::warn('Could not write text email log file. LogId: ' . $this->getId());
            }
        }
    }

    /**
     * Sets the "to" recipients (multiple recipients should be separated by a ",")
     *
     * @param string $to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }

    /**
     * Returns the "to" recipients
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Returns the "to" recipients as array
     *
     * @return array
     */
    public function getToAsArray(){
        return $this->buildArray($this->getTo());
    }

    /**
     * Sets the carbon copy recipients (multiple recipients should be separated by a ",")
     *
     * @param string $cc
     */
    public function setCc($cc)
    {
        $this->cc = $cc;
    }

    /**
     * Returns the carbon copy recipients
     *
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Returns the carbon copy recipients as array
     *
     * @return array
     */
    public function getCcAsArray(){
        return $this->buildArray($this->getCc());
    }

    /**
     * Sets the blind carbon copy recipients (multiple recipients should be separated by a ",")
     *
     * @param string $bcc
     */
    public function setBcc($bcc)
    {
        $this->bcc = $bcc;
    }

    /**
     * Returns the blind carbon copy recipients
     *
     * @return string
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * Returns the blind carbon copy recipients as array
     *
     * @return array
     */
    public function getBccAsArray(){
        return $this->buildArray($this->getBcc());
    }

    /**
     * Sets the "from" email address
     *
     * @param string $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * Returns the "from" email address
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Sets the html content of the email
     *
     * @param string $html
     */
    public function setBodyHtml($html)
    {
        $this->bodyHtml = $html;
    }

    /**
     * returns the html content of the email
     *
     * @return string | null
     */
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    /**
     * Sets the text version of the email
     *
     * @param string $text
     */
    public function setBodyText($text)
    {
        $this->bodyText = $text;
    }

    /**
     * Returns the text version of the email
     *
     * @return string
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * Helper to get the recipients as array
     */
    protected function buildArray($data){
        $dataArray = array();
        $tmp = explode(',',trim($data));

        foreach($tmp as $entry){
            $entry  = trim($entry);
            $tmp2   = explode(' ',$entry);
            $dataArray[] = array('email' => trim($tmp2[0]),
                                 'name' => str_replace(array('(',')'),'',$tmp2[1])
            );
        }
        return $dataArray;
    }
}
