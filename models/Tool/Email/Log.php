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
 * @category   Pimcore
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Email;

use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\Email\Log\Dao getDao()
 */
class Log extends Model\AbstractModel
{
    /**
     * EmailLog Id
     *
     * @var int
     */
    public $id;

    /**
     * Id of the email document or null if no document was given
     *
     * @var int | null
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
     * @var int
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
     * Contains the reply to email addresses (multiple recipients are separated by a ",")
     *
     * @var string
     */
    public $replyTo;

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
     * @var int
     */
    public $emailLogExistsHtml;

    /**
     * Contains 1 if a text logfile exists and 0 if no text logfile exists
     *
     * @var int
     */
    public $emailLogExistsText;

    /**
     * Contains the timestamp when the email was sent
     *
     * @var int
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
     * @param int $id
     *
     * @return $this
     */
    public function setDocumentId($id)
    {
        $this->documentId = $id;

        return $this;
    }

    /**
     * @param string $requestUri
     *
     * @return $this
     */
    public function setRequestUri($requestUri)
    {
        $this->requestUri = $requestUri;

        return $this;
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
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int)$id;

        return $this;
    }

    /**
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
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
     *
     * @param int $id
     *
     * @return Log|null
     */
    public static function getById($id)
    {
        $id = intval($id);
        if ($id < 1) {
            return null;
        }

        $emailLog = new Model\Tool\Email\Log();
        $emailLog->getDao()->getById($id);
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
     * @param array $params
     *
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Returns the dynamic parameter
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Sets the modification date
     *
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Returns the modification date
     *
     * @return int - Timestamp
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Sets the sent date and time
     *
     * @param int $sentDate - Timestamp
     *
     * @return $this
     */
    public function setSentDate($sentDate)
    {
        $this->sentDate = $sentDate;

        return $this;
    }

    /**
     * Returns the sent date and time as unix timestamp
     *
     * @return int
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

        return $this;
    }

    /**
     * Returns 1 if a html email log file exists and 0 if no html log file exists
     *
     * @return int - 0 or 1
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

        return $this;
    }

    /**
     * Returns 1 if a text email log file exists and 0 if no text log file exists
     *
     * @return int - 0 or 1
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

        return false;
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

        return false;
    }

    /**
     * Removes the log file entry from the db and removes the log files on the system
     */
    public function delete()
    {
        @unlink(PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-html.log');
        @unlink(PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-text.log');
        $this->getDao()->delete();
    }

    public function save()
    {
        $this->getDao()->save();
        if (!is_dir(PIMCORE_LOG_MAIL_PERMANENT)) {
            File::mkdir(PIMCORE_LOG_MAIL_PERMANENT);
        }

        if ($html = $this->getBodyHtml()) {
            if (File::put(PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-html.log', $html) === false) {
                Logger::warn('Could not write html email log file. LogId: ' . $this->getId());
            }
        }

        if ($text = $this->getBodyText()) {
            if (File::put(PIMCORE_LOG_MAIL_PERMANENT . '/email-' . $this->getId() . '-text.log', $text) === false) {
                Logger::warn('Could not write text email log file. LogId: ' . $this->getId());
            }
        }
    }

    /**
     * @param string $to
     *
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
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
     * @param string $cc
     *
     * @return $this
     */
    public function setCc($cc)
    {
        $this->cc = $cc;

        return $this;
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
     * @param string $bcc
     *
     * @return $this
     */
    public function setBcc($bcc)
    {
        $this->bcc = $bcc;

        return $this;
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
     * @param string $from
     *
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
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
     * @param string $replyTo
     *
     * @return $this
     */
    public function setReplyTo($replyTo)
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * Returns the "replyTo" email address
     *
     * @return string
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * @param string $html
     *
     * @return $this
     */
    public function setBodyHtml($html)
    {
        $this->bodyHtml = $html;

        return $this;
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
     * @param string $text
     *
     * @return $this
     */
    public function setBodyText($text)
    {
        $this->bodyText = $text;

        return $this;
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
}
