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

namespace Pimcore\Model\Document\DocType\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Document\DocType\Listing $model
 */
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

        $docTypes = [];
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
