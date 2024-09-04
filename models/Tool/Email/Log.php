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

namespace Pimcore\Model\Tool\Email;

use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Tool\Storage;

/**
 * @internal
 *
 * @method \Pimcore\Model\Tool\Email\Log\Dao getDao()
 */
class Log extends Model\AbstractModel
{
    /**
     * EmailLog Id
     */
    protected ?int $id = null;

    /**
     * Id of the email document or null if no document was given
     */
    protected ?int $documentId = null;

    /**
     * Parameters passed for replacement
     */
    protected string|array $params;

    /**
     * Modification date as timestamp
     */
    protected int $modificationDate;

    /**
     * The request URI from were the email was sent
     */
    protected string $requestUri;

    /**
     * The "from" email address
     */
    protected string $from;

    /**
     * Contains the reply to email addresses (multiple recipients are separated by a ",")
     */
    protected ?string $replyTo = null;

    /**
     * The "to" recipients (multiple recipients are separated by a ",")
     */
    protected ?string $to = null;

    /**
     * The carbon copy recipients (multiple recipients are separated by a ",")
     */
    protected ?string $cc = null;

    /**
     * The blind carbon copy recipients (multiple recipients are separated by a ",")
     */
    protected ?string $bcc = null;

    /**
     * Contains 1 if a html logfile exists and 0 if no html logfile exists
     */
    protected int $emailLogExistsHtml;

    /**
     * Contains 1 if a text logfile exists and 0 if no text logfile exists
     */
    protected int $emailLogExistsText;

    /**
     * Contains the timestamp when the email was sent
     */
    protected int $sentDate;

    /**
     * Contains the rendered html content of the email
     */
    protected string $bodyHtml = '';

    /**
     * Contains the rendered text content of the email
     */
    protected string $bodyText = '';

    /**
     * Contains the rendered subject of the email
     */
    protected string $subject;

    /**
     * Error log, when mail send resulted in failure - empty if successfully sent
     */
    protected ?string $error = null;

    /**
     * @return $this
     */
    public function setDocumentId(int $id): static
    {
        $this->documentId = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setRequestUri(string $requestUri): static
    {
        $this->requestUri = $requestUri;

        return $this;
    }

    /**
     * Returns the request uri
     */
    public function getRequestUri(): string
    {
        return $this->requestUri;
    }

    /**
     * Returns the email log id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Returns the subject
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Returns the EmailLog entry by the given id
     */
    public static function getById(int $id): ?Log
    {
        if ($id < 1) {
            return null;
        }

        try {
            $emailLog = new Model\Tool\Email\Log();
            $emailLog->getDao()->getById($id);
            $emailLog->setEmailLogExistsHtml();
            $emailLog->setEmailLogExistsText();

            return $emailLog;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    /**
     * Returns the email document id
     */
    public function getDocumentId(): ?int
    {
        return $this->documentId;
    }

    /**
     * @return $this
     */
    public function setParams(string|array $params): static
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Returns the dynamic parameter
     */
    public function getParams(): array
    {
        if (is_string($this->params)) {
            $this->params = json_decode($this->params, true);
        }

        return $this->params;
    }

    /**
     * Sets the modification date
     *
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Returns the modification date
     *
     * @return int - Timestamp
     */
    public function getModificationDate(): int
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
    public function setSentDate(int $sentDate): static
    {
        $this->sentDate = $sentDate;

        return $this;
    }

    /**
     * Returns the sent date and time as unix timestamp
     */
    public function getSentDate(): int
    {
        return $this->sentDate;
    }

    /**
     * Checks if a html log file exits and sets $this->emailLogExistsHtml to 0 or 1
     *
     * @return $this
     */
    public function setEmailLogExistsHtml(): static
    {
        $storage = Storage::get('email_log');
        $storageFile = $this->getHtmlLogFilename();
        $this->emailLogExistsHtml = $storage->fileExists($storageFile) ? 1 : 0;

        return $this;
    }

    /**
     * Returns 1 if a html email log file exists and 0 if no html log file exists
     *
     * @return int - 0 or 1
     */
    public function getEmailLogExistsHtml(): int
    {
        return $this->emailLogExistsHtml;
    }

    /**
     * Checks if a text log file exits and sets $this->emailLogExistsText to 0 or 1
     *
     * @return $this
     */
    public function setEmailLogExistsText(): static
    {
        $storage = Storage::get('email_log');
        $storageFile = $this->getTextLogFilename();
        $this->emailLogExistsText = $storage->fileExists($storageFile) ? 1 : 0;

        return $this;
    }

    /**
     * Returns 1 if a text email log file exists and 0 if no text log file exists
     *
     * @return int - 0 or 1
     */
    public function getEmailLogExistsText(): int
    {
        return $this->emailLogExistsText;
    }

    /**
     * Returns the filename of the html log
     */
    public function getHtmlLogFilename(): string
    {
        return 'email-' . $this->getId() . '-html.log';
    }

    /**
     * Returns the filename of the text log
     */
    public function getTextLogFilename(): string
    {
        return 'email-' . $this->getId() . '-txt.log';
    }

    /**
     * Returns the content of the html log file
     */
    public function getHtmlLog(): string|false
    {
        if ($this->getEmailLogExistsHtml()) {
            $storage = Storage::get('email_log');

            return $storage->read($this->getHtmlLogFilename());
        }

        return false;
    }

    /**
     * Returns the content of the text log file
     */
    public function getTextLog(): string|false
    {
        if ($this->getEmailLogExistsText()) {
            $storage = Storage::get('email_log');

            return $storage->read($this->getTextLogFilename());
        }

        return false;
    }

    /**
     * Removes the log file entry from the db and removes the log files on the system
     */
    public function delete(): void
    {
        $storage = Storage::get('email_log');
        $storage->delete($this->getHtmlLogFilename());
        $storage->delete($this->getTextLogFilename());
        $this->getDao()->delete();
    }

    public function save(): void
    {
        $this->getDao()->save();

        $storage = Storage::get('email_log');

        if ($html = $this->getBodyHtml()) {
            try {
                $storage->write($this->getHtmlLogFilename(), $html);
            } catch (FilesystemException | UnableToWriteFile $exception) {
                Logger::warn('Could not write html email log file.'.$exception.' LogId: ' . $this->getId());
            }
        }

        if ($text = $this->getBodyText()) {
            try {
                $storage->write($this->getTextLogFilename(), $text);
            } catch (FilesystemException | UnableToWriteFile $exception) {
                Logger::warn('Could not write text email log file.'.$exception.' LogId: ' . $this->getId());
            }
        }
    }

    /**
     * @return $this
     */
    public function setTo(?string $to): static
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Returns the "to" recipients
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * @return $this
     */
    public function setCc(?string $cc): static
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * Returns the carbon copy recipients
     */
    public function getCc(): ?string
    {
        return $this->cc;
    }

    /**
     * @return $this
     */
    public function setBcc(?string $bcc): static
    {
        $this->bcc = $bcc;

        return $this;
    }

    /**
     * Returns the blind carbon copy recipients
     */
    public function getBcc(): ?string
    {
        return $this->bcc;
    }

    /**
     * @return $this
     */
    public function setFrom(string $from): static
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Returns the "from" email address
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return $this
     */
    public function setReplyTo(string $replyTo): static
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * Returns the "replyTo" email address
     */
    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }

    /**
     * @return $this
     */
    public function setBodyHtml(string $html): static
    {
        $this->bodyHtml = $html;

        return $this;
    }

    /**
     * returns the html content of the email
     */
    public function getBodyHtml(): ?string
    {
        return $this->bodyHtml;
    }

    /**
     * @return $this
     */
    public function setBodyText(string $text): static
    {
        $this->bodyText = $text;

        return $this;
    }

    /**
     * Returns the text version of the email
     */
    public function getBodyText(): string
    {
        return $this->bodyText;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }
}
