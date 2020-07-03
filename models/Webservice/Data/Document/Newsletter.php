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
 * @package    Webservice
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice\Data\Document;

use Pimcore\Model;

/**
 * @deprecated
 */
class Newsletter extends Model\Webservice\Data\Document\Snippet
{
    /**
     * Static type of the document
     *
     * @var string
     */
    public $type = 'newsletter';

    /**
     * Contains the email subject
     *
     * @var string
     */
    public $subject = '';

    /**
     * Contains the from email address
     *
     * @var string
     */
    public $from = '';

    /**
     * enables adding tracking parameters to all links
     *
     * @var bool
     */
    public $enableTrackingParameters = false;

    /**
     * @var string
     */
    public $trackingParameterSource = 'newsletter';

    /**
     * @var string
     */
    public $trackingParameterMedium = 'email';

    /**
     * @var string
     */
    public $trackingParameterName = '';

    /**
     * @var string
     */
    public $sendingMode = \Pimcore\Tool\Newsletter::SENDING_MODE_SINGLE;
}
