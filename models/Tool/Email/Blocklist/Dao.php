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

namespace Pimcore\Model\Tool\Email\Blocklist;

use Pimcore\Db\Helper;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Tool\Email\Blocklist $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByAddress(string $address): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM email_blocklist WHERE address = ?', [$address]);

        if (!$data) {
            throw new Model\Exception\NotFoundException('blocklist item with address ' . $address . ' not found');
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     */
    public function save(): void
    {
        $this->model->setModificationDate(time());
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate(time());
        }

        $version = $this->model->getObjectVars();

        // save main table
        $data = [];
        foreach ($version as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('email_blocklist'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        Helper::upsert($this->db, 'email_blocklist', $data, $this->getPrimaryKey('email_blocklist'));
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('email_blocklist', ['address' => $this->model->getAddress()]);
    }
}
