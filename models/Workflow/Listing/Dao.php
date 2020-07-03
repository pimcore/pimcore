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
 * @package    Staticroute
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Workflow\Listing;

use Pimcore\Model;
use Pimcore\Model\Workflow;

/**
 * @property \Pimcore\Model\Workflow\Listing $model
 */
class Dao extends Model\Dao\PhpArrayTable
{
    public function configure()
    {
        parent::configure();
        $this->setFile('workflowmanagement');
    }

    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Workflow elements
     *
     * @return Workflow[]
     */
    public function load()
    {
        $workflowsData = $this->db->fetchAll($this->model->getFilter(), $this->model->getOrder());

        $workflows = [];
        foreach ($workflowsData as $workflowData) {
            $workflows[] = Model\Workflow::getById($workflowData['id']);
        }

        $this->model->setWorkflows($workflows);

        return $workflows;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $data = $this->db->fetchAll($this->model->getFilter(), $this->model->getOrder());
        $amount = count($data);

        return $amount;
    }
}
