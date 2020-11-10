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

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Newsletter\Dao getDao()
 */
class Newsletter extends Model\Document\PageSnippet
{
    /**
     * Static type of the document
     *
     * @var string
     */
    protected $type = 'newsletter';

    /**
     * Contains the email subject
     *
     * @var string
     */
    protected $subject = '';

    /**
     * Contains the plain text part of the email
     *
     * @var string
     */
    protected $plaintext = '';

    /**
     * Contains the from email address
     *
     * @var string
     */
    protected $from = '';

    /**
     * enables adding tracking parameters to all links
     *
     * @var bool
     */
    protected $enableTrackingParameters = false;

    /**
     * @var string
     */
    protected $trackingParameterSource = 'newsletter';

    /**
     * @var string
     */
    protected $trackingParameterMedium = 'email';

    /**
     * @var string
     */
    protected $trackingParameterName = null;

    /**
     * @var string
     */
    protected $sendingMode = \Pimcore\Tool\Newsletter::SENDING_MODE_SINGLE;

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
     * Contains the email plain text part
     *
     * @param string $plaintext
     *
     * @return $this
     */
    public function setPlaintext($plaintext)
    {
        $this->plaintext = $plaintext;

        return $this;
    }

    /**
     * Returns the email plain text part
     *
     * @return string
     */
    public function getPlaintext()
    {
        return $this->plaintext;
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
     * @return bool
     */
    public function getEnableTrackingParameters()
    {
        return $this->enableTrackingParameters;
    }

    /**
     * @param bool $enableTrackingParameters
     */
    public function setEnableTrackingParameters($enableTrackingParameters)
    {
        $this->enableTrackingParameters = $enableTrackingParameters;
    }

    /**
     * @return string
     */
    public function getTrackingParameterSource()
    {
        return $this->trackingParameterSource;
    }

    /**
     * @param string $trackingParameterSource
     */
    public function setTrackingParameterSource($trackingParameterSource)
    {
        $this->trackingParameterSource = $trackingParameterSource;
    }

    /**
     * @return string
     */
    public function getTrackingParameterMedium()
    {
        return $this->trackingParameterMedium;
    }

    /**
     * @param string $trackingParameterMedium
     */
    public function setTrackingParameterMedium($trackingParameterMedium)
    {
        $this->trackingParameterMedium = $trackingParameterMedium;
    }

    /**
     * returns key by default
     *
     * @return string
     */
    public function getTrackingParameterName()
    {
        if (is_null($this->trackingParameterName)) {
            return $this->getKey();
        }

        return $this->trackingParameterName;
    }

    /**
     * @param string $trackingParameterName
     */
    public function setTrackingParameterName($trackingParameterName)
    {
        $this->trackingParameterName = $trackingParameterName;
    }

    /**
     * @return string
     */
    public function getSendingMode()
    {
        return $this->sendingMode;
    }

    /**
     * @param string $sendingMode
     */
    public function setSendingMode($sendingMode)
    {
        $this->sendingMode = $sendingMode;
    }

    /**
     * @return string
     */
    public function getTmpStoreId()
    {
        return 'newsletter__' . $this->getId();
    }
}
