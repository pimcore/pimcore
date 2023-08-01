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

namespace Pimcore\Model\Element\Editlock;

use Pimcore\Db\Helper;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Element\Editlock $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByElement(int $cid, string $ctype): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM edit_lock WHERE cid = ? AND ctype = ?', [$cid, $ctype]);

        if (!$data) {
            throw new Model\Exception\NotFoundException('Lock with cid ' . $cid . ' and ctype ' . $ctype . ' not found');
        }

        $this->assignVariablesToModel($data);

        // add elements path
        $element = Model\Element\Service::getElementById($ctype, $cid);
        if ($element) {
            $this->model->setCpath($element->getRealFullPath());
        }
    }

    /**
     * Save object to database
     *
     *
     * @todo: not all save methods return a boolean, why this one?
     */
    public function save(): bool
    {
        $version = $this->model->getObjectVars();
        $data = [];

        foreach ($version as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('edit_lock'))) {
                $data[$key] = $value;
            }
        }

        Helper::upsert($this->db, 'edit_lock', $data, $this->getPrimaryKey('edit_lock'));

        $lastInsertId = $this->db->lastInsertId();
        if (!$this->model->getId() && $lastInsertId) {
            $this->model->setId((int) $lastInsertId);
        }

        return true;
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('edit_lock', ['id' => $this->model->getId()]);
    }

    public function clearSession(string $sessionId): void
    {
        $this->db->delete('edit_lock', ['sessionId' => $sessionId]);
    }
}
