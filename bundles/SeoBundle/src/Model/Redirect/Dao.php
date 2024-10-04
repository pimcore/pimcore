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

namespace Pimcore\Bundle\SeoBundle\Model\Redirect;

use Exception;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Bundle\SeoBundle\Redirect\RedirectUrlPartResolver;
use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @property \Pimcore\Bundle\SeoBundle\Model\Redirect $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws NotFoundException
     */
    public function getById(int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM redirects WHERE id = ?', [$this->model->getId()]);
        if (!$data) {
            throw new NotFoundException(sprintf('Redirect with ID %d doesn\'t exist', $this->model->getId()));
        }

        $this->assignVariablesToModel($data);
    }

    /**
     *
     * @throws NotFoundException
     */
    public function getByExactMatch(Request $request, ?Site $site = null, bool $override = false): void
    {
        $partResolver = new RedirectUrlPartResolver($request);
        $siteId = $site ? $site->getId() : null;

        $sql = 'SELECT * FROM redirects WHERE
            (
                (source = :sourcePath AND (`type` = :typePath OR `type` = :typeAuto)) OR
                (source = :sourcePathQuery AND `type` = :typePathQuery) OR
                (source = :sourceEntireUri AND `type` = :typeEntireUri)
            ) AND active = 1 AND (regex IS NULL OR regex = 0) AND (expiry > UNIX_TIMESTAMP() OR expiry IS NULL)';

        if ($siteId) {
            $sql .= ' AND sourceSite = ' . $siteId;
        } else {
            $sql .= ' AND sourceSite IS NULL';
        }

        if ($override) {
            $sql .= ' AND priority = 99';
        }

        $sql .= ' ORDER BY `priority` DESC';

        $data = $this->db->fetchAssociative($sql, [
            'sourcePath' => $partResolver->getRequestUriPart(Redirect::TYPE_PATH),
            'sourcePathQuery' => $partResolver->getRequestUriPart(Redirect::TYPE_PATH_QUERY),
            'sourceEntireUri' => $partResolver->getRequestUriPart(Redirect::TYPE_ENTIRE_URI),
            'typePath' => Redirect::TYPE_PATH,
            'typePathQuery' => Redirect::TYPE_PATH_QUERY,
            'typeEntireUri' => Redirect::TYPE_ENTIRE_URI,
            'typeAuto' => Redirect::TYPE_AUTO_CREATE,
        ]);

        if (!$data) {
            throw new NotFoundException('No matching redirect found for the given request');
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            // create in database
            $this->db->insert('redirects', []);

            $this->model->setId((int) $this->db->lastInsertId());
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
    public function delete(): void
    {
        $this->db->delete('redirects', ['id' => $this->model->getId()]);
    }

    protected function updateModificationInfos(): void
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
