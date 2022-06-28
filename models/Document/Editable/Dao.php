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

namespace Pimcore\Model\Document\Editable;

use Pimcore\Db\Helper;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Document\Editable\Areablock $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public function save()
    {
        $data = $this->model->getDataForResource();

        if (is_array($data) || is_object($data)) {
            $data = \Pimcore\Tool\Serialize::serialize($data);
        }

        $element = [
            'data' => $data,
            'documentId' => $this->model->getDocumentId(),
            'name' => $this->model->getName(),
            'type' => $this->model->getType(),
        ];

        Helper::insertOrUpdate($this->db, 'documents_editables', $element);
    }

    public function delete()
    {
        $this->db->delete('documents_editables', [
            'documentId' => $this->model->getDocumentId(),
            'name' => $this->model->getName(),
        ]);
    }
}
