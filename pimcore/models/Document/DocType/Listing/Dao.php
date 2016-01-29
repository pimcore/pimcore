<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Document\DocType\Listing;

use Pimcore\Model;

class Dao extends Model\Dao\PhpArrayTable
{

    /**
     *
     */
    public function configure()
    {
        parent::configure();
        $this->setFile("document-types");
    }

    /**
     * Loads a list of document-types for the specicifies parameters, returns an array of Document\DocType elements
     *
     * @return array
     */
    public function load()
    {
        $docTypesData = $this->db->fetchAll($this->model->getFilter(), $this->model->getOrder());

        $docTypes = array();
        foreach ($docTypesData as $docTypeData) {
            $docTypes[] = Model\Document\DocType::getById($docTypeData["id"]);
        }

        $this->model->setDocTypes($docTypes);
        return $docTypes;
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
