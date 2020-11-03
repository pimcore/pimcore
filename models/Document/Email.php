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

namespace Pimcore\Model\Document;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Email\Dao getDao()
 */
class Email extends Model\Document\PageSnippet
{
    /**
     * Static type of the document
     *
     * @var string
     */
    protected $type = 'email';

    /**
     * Contains the email subject
     *
     * @var string
     */
    protected $subject = '';

    /**
     * Contains the from email address
     *
     * @var string
     */
    protected $from = '';

    /**
     * Contains the reply to email addresses
     *
     * @var string
     */
    protected $replyTo = '';

    /**
     * Contains the email addresses of the recipients
     *
     * @var string
     */
    protected $to = '';

    /**
     * Contains the carbon copy recipients
     *
     * @var string
     */
    protected $cc = '';

    /**
     * Contains the blind carbon copy recipients
     *
     * @var string
     */
    protected $bcc = '';

    /**
     * @inheritdoc
     */
    protected $supportsContentMaster = false;

    /**
     * Contains the email subject
     *
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
     * Returns the email subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Sets the "to" receiver
     *
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
     * Returns the "to" receivers
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Helper to validate a email address
     *
     * @static
     *
     * @param string $emailAddress
     *
     * @return string | null - returns "null" if the email address is invalid otherwise the email address is returned
     */
    public static function validateEmailAddress($emailAddress)
    {
        $emailAddress = trim($emailAddress);

        $validator = new EmailValidator();
        if ($validator->isValid($emailAddress, new RFCValidation())) {
            return $emailAddress;
        }

        return null;
    }

    /**
     * Sets the "from" email address
     *
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
     * Sets the "replyTo" email address
     *
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
     * Sets the carbon copy receivers (multiple receivers should be separated with a ",")
     *
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
     * Returns the carbon copy receivers
     *
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Sets the blind carbon copy receivers (multiple receivers should be separated with a ",")
     *
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
     * Returns the blind carbon copy receivers
     *
     * @return string
     */
    public function getBcc()
    {
        return $this->bcc;
    }
}
