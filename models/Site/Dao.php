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

namespace Pimcore\Model\Site;

use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property \Pimcore\Model\Site $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM sites WHERE id = ?', [$id]);
        if (!$data) {
            throw new NotFoundException(sprintf('Unable to load site with ID `%s`', $id));
        }
        $this->assignVariablesToModel($data);
    }

    /**
     *
     * @throws NotFoundException
     */
    public function getByRootId(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM sites WHERE rootId = ?', [$id]);
        if (!$data) {
            throw new NotFoundException(sprintf('Unable to load site with ID `%s`', $id));
        }
        $this->assignVariablesToModel($data);
    }

    /**
     *
     * @throws NotFoundException
     */
    public function getByDomain(string $domain): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM sites WHERE mainDomain = ? OR domains LIKE ?', [$domain, '%"' . $domain . '"%']);
        if (!$data) {
            // check for wildcards
            // @TODO: refactor this to be more clear
            $sitesRaw = $this->db->fetchAllAssociative('SELECT id,domains FROM sites');
            $wildcardDomains = [];
            foreach ($sitesRaw as $site) {
                if (!empty($site['domains']) && strpos($site['domains'], '*')) {
                    $siteDomains = unserialize($site['domains']);
                    if (is_array($siteDomains) && count($siteDomains) > 0) {
                        foreach ($siteDomains as $siteDomain) {
                            if (str_contains($siteDomain, '*')) {
                                $siteDomain = str_replace('.*', '*', $siteDomain); // backward compatibility
                                $wildcardDomains[$siteDomain] = $site['id'];
                            }
                        }
                    }
                }
            }

            foreach ($wildcardDomains as $wildcardDomain => $siteId) {
                $wildcardDomain = preg_quote($wildcardDomain, '#');
                $wildcardDomain = str_replace('\\*', '.*', $wildcardDomain);
                if (preg_match('#^' . $wildcardDomain . '$#', $domain)) {
                    $data = $this->db->fetchAssociative('SELECT * FROM sites WHERE id = ?', [$siteId]);
                }
            }

            if (!$data) {
                throw new NotFoundException('there is no site for the requested domain: `' . $domain . '´');
            }
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->create();
        }

        $this->update();
    }

    /**
     * Create a new record for the object in database
     */
    public function create(): void
    {
        $ts = time();
        $this->model->setCreationDate($ts);
        $this->model->setModificationDate($ts);
        $this->db->insert('sites', ['rootId' => $this->model->getRootId()]);
        $this->model->setId((int) $this->db->lastInsertId());
    }

    /**
     * Save changes to database, it's a good idea to use save() instead
     */
    public function update(): void
    {
        $ts = time();
        $this->model->setModificationDate($ts);

        $data = [];
        $site = $this->model->getObjectVars();

        foreach ($site as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('sites'))) {
                if (is_array($value) || is_object($value)) {
                    $value = \Pimcore\Tool\Serialize::serialize($value);
                }
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update('sites', $data, ['id' => $this->model->getId()]);

        $this->model->clearDependentCache();
    }

    /**
     * Deletes site from database
     */
    public function delete(): void
    {
        $this->db->delete('sites', ['id' => $this->model->getId()]);
        //clean slug table
        Model\DataObject\Data\UrlSlug::handleSiteDeleted($this->model->getId());

        $this->model->clearDependentCache();
    }
}
