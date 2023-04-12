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

namespace Pimcore\Bundle\NewsletterBundle\Model\Document;

use Pimcore\Bundle\NewsletterBundle\Model\Document\Newsletter\Dao;
use Pimcore\Bundle\NewsletterBundle\Tool\Newsletter as NewsletterTool;
use Pimcore\Model\Document\Email;

/**
 * @method Dao getDao()
 */
class Newsletter extends Email
{
    /**
     * {@inheritdoc}
     */
    protected string $type = 'newsletter';

    /**
     * Contains the email subject
     *
     * @internal
     */
    protected string $subject = '';

    /**
     * Contains the plain text part of the email
     *
     * @internal
     */
    protected string $plaintext = '';

    /**
     * Contains the from email address
     *
     * @internal
     */
    protected string $from = '';

    /**
     * enables adding tracking parameters to all links
     *
     * @internal
     */
    protected bool $enableTrackingParameters = false;

    /**
     * @internal
     */
    protected string $trackingParameterSource = 'newsletter';

    /**
     * @internal
     */
    protected string $trackingParameterMedium = 'email';

    /**
     * @internal
     */
    protected ?string $trackingParameterName = null;

    /**
     * @internal
     */
    protected string $sendingMode = NewsletterTool::SENDING_MODE_SINGLE;

    /**
     * {@inheritdoc}
     */
    protected bool $supportsContentMain = false;

    /**
     * Contains the email subject
     *
     * @param string $subject
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
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Sets the "from" email address
     *
     * @param string $from
     *
     * @return $this
     */
    public function setFrom(string $from): static
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Contains the email plain text part
     *
     * @param string $plaintext
     *
     * @return $this
     */
    public function setPlaintext(string $plaintext): static
    {
        $this->plaintext = $plaintext;

        return $this;
    }

    /**
     * Returns the email plain text part
     *
     * @return string
     */
    public function getPlaintext(): string
    {
        return $this->plaintext;
    }

    /**
     * Returns the "from" email address
     *
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    public function getEnableTrackingParameters(): bool
    {
        return $this->enableTrackingParameters;
    }

    public function setEnableTrackingParameters(bool $enableTrackingParameters): void
    {
        $this->enableTrackingParameters = $enableTrackingParameters;
    }

    public function getTrackingParameterSource(): string
    {
        return $this->trackingParameterSource;
    }

    public function setTrackingParameterSource(string $trackingParameterSource): void
    {
        $this->trackingParameterSource = $trackingParameterSource;
    }

    public function getTrackingParameterMedium(): string
    {
        return $this->trackingParameterMedium;
    }

    public function setTrackingParameterMedium(string $trackingParameterMedium): void
    {
        $this->trackingParameterMedium = $trackingParameterMedium;
    }

    /**
     * returns key by default
     *
     * @return string|null
     */
    public function getTrackingParameterName(): ?string
    {
        if (is_null($this->trackingParameterName)) {
            return $this->getKey();
        }

        return $this->trackingParameterName;
    }

    public function setTrackingParameterName(string $trackingParameterName): void
    {
        $this->trackingParameterName = $trackingParameterName;
    }

    public function getSendingMode(): string
    {
        return $this->sendingMode;
    }

    public function setSendingMode(string $sendingMode): void
    {
        $this->sendingMode = $sendingMode;
    }

    /**
     * @internal
     *
     * @return string
     */
    public function getTmpStoreId(): string
    {
        return 'newsletter__' . $this->getId();
    }
}
