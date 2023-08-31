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

namespace Pimcore\Model\Document;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Email\Dao getDao()
 */
class Email extends Model\Document\PageSnippet
{
    protected string $type = 'email';

    /**
     * Contains the email subject
     *
     * @internal
     */
    protected string $subject = '';

    /**
     * Contains the from email address
     *
     * @internal
     */
    protected string $from = '';

    /**
     * Contains the reply to email addresses
     *
     * @internal
     */
    protected string $replyTo = '';

    /**
     * Contains the email addresses of the recipients
     *
     * @internal
     */
    protected string $to = '';

    /**
     * Contains the carbon copy recipients
     *
     * @internal
     */
    protected string $cc = '';

    /**
     * Contains the blind carbon copy recipients
     *
     * @internal
     */
    protected string $bcc = '';

    protected bool $supportsContentMain = false;

    /**
     * Contains the email subject
     *
     *
     * @return $this
     */
    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Returns the email subject
     *
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Sets the "to" receiver
     *
     *
     * @return $this
     */
    public function setTo(string $to): static
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Returns the "to" receivers
     *
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * Sets the "from" email address
     *
     *
     * @return $this
     */
    public function setFrom(string $from): static
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Returns the "from" email address
     *
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * Sets the "replyTo" email address
     *
     *
     * @return $this
     */
    public function setReplyTo(string $replyTo): static
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * Returns the "replyTo" email address
     *
     */
    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    /**
     * Sets the carbon copy receivers (multiple receivers should be separated with a ",")
     *
     *
     * @return $this
     */
    public function setCc(string $cc): static
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * Returns the carbon copy receivers
     *
     */
    public function getCc(): string
    {
        return $this->cc;
    }

    /**
     * Sets the blind carbon copy receivers (multiple receivers should be separated with a ",")
     *
     *
     * @return $this
     */
    public function setBcc(string $bcc): static
    {
        $this->bcc = $bcc;

        return $this;
    }

    /**
     * Returns the blind carbon copy receivers
     *
     */
    public function getBcc(): string
    {
        return $this->bcc;
    }
}
