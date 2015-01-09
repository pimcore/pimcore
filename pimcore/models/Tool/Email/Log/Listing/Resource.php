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
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\Email\Log\Listing;

use Pimcore\Model;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of Email_Log for the specified parameters, returns an array of Email_Log elements
     *
     * @return array
     */
    public function load() {
        $emailLogs = $this->db->fetchCol("SELECT id FROM email_log" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $emailLogsArray = array();
        foreach ($emailLogs as $log) {
            $emailLogsArray[] = Model\Tool\Email\Log::getById($log);
        }
        $this->model->setEmailLogs($emailLogsArray);

        return $emailLogsArray;
    }

    /**
     * Returns the db entries from email_log by the specified parameters
     *
     * @return array
     */
    public function getDataArray() {
        $emailLogData = $this->db->fetchAll("SELECT * FROM email_log " . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $emailLogData;
    }

    /**
     * Returns the total amount of Email_Log entries
     *
     * @return integer
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM email_log " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }
        return $amount;
    }
}
