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
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Document\DocType\Listing;

use Pimcore\Model;

class Dao extends Model\Listing\Dao\AbstractDao {

    /**
     * Loads a list of document-types for the specicifies parameters, returns an array of Document\DocType elements
     *
     * @return array
     */
    public function load() {

        $docTypesData = $this->db->fetchCol("SELECT id FROM documents_doctypes" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $docTypes = array();
        foreach ($docTypesData as $docTypeData) {
            $docTypes[] = Model\Document\DocType::getById($docTypeData);
        }

        $this->model->setDocTypes($docTypes);
        return $docTypes;
    }

}
