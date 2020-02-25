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

namespace Pimcore\Model\Webservice\Data\Document\Email;

use Pimcore\Model;

/**
 * @deprecated
 */
class Out extends Model\Webservice\Data\Document\Snippet\Out
{
    /**
     * @var string
     */
    public $path;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $userModification;

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
     * Contains the email addresses of the recipients
     *
     * @var string
     */
    public $to = '';

    /**
     * Contains the carbon copy recipients
     *
     * @var string
     */
    public $cc = '';

    /**
     * Contains the blind carbon copy recipients
     *
     * @var string
     */
    public $bcc = '';
}
