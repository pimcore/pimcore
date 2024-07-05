<?php

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

namespace Pimcore\Model\Tool\Email\Log\Listing;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Tool\Email\Log\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of Email_Log for the specified parameters, returns an array of Email_Log elements
     *
     */
    public function load(): array
    {
        $emailLogs = $this->db->fetchFirstColumn('SELECT id FROM email_log' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

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
     */
    public function getDataArray(): array
    {
        $emailLogData = $this->db->fetchAllAssociative('SELECT * FROM email_log ' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return $emailLogData;
    }

    /**
     * Returns the total amount of Email_Log entries
     *
     */
    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM email_log ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return 0;
        }
    }
}
