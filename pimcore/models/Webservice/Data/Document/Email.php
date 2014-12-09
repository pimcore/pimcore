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
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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
