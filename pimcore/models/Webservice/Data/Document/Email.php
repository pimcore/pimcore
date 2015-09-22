<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Webservice\Data\Document;

use Pimcore\Model;

class Email extends Model\Webservice\Data\Document\Snippet {


    /**
     * Contains a \Zend_Validate_EmailAddress object
     *
     * @var \Zend_Validate_EmailAddress
     */
    protected static $validator;

    /**
     * Static type of the document
     *
     * @var string
     */
    public $type = "email";

    /**
     * Contains the email subject
     *
     * @var string
     */
    public $subject = "";

    /**
     * Contains the from email address
     *
     * @var string
     */
    public $from = "";

    /**
     * Contains the email addresses of the recipients
     *
     * @var string
     */
    public $to = "";

    /**
     * Contains the carbon copy recipients
     *
     * @var string
     */
    public $cc = "";

    /**
     * Contains the blind carbon copy recipients
     *
     * @var string
     */
    public $bcc = "";

}
