<?php


namespace Pimcore\Model\Element\Traits;

use Pimcore\Model\Version;
use Pimcore\Model\Element;

trait VersionDaoTrait
{
    /**
     * Get latest available version, using $force always returns a version no matter if it is the same as the published one
     *
     * @param int|null $userId
     *
     * @return Version|null
     */
    public function getLatestVersion($userId = null)
    {
        $versionId = $this->db->fetchOne("SELECT id FROM versions WHERE cid = :cid AND ctype = :ctype AND (`date` > :mdate OR versionCount > :versionCount) AND ((autoSave = 1 AND userId = :userId) OR autoSave = 0) ORDER BY `versionCount` DESC LIMIT 1", [
            'cid' => $this->model->getId(),
            'ctype' => Element\Service::getType($this->model),
            'userId' => $userId,
            'mdate' => $this->model->getModificationDate(),
            'versionCount' => $this->model->getVersionCount(),
        ]);

        if ($versionId) {
            return Version::getById($versionId);
        }

        return null;
    }

    /**
     * Get available versions fot the object and return an array of them
     *
     * @return Version[]
     */
    public function getVersions()
    {
        $list = new Version\Listing();
        $list->setCondition("cid = :cid AND ctype = :ctype",[
            'cid' => $this->model->getId(),
            'ctype' => Element\Service::getType($this->model),
        ])->setOrderKey('id')->setOrder('ASC');

        $versions = $list->load();

        $this->model->setVersions($versions);

        return $versions;
    }
}
