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
 * @package    EmailLog
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Email_Log_List extends Pimcore_Model_List_Abstract
{

    /**
     * Contains the results of the list. They are all an instance of Document_Email
     *
     * @var array
     */
    public $emailLogs = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     * @param mixed $key
     * @return boolean
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * Returns a list of EmailLog entries
     *
     * @return array
     */
    public function getEmailLogs()
    {
        return $this->emailLogs;
    }

    /**
     * Sets EmailLog entries
     *
     * @param array $emailLogs
     * @return void
     */
    public function setEmailLogs($emailLogs)
    {
        $this->emailLogs = $emailLogs;
    }
}
