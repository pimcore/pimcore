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
 * @package    Redirect
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Redirect;

use Pimcore\Model;
use Pimcore\Model\Redirect;
use Pimcore\Model\Site;
use Pimcore\Routing\Redirect\RedirectUrlPartResolver;
use Symfony\Component\HttpFoundation\Request;

/**
 * @property \Pimcore\Model\Redirect $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param int|null $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow('SELECT * FROM redirects WHERE id = ?', $this->model->getId());
        if (!$data) {
            throw new \Exception(sprintf('Redirect with ID %d doesn\'t exist', $this->model->getId()));
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * @param Request $request
     * @param Site|null $site
     * @param bool $override
     *
     * @throws \Exception
     */
    public function getByExactMatch(Request $request, ?Site $site = null, bool $override = false)
    {
        $partResolver = new RedirectUrlPartResolver($request);
        $siteId = $site ? $site->getId() : null;

        $sql = 'SELECT * FROM redirects WHERE
            (
                (source = :sourcePath AND (`type` = :typePath OR `type` = :typeAuto)) OR
                (source = :sourcePathQuery AND `type` = :typePathQuery) OR
                (source = :sourceEntireUri AND `type` = :typeEntireUri)
            ) AND active = 1 AND regex IS NULL AND (expiry > UNIX_TIMESTAMP() OR expiry IS NULL)';

        if ($siteId) {
            $sql .= ' AND sourceSite = ' . $this->db->quote($siteId);
        } else {
            $sql .= ' AND sourceSite IS NULL';
        }

        if ($override) {
            $sql .= ' AND priority = 99';
        }

        $sql .= ' ORDER BY `priority` DESC';

        $data = $this->db->fetchRow($sql, [
            'sourcePath' => $partResolver->getRequestUriPart(Redirect::TYPE_PATH),
            'sourcePathQuery' => $partResolver->getRequestUriPart(Redirect::TYPE_PATH_QUERY),
            'sourceEntireUri' => $partResolver->getRequestUriPart(Redirect::TYPE_ENTIRE_URI),
            'typePath' => Redirect::TYPE_PATH,
            'typePathQuery' => Redirect::TYPE_PATH_QUERY,
            'typeEntireUri' => Redirect::TYPE_ENTIRE_URI,
            'typeAuto' => Redirect::TYPE_AUTO_CREATE,
        ]);

        if (!$data) {
            throw new \Exception('No matching redirect found for the given request');
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->model->getId()) {
            // create in database
            $this->db->insert('redirects', []);

            $this->model->setId($this->db->lastInsertId());
        }

        $this->updateModificationInfos();

        $data = [];
        $type = $this->model->getObjectVars();

        foreach ($type as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('redirects'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update('redirects', $data, ['id' => $this->model->getId()]);
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('redirects', ['id' => $this->model->getId()]);
    }

    protected function updateModificationInfos()
    {
        $updateTime = time();
        $this->model->setModificationDate($updateTime);

        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($updateTime);
        }

        // auto assign user if possible, if no user present, use ID=0 which represents the "system" user
        $userId = 0;
        $user = \Pimcore\Tool\Admin::getCurrentUser();
        if ($user instanceof Model\User) {
            $userId = $user->getId();
        }
        $this->model->setUserModification($userId);

        if ($this->model->getUserOwner() === null) {
            $this->model->setUserOwner($userId);
        }
    }
}
