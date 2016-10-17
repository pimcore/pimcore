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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Email\Log\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Tool\Email\Log\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * Loads a list of Email_Log for the specified parameters, returns an array of Email_Log elements
     *
     * @return array
     */
    public function load()
    {
        $emailLogs = $this->db->fetchCol("SELECT id FROM email_log" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $emailLogsArray = [];
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
    public function getDataArray()
    {
        $emailLogData = $this->db->fetchAll("SELECT * FROM email_log " . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return $emailLogData;
    }

    /**
     * Returns the total amount of Email_Log entries
     *
     * @return integer
     */
    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM email_log " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
